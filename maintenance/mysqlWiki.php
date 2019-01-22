#!/usr/local/bin/php
<?php
$domain = isset($argv[1]) ? trim($argv[1]) : null;
define('MEDIAWIKI', true);
define('SETTINGS_ONLY', true);
if (empty($domain)) {
	$settings = dirname(__DIR__, 3)."/LocalSettings.php";
} else {
	$settings = dirname(__DIR__, 3)."/sites/{$domain}/LocalSettings.php";
}
@require($settings);
if (empty($wgDBserver)) {
	exit;
}
if (strpos($wgDBserver, ':') !== false) {
	list($host, $port) = explode(':', $wgDBserver);
	echo "mysql -h {$host} -P {$port} -u {$wgDBuser} -p{$wgDBpassword} {$wgDBname}";
} else {
	echo "mysql -h {$wgDBserver} -u {$wgDBuser} -p{$wgDBpassword} {$wgDBname}";
}