<?php

require dirname(__DIR__).'/vendor/autoload.php';

// Stub logging functions so controller code can call them without init.php
if (!function_exists('log_info')) {
    function log_info($message, $context = [])
    {
    }
}
if (!function_exists('log_debug')) {
    function log_debug($message, $context = [])
    {
    }
}
if (!function_exists('log_warning')) {
    function log_warning($message, $context = [])
    {
    }
}
if (!function_exists('log_error')) {
    function log_error($message, $context = [])
    {
    }
}
if (!function_exists('log_exception')) {
    function log_exception($e, $context = [])
    {
    }
}

// Load value objects, domain services, and controller classes
require dirname(__DIR__).'/src/api/classes/requests/ContactInfo.php';
require dirname(__DIR__).'/src/api/classes/requests/EnquiryRequest.php';
require dirname(__DIR__).'/src/api/classes/controllers/base.php';
require dirname(__DIR__).'/src/api/classes/traits/enquiry.php';
require dirname(__DIR__).'/src/api/classes/traits/confirmation.php';
require dirname(__DIR__).'/src/api/classes/traits/lead.php';
require dirname(__DIR__).'/src/api/classes/traits/registration.php';
require dirname(__DIR__).'/src/api/classes/traits/order_resources_26.php';
require dirname(__DIR__).'/src/api/classes/traits/accept_dates.php';
require dirname(__DIR__).'/src/api/classes/traits/assess.php';
require dirname(__DIR__).'/src/api/classes/traits/qualify.php';
require dirname(__DIR__).'/src/api/classes/traits/calendly_prospect.php';
require dirname(__DIR__).'/src/api/classes/controllers/school.php';
require dirname(__DIR__).'/src/api/classes/controllers/workplace.php';
require dirname(__DIR__).'/src/api/classes/controllers/early_years.php';
require dirname(__DIR__).'/src/api/classes/controllers/general.php';

// Global helper functions
require dirname(__DIR__).'/src/functions.php';
