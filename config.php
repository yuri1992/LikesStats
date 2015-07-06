<?php
	ini_set('display_errors',1);
	ini_set('display_startup_errors',1);
	error_reporting(-1); 
	define('FACEBOOK_SDK_V4_SRC_DIR', 'facebook-php-sdk-v4-4.0-dev/src/Facebook/');

	require 'facebook-php-sdk-v4-4.0-dev/autoload.php';

	use Facebook\FacebookSession;
	use Facebook\FacebookRedirectLoginHelper;
	use Facebook\FacebookRequest;
	use Facebook\FacebookResponse;
	use Facebook\FacebookSDKException;
	use Facebook\FacebookRequestException;
	use Facebook\FacebookAuthorizationException;
	use Facebook\GraphObject;

	$appid = '1649266495305734';
	$secret = 'c9498587ac9d2a7cee813abbaa8ed7c0';
	FacebookSession::setDefaultApplication('1649266495305734', 'c9498587ac9d2a7cee813abbaa8ed7c0');
	
?>