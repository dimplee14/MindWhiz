<?php
/**
 * Cleanup Old Records API
 * Handles viewing and deleting old student records
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../admin/session_check.php';

use App\Services\CleanupService;
use App\Utils\Response;
use App\Utils\Logger;

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'preview';
    $cleanupService = new CleanupService();

    switch ($action) {
        case 'preview':
            // Get preview of records to be deleted
            $preview = $cleanupService->getCleanupPreview();
            Response::success('Cleanup preview retrieved', $preview);
            break;

        case 'count':
            // Get count of old records
            $count = $cleanupService->getOldRecordsCount();
            Response::success('Count retrieved', ['count' => $count]);
            break;

        case 'delete':
            // Delete old records (requires POST)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }

            $result = $cleanupService->deleteOldRecords();
            Logger::info('Admin initiated cleanup', [
                'admin_id' => $_SESSION['admin_id'],
                'result' => $result
            ]);
            Response::success($result['message'], $result);
            break;

        case 'get_settings':
            // Get current cleanup settings
            $settings = [
                'retention_days' => $cleanupService->getRetentionDays(),
                'old_records_count' => $cleanupService->getOldRecordsCount()
            ];
            Response::success('Settings retrieved', $settings);
            break;

        case 'update_settings':
            // Update retention days (requires POST)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $days = (int)($data['retention_days'] ?? 0);

            if ($days < 1 || $days > 365) {
                Response::error('Retention days must be between 1 and 365');
            }

            $success = $cleanupService->updateRetentionDays($days);
            if ($success) {
                Logger::info('Retention days updated', [
                    'admin_id' => $_SESSION['admin_id'],
                    'new_days' => $days
                ]);
                Response::success('Settings updated successfully', ['retention_days' => $days]);
            } else {
                Response::error('Failed to update settings');
            }
            break;

        default:
            Response::error('Invalid action');
    }

} catch (Exception $e) {
    Logger::error('Cleanup API error', ['error' => $e->getMessage()]);
    Response::error('An error occurred: ' . $e->getMessage(), [], 500);
}
