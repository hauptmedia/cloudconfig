<?php
require_once('../vendor/autoload.php');

function exception_error_handler($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
}

set_error_handler("exception_error_handler");

switch($_SERVER['REDIRECT_URL']) {
	case '/install.sh':
		require_once('install.sh.php');	
	break;

	case '/cloud-config.yml':
		require_once('cloud-config.yml.php');
	break;

	default:
	header('HTTP/1.1 500 Internal Server Error');
	break;
}

