<?php
/**
 * Curse Inc.
 * Dynamic Settings
 * Dynamic Settings Host Helper - Switches HTTP_HOST to target a wiki.
 *
 * @author 		Alex Smith
 * @copyright	(c) 2014 Curse Inc.
 * @license		All Rights Reserved
 * @package		Dynamic Settings
 * @link		http://www.curse.com/
 *
**/

umask(002);

if (PHP_SAPI != 'cli') {
	exit;
}

if (!isset($argv[1])) {
	echo "Please specify a domain.\n";
	exit;
}
if (!isset($argv[2])) {
	echo "Please specify a script.\n";
	exit;
}

$host	= $argv[1];
$script	= $argv[2];

if (strpos($host, '.com') == strlen($host) - 4 && $_SERVER['PHP_ENV'] === 'development') {
	echo "Did you mean to use '.com' while in development?\n";
	exit;
}
if (strpos($host, '.local') == strlen($host) - 6 && $_SERVER['PHP_ENV'] === 'live') {
	echo "Did you mean to use '.local' while in live?\n";
	exit;
}

$found = false;
if (file_exists(dirname(__DIR__, 3).'/sites/'.$host.'/LocalSettings.php')) {
	$found = true;
}

if (!$found) {
	if (!getenv('SILENT')) {
		fwrite(STDERR, "The host \"${host}\" was not found or is not cached out to disk!\n");
	}
	exit(2); // So other scripts can know the error happened.
}

$_SERVER['HTTP_HOST'] = $host;

//Get rid of extra arguments.
array_shift($argv);
array_shift($argv);

require($script);
