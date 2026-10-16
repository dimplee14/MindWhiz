<?php

/**
 * Memory Test Configuration
 * 
 * All test parameters are centralized here.
 * Client can easily modify test requirements by changing values in this file.
 */

// Test Attempt Settings
define('MAX_ATTEMPTS', 3);              // Maximum number of attempts allowed per student

// Test Content Settings
define('TOTAL_WORDS', 60);              // Total number of words to display
define('PASSING_SCORE', 20);            // Minimum correct words required to pass

// Timer Settings (in seconds)
define('MEMORIZE_TIME', 300);           // Time to memorize words (5 minutes = 300 seconds)
define('RECALL_TIME', 600);             // Time to recall/write words (10 minutes = 600 seconds)

// Word List Files (if using external word files)
define('WORDS_FILE_ENG', __DIR__ . '/../data/words_english.txt');
define('WORDS_FILE_GUJ', __DIR__ . '/../data/words_gujarati.txt');

// Scoring Rules
define('REQUIRE_EXACT_SEQUENCE', true); // true = words must be in exact order, false = any order accepted

// Contact Settings
define('WHATSAPP_CONTACT_NUMBER', '8460996890'); // WhatsApp contact number for support
?>