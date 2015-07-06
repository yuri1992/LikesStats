<?php

	require 'database.php';
	use Facebook\FacebookSession;
	use Facebook\FacebookRedirectLoginHelper;
	use Facebook\FacebookRequest;
	use Facebook\FacebookResponse;
	use Facebook\FacebookSDKException;
	use Facebook\FacebookRequestException;
	use Facebook\FacebookAuthorizationException;
	use Facebook\GraphObject;

	class LoginHandler extends Database {
		private $_login_status;
		private $_session;
		private $_user;
		function __construct() {
			$this->_user = NULL;
			$this->_login_status = false;
			if ($this->_loginFromRedirect()) {
				$this->_login_status = true;
				$this->checkIfUserExsits();
			} else if ($this->_loginFromExsitsSession()) {
				$this->_login_status = true;
				$this->checkIfUserExsits();
			}

		}
		public function _loginFromRedirect() {
			
			$url_redirect = "http://howmuchlikesyouworth.com/";
			$helper = new FacebookRedirectLoginHelper($url_redirect);
			try
			{
			  // In case it comes from a redirect login helper
			  $session = $helper->getSessionFromRedirect();
			  	if ($session) {
					$_SESSION['token'] = $session->getToken();
					$this->_session = $session;
					$this->updateAccessToken();					
					return true;
			  	}
			} 
			catch( FacebookRequestException $ex ) 
			{
			  // When Facebook returns an error
				#echo $ex;
				$this->_session =NULL;
			} 
			catch( Exception $ex ) 
			{
			  // When validation fails or other local issues
				#echo $ex;
				$this->_session =NULL;
			}
			return false;
		}
		public function publishWall() {
			try {
				$request = new FacebookRequest(
				  $this->_session,
				  'POST',
				  '/me/feed',
				  array(
				  	'message' => 'Try 1'
				  )
				);
				$response = $request->execute();
				$graphObject = $response->getGraphObject();
				print_r($graphObject);	
			} catch( Exception $ex ) {
				// When validation fails or other local issues
				#$this->_logErrors('Error While Getting : '.$url);
			  	print_r($ex);
			  	return false;
			}
		}
		public function _loginFromExsitsSession() {
			// see if we have a session in $_Session[]
			if( isset($_SESSION['token']))
			{
			    // We have a token, is it valid? 
			    $session = new FacebookSession($_SESSION['token']); 
			    try
			    {
			    	$appid = '1649266495305734';
					$secret = 'c9498587ac9d2a7cee813abbaa8ed7c0';
			        $session->Validate($appid ,$secret);
			        $this->_session = $session;
			  		return true;
			    }
			    catch( FacebookAuthorizationException $ex)
			    {
			        // Session is not valid any more, get a new one.
			        $this->_session =NULL;
			        $_SESSION['token'] = NULL;
			    }
			}
			return false;
		}
		private function checkIfUserExsits() {
			$user_id = $this->getUserIdFromSession();
			$this->db()->where('FB_ID',$user_id);

			if($this->db()->has('users')) {
			    $this->_user = $this->db()->getOne('users');
			    return true;
			}
		    return false;
		}
		private function updateAccessToken() {
			if ($this->isRegisteredUser()) {
				$expireDate = $this->getSessionInfo()->getExpiresAt()->format('Y-m-d H:i:s');
				$user_id = $this->getUserIdFromSession();
			    $data_user = array(
					'VALIDATION_DATE' => $expireDate,
			    	'ACCESS_TOKEN' => $this->getSession()->getToken()
				);
			    $db->where('FB_ID',$user_id);
			    $db->update('users',$data_user);
			}
		}
		public function makeNewWaitingRequest() {
			$this->db()->where('FB_ID',$this->getUserIdFromSession());
			$this->db()->update('users_requests',array(
					'STATUS' => 'waiting'
				));
			return true;
		}
		public function registerUser() {
			if (!$this->isRegisteredUser() && $this->isLogin()) {

				$user_id = $this->getUserIdFromSession();
			   	$expireDate = $this->getSessionInfo()->getExpiresAt()->format('Y-m-d H:i:s');
			   	
			    $data_user = array(
					'FB_ID'=>$user_id,
					'VALIDATION_DATE' => $expireDate,
			    	'ACCESS_TOKEN' => $this->getSession()->getToken()
				);
			    $id = $this->db()->insert('users',$data_user);
			    
			    $data_request = array(
			    	'STATUS' => 'waiting',
			    	'FB_ID' => $user_id
			    );	

			    $id_request = $this->db()->insert('users_requests',$data_request);
			    return true;
			}
			return false;
		}
		public function isRegisteredUser() {
			// return whatever the user is register to our DB
			if (isset($this->_user))
				return true;

			if ($this->isLogin()) {
				$user_id = $this->getUserIdFromSession();
				if ($this->checkIfUserExsits())
					return true;
			}
			return false;
		}
		public function isLogin() {
			// return status of login with facebook
			if ($this->_login_status)
				return true;
			return false;
		}
		public function getSession() {
			if ($this->isLogin())
				return $this->_session;
			return false;
		}
		public function getUserIdFromSession() {
			if ($this->isLogin()) {
				$info = $this->_session->getSessionInfo();
				return $info->getId();
			}
			return false;
		}
		public function getUserIdFromGraph() {
			try {
				$request = new FacebookRequest(
				  $this->_session,
				  'GET',
				  '/me'
				);
				$response = $request->execute();
				$graphObject = $response->getGraphObject();
				return $graphObject->getProperty('id');	
			} catch( Exception $ex ) {
				// When validation fails or other local issues
				#$this->_logErrors('Error While Getting : '.$url);
			  	return false;
			}
		}
		public function getScopeFromSession() {
			if ($this->isLogin()) {
				$info = $this->_session->getSessionInfo();
				return $info->getScopes();
			}
			return false;
		}
		public function getSessionInfo() {
			if ($this->isLogin())
				return $this->_session->getSessionInfo();
			return false;
		}
		public function getLoginUrl($scope) {
			$url_redirect = "http://howmuchlikesyouworth.com/";
			$helper = new FacebookRedirectLoginHelper($url_redirect);
			return $helper->getLoginUrl($scope);
		}
	}

	$login = new LoginHandler();
