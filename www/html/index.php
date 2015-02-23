<?php

switch($_SERVER['REQUEST_URI']) {
	case '/install.sh':
		require_once('install.sh.php');	
	break;

	default:
	header('HTTP/1.1 500 Internal Server Error');
	break;
}

