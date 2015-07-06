<?php
	ini_set('display_errors',1);
	ini_set('display_startup_errors',1);
	error_reporting(-1);
	session_start();

	require 'config.php';
	require 'login_handler.php';


	if ($_GET['publish']) {
		$login->publishWall();
	}
	 
	if ($_GET['destroy']) {
		session_destroy();
		$_SESSION['token'] = NULL;
	}
	// see if we have a session
	// LoginHandler->isLogin()
	if ( $login->isLogin() ) 
	{   
		$fb_id = $login->getUserIdFromSession();

		if ($login->isRegisteredUser()) {
			// user login from facebook and also registered to our system
		} else {
			// user only login from facebook, and not registered in out system
			$login->registerUser();
		}
		$page = 'new_user.php';
	} else {
		$page = 'login.php';
	}

	#$page = 'static_page.php';
?>
<?php require 'header.php'; ?>
<?php

	switch ($page): 
		case 'login.php':
			require 'login.php';
		break;
		case 'request_add.php':
			#will chagne the counting request to waiting...
			$this->makeNewWaitingRequest();
		break; 
		case 'new_user.php':
			require 'new_user.php';
		break;
		case 'static_page.php':
			require 'static_page.php';
		break;
		default:
			#require 'login.php';
		break;
	endswitch;
?>
<?php require 'footer.php'; ?>