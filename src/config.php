<?php

// / sandbox /
$vtod_config['url'] = 'https://theresilienceproject.od2.vtiger.com/';
$vtod_config['username'] = 'maddie@theresilienceproject.com.au';
$vtod_config['accesskey'] = 'EKCC5OlQjHZjoOMh';
// Vtiger timeout must be less than Lambda timeout (29s) to avoid API Gateway timeouts
// HTTP API Gateway has a hard limit of 30 seconds, so we use 25s for Vtiger + 4s overhead
$vtod_config['timeout'] = 25;

//$default_assigned_to = "19x1";
$debug = true;
$vtod_config['debug'] = true;

// server mail
$mail_config['mail_server'] = 'ssl://trpstaging.dev';
$mail_config['mail_server_username'] = '_mainaccount@trpstaging.dev';
$mail_config['mail_server_password'] = 'CrOOwl*e3XFy9MN9';
$mail_config['mail_from'] = 'bookings@theresilienceproject.com.au';

//MYSQL config
$local_config['dbtype'] = 'mysql';
$local_config['dbhost'] = 'localhost';
$local_config['dbport'] = '3306';
$local_config['dbname'] = 'trpstaging_resilience';
$local_config['dbuser'] = 'trpstaging_resilience';
$local_config['dbpass'] = 'bWaLy]7jgv1$';

//VT9
$vt9_config['url'] = 'https://theresilienceproject.od2.vtiger.com/';
$vt9_config['username'] = 'maddie@theresilienceproject.com.au';
$vt9_config['accesskey'] = 'EKCC5OlQjHZjoOMh';
// Vtiger timeout must be less than Lambda timeout (29s)
$vt9_config['timeout'] = 25;
