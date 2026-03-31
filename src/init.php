<?php

chdir(dirname(__FILE__));

require_once 'config.php';
require_once 'lib/class_dhpdo.php';

// Database connection is lazy-loaded - only create if needed
$dbh = null;

function get_db()
{
    global $dbh, $local_config;

    if ($dbh === null) {
        try {
            $dbh = new dhpdo($local_config);
            log_info('Database connection established');
        } catch (Exception $e) {
            log_exception($e, ['component' => 'database_init']);
            throw $e;
        }
    }

    return $dbh;
}

require_once 'lib/class_dhrest.php';
require_once 'lib/class_dhvt.php';

require_once 'functions.php';

try {
    $vtod = init_vtod();
} catch (Exception $e) {
    log_exception($e, ['component' => 'vtiger_init']);
    return  'Error Message: ' .$e->getMessage();
}

/* Instance init functions */
function init_vtod()
{
    global $vtod_config;
    try {
        // Use configured timeout or default to 25 seconds (HTTP API Gateway limit is 30s)
        $timeout = isset($vtod_config['timeout']) ? $vtod_config['timeout'] : 25;
        $vtod = new dhvt($vtod_config['url'].'webservice.php', $vtod_config['username'], $vtod_config['accesskey'], $timeout);
        log_info('Vtiger client initialized', ['timeout' => $timeout]);
        return $vtod;
    } catch (Exception $e) {
        log_exception($e, ['component' => 'vtiger_connection']);
        return 'Error Message: ' .$e->getMessage();
    }

}

/**
 * Enhanced logging functions that log to error_log with context
 */

/**
 * Log debug information
 * @param string $message The message to log
 * @param array $context Additional context data
 */
function log_debug($message, $context = [])
{
    $log_message = '[DEBUG] ' . $message;
    if (!empty($context)) {
        $log_message .= ' | Context: ' . json_encode($context);
    }
    error_log($log_message);
}

/**
 * Log info message
 * @param string $message The message to log
 * @param array $context Additional context data
 */
function log_info($message, $context = [])
{
    $log_message = '[INFO] ' . $message;
    if (!empty($context)) {
        $log_message .= ' | Context: ' . json_encode($context);
    }
    error_log($log_message);
}

/**
 * Log warning message
 * @param string $message The message to log
 * @param array $context Additional context data
 */
function log_warning($message, $context = [])
{
    $log_message = '[WARNING] ' . $message;
    if (!empty($context)) {
        $log_message .= ' | Context: ' . json_encode($context);
    }
    error_log($log_message);
}

/**
 * Log error message
 * @param string $message The message to log
 * @param array $context Additional context data
 */
function log_error($message, $context = [])
{
    $log_message = '[ERROR] ' . $message;
    if (!empty($context)) {
        $log_message .= ' | Context: ' . json_encode($context);
    }
    error_log($log_message);
}

/**
 * Log exception
 * @param Exception $exception The exception to log
 * @param array $context Additional context data
 */
function log_exception($exception, $context = [])
{
    $log_message = '[EXCEPTION] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
    if (!empty($context)) {
        $log_message .= ' | Context: ' . json_encode($context);
    }
    error_log($log_message);
}
