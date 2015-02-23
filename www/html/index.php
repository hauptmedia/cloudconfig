<?php

switch($_SERVER['REDIRECT_URL']) {
	case '/install.sh':
		require_once('install.sh.php');	
	break;

	case '/cloud-config.yml':
		echo "Hello world";
	break;

	default:
	header('HTTP/1.1 500 Internal Server Error');
	break;
}

