<?php

// Stub logging functions so controller classes can load without init.php
if (!function_exists('log_debug')) {
    function log_debug(string $msg, array $ctx = []): void
    {
    }
}
if (!function_exists('log_info')) {
    function log_info(string $msg, array $ctx = []): void
    {
    }
}
if (!function_exists('log_warning')) {
    function log_warning(string $msg, array $ctx = []): void
    {
    }
}
if (!function_exists('log_error')) {
    function log_error(string $msg, array $ctx = []): void
    {
    }
}
if (!function_exists('log_exception')) {
    function log_exception(\Throwable $e, array $ctx = []): void
    {
    }
}

// Composer autoloader (for ApiV2 namespace)
require_once __DIR__ . '/../vendor/autoload.php';

// Load helper functions
require_once __DIR__ . '/../src/functions.php';

// Load test helpers
require_once __DIR__ . '/ApiV2/StubVtigerWebhookClient.php';

// Load controller classes
require_once __DIR__ . '/../src/api/classes/base.php';
require_once __DIR__ . '/../src/api/classes/school.php';
require_once __DIR__ . '/../src/api/classes/early_years.php';
require_once __DIR__ . '/../src/api/classes/workplace.php';
require_once __DIR__ . '/../src/api/classes/general.php';
