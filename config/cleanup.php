<?php
/**
 * Data Cleanup Configuration
 */

return [
    // Number of days to retain student records
    'retention_days' => (int)env('DATA_RETENTION_DAYS', 30),
    
    // Enable automatic cleanup
    'auto_cleanup_enabled' => env('AUTO_CLEANUP_ENABLED', false),
    
    // Require admin approval before deletion
    'require_admin_approval' => env('CLEANUP_REQUIRE_ADMIN_APPROVAL', true),
    
    // Cleanup schedule (for cron jobs)
    'schedule' => 'daily', // daily, weekly, monthly
    
    // What to delete
    'cleanup_targets' => [
        'students' => true,           // Delete old student records
        'attempts' => true,           // Delete old attempt records
        'email_verifications' => true, // Delete old verification codes
    ],
];
