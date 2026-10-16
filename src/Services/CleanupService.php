<?php

namespace App\Services;

use App\Utils\Database;
use App\Utils\Logger;
use Exception;

/**
 * Data Cleanup Service
 * Handles automatic deletion of old records
 */
class CleanupService
{
    private array $config;
    private int $retentionDays;

    public function __construct()
    {
        $this->config = require CONFIG_PATH . '/cleanup.php';
        $this->retentionDays = $this->config['retention_days'];
    }

    /**
     * Get records eligible for deletion
     */
    public function getOldRecords(): array
    {
        $conn = Database::getConnection();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays} days"));

        $query = "
            SELECT 
                s.id,
                s.name,
                s.parent_name,
                s.surname,
                s.email,
                s.created_at,
                (SELECT COUNT(*) FROM student_attempts WHERE student_id = s.id) as attempts_count
            FROM students s
            WHERE s.created_at < ?
            ORDER BY s.created_at ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $cutoffDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }

        $stmt->close();
        return $records;
    }

    /**
     * Get count of old records
     */
    public function getOldRecordsCount(): int
    {
        $conn = Database::getConnection();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays} days"));

        $query = "SELECT COUNT(*) as count FROM students WHERE created_at < ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $cutoffDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)$row['count'];
    }

    /**
     * Delete old records
     */
    public function deleteOldRecords(): array
    {
        $conn = Database::getConnection();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays} days"));

        try {
            $conn->begin_transaction();

            // Get IDs and emails of students to delete (emails needed for email_verifications table)
            $query = "SELECT id, email FROM students WHERE created_at < ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $cutoffDate);
            $stmt->execute();
            $result = $stmt->get_result();

            $studentIds = [];
            $emails = [];
            while ($row = $result->fetch_assoc()) {
                $studentIds[] = (int)$row['id'];
                if (!empty($row['email'])) {
                    $emails[] = $row['email'];
                }
            }
            $stmt->close();

            if (empty($studentIds)) {
                $conn->commit();
                return ['deleted' => 0, 'message' => 'No old records found'];
            }

            $idPlaceholders = implode(',', array_fill(0, count($studentIds), '?'));

            // Delete related records first (respect FK order). Each query is prepared separately for clarity.
            $totalDeleted = 0;

            // Delete attempt_words linked via student_attempts
            $stmt = $conn->prepare("DELETE FROM attempt_words WHERE attempt_id IN (SELECT id FROM student_attempts WHERE student_id IN ($idPlaceholders))");
            $types = str_repeat('i', count($studentIds));
            $stmt->bind_param($types, ...$studentIds);
            $stmt->execute();
            $totalDeleted += $stmt->affected_rows;
            $stmt->close();

            // Delete attempt_flags linked via student_attempts
            $stmt = $conn->prepare("DELETE FROM attempt_flags WHERE attempt_id IN (SELECT id FROM student_attempts WHERE student_id IN ($idPlaceholders))");
            $stmt->bind_param($types, ...$studentIds);
            $stmt->execute();
            $totalDeleted += $stmt->affected_rows;
            $stmt->close();

            // Delete student_attempts
            $stmt = $conn->prepare("DELETE FROM student_attempts WHERE student_id IN ($idPlaceholders)");
            $stmt->bind_param($types, ...$studentIds);
            $stmt->execute();
            $totalDeleted += $stmt->affected_rows;
            $stmt->close();

            // Delete email_verifications by email (table has no student_id column)
            if (!empty($emails)) {
                $emailPlaceholders = implode(',', array_fill(0, count($emails), '?'));
                $emailTypes = str_repeat('s', count($emails));
                $stmt = $conn->prepare("DELETE FROM email_verifications WHERE email IN ($emailPlaceholders)");
                $stmt->bind_param($emailTypes, ...$emails);
                $stmt->execute();
                $totalDeleted += $stmt->affected_rows;
                $stmt->close();
            }

            // Finally delete students
            $stmt = $conn->prepare("DELETE FROM students WHERE id IN ($idPlaceholders)");
            $stmt->bind_param($types, ...$studentIds);
            $stmt->execute();
            $totalDeleted += $stmt->affected_rows;
            $stmt->close();

            $conn->commit();

            Logger::info("Cleanup completed", [
                'students_deleted' => count($studentIds),
                'total_records_deleted' => $totalDeleted,
                'cutoff_date' => $cutoffDate
            ]);

            return [
                'deleted' => count($studentIds),
                'total_records' => $totalDeleted,
                'message' => count($studentIds) . ' student records and ' . $totalDeleted . ' total records deleted'
            ];

        } catch (Exception $e) {
            $conn->rollback();
            Logger::error("Cleanup failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get retention days configuration
     */
    public function getRetentionDays(): int
    {
        return $this->retentionDays;
    }

    /**
     * Update retention days configuration
     */
    public function updateRetentionDays(int $days): bool
    {
        if ($days < 1) {
            return false;
        }

        // Update .env file
        $envPath = ROOT_PATH . '/.env';
        if (!file_exists($envPath)) {
            return false;
        }

        $content = file_get_contents($envPath);
        
        if (strpos($content, 'DATA_RETENTION_DAYS=') !== false) {
            $content = preg_replace('/DATA_RETENTION_DAYS=\d+/', "DATA_RETENTION_DAYS=$days", $content);
        } else {
            $content .= "\nDATA_RETENTION_DAYS=$days\n";
        }

        file_put_contents($envPath, $content);
        $this->retentionDays = $days;

        Logger::info("Retention days updated", ['new_value' => $days]);
        return true;
    }

    /**
     * Preview what will be deleted
     */
    public function getCleanupPreview(): array
    {
        $records = $this->getOldRecords();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays} days"));

        return [
            'retention_days' => $this->retentionDays,
            'cutoff_date' => $cutoffDate,
            'records_count' => count($records),
            'records' => array_slice($records, 0, 10), // First 10 records as preview
            'has_more' => count($records) > 10
        ];
    }
}
