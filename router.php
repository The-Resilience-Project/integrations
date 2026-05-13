<?php

/**
 * Local dev router for `php -S localhost:8000 -t src/ router.php`
 *
 * Maps clean API Gateway URLs to actual endpoint files,
 * matching the serverless.yml path → handler mappings.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$routes = [
    // API v2 — Schools (mirror serverless.yml httpApi paths)
    '/api/v2/schools/enquiry' => '/api-v2/endpoints/schools/enquiry.php',
    '/api/v2/schools/submit-ca-v2' => '/api-v2/endpoints/schools/submit-ca-v2.php',
    '/api/v2/schools/prize_pack' => '/api-v2/endpoints/schools/prize_pack.php',
    '/api/v2/schools/event_confirmation' => '/api-v2/endpoints/schools/event_confirmation.php',
    '/api/v2/schools/info_session_recording' => '/api-v2/endpoints/schools/info_session_recording.php',

    // API v1
    '/api/enquiry.php' => '/api/enquiry.php',
    '/api/qualify.php' => '/api/qualify.php',
    '/api/register.php' => '/api/register.php',
    '/api/confirm.php' => '/api/confirm.php',
    '/api/lead.php' => '/api/lead.php',
    '/api/accept_dates.php' => '/api/accept_dates.php',
    '/api/order_resources_26.php' => '/api/order_resources_26.php',
    '/api/assess.php' => '/api/assess.php',
];

if (isset($routes[$uri])) {
    require __DIR__ . '/src' . $routes[$uri];
    return true;
}

// Fall back to serving static files
return false;
