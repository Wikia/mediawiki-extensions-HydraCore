#!/usr/local/bin/php
<?php
$domain = isset($argv[1]) ? trim($argv[1]) : null;
// Load Enough of MW Environment to make sure settings are all initialized.
$IP = dirname(__DIR__, 3);
define('MEDIAWIKI', true);
define('SETTINGS_ONLY', true);
require_once $IP . "/vendor/autoload.php";
require_once $IP . "/includes/AutoLoader.php";
require_once $IP . "/includes/Defines.php";

// Determine which settings to load
if (empty($domain)) {
	$settings = $IP . "/LocalSettings.php";
} else {
	$settings = $IP . "/sites/{$domain}/LocalSettings.php";
}

// Connect to a Database from extracted settings
@require $settings;
if (empty($wgDBserver)) {
	exit;
}
if (strpos($wgDBserver, ':') !== false) {
	list($host, $port) = explode(':', $wgDBserver);
	echo "mysql -h {$host} -P {$port} -u {$wgDBuser} -p{$wgDBpassword} {$wgDBname}";
} else {
	echo "mysql -h {$wgDBserver} -u {$wgDBuser} -p{$wgDBpassword} {$wgDBname}";
}
