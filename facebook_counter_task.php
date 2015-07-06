<?php
require 'config.php';
require 'database.php';
	use Facebook\FacebookSession;
	use Facebook\FacebookRedirectLoginHelper;
	use Facebook\FacebookRequest;
	use Facebook\FacebookResponse;
	use Facebook\FacebookSDKException;
	use Facebook\FacebookRequestException;
	use Facebook\FacebookAuthorizationException;
	use Facebook\GraphObject;
class FacebookCounter extends Database {
	function _debug() {
		echo "start Debuging";
		$this->db()->where('FB_ID','10205447047469248');
		$this->db()->update('users_requests',array('status'=>'waiting'));
	}
	function _getWaitingUsers($limit=2) {
		$this->db()->join("users a_t", "a_t.FB_ID=u_r.FB_ID", "LEFT");
		$this->db()->where('STATUS','waiting');
		$res = $this->db()->get('users_requests u_r',$limit);
		return $res;
	}
	function _markWaitingUsers($arr_list) {
		$arr = array();
		foreach ($arr_list as $u) {
			array_push($arr ,$u['ID']);
		}

		$this->db()->where('ID', $arr, 'IN');
		$this->db()->update('users_requests',array('status'=>'waitingforexcute'));

	}
	function _setRequestWaiting($user_id) {
		if (strlen($user_id) > 0 ) {
			$this->db()->where('FB_ID', $user_id);
			$this->db()->update('users_requests',array('status'=>'waiting'));
		}
	}
	function _deletePreviousData($user_id) {
		if (strlen($user_id) > 0 ) {
			//delete all information from DB
			$this->db()->where('FB_ID',$user_id);
			$this->db()->delete('likes');

			$this->db()->where('FB_ID',$user_id);
			$this->db()->delete('likes_log');
		}
	}
	function _markUserInProccesing($user_id) {
		$this->_log("deleting previous data:".$user_id);
		$this->db()->where('FB_ID',$user_id);
		$this->db()->update('users_requests',array('status'=>'excuting'));
		$this->_deletePreviousData($user_id);
	}
	function insertElement($element_id,$type,$user_id,$likes) {
		/*
			insering element(photo,video,posts) 
		*/
	    $likes = array(
	    	'ELEMENT_ID' => $element_id,
	    	'TYPE' => $type,
	    	'FB_ID' => $user_id,
	    	'LIKE_COUNT' => $likes
    	);
	    $id = $this->db()->insert('likes',$likes);

	}
	function insertLikers($user_id,$type,$element_id,$likers) {
		foreach ($likers as $like) {
			$fb_id_liker = $like->id;
			$liker_name = $like->name;
			$liker_photo = $like->pic_square;
			
			$likes = array(
		    	'FB_ID_LIKER' => $fb_id_liker,
		    	'ELEMENT_ID' => $element_id,
		    	'TYPE' => $type,
		    	'FB_ID' => $user_id,
		    	'LIKER_NAME' => $liker_name,
		    	'LIKER_PHOTO' => $liker_photo,
	    	);
		    $id = $this->db()->insert('likes_log',$likes);
		}
	}
	function updateUser($like,$user_id) {
		$this->db()->where('FB_ID',$user_id);
		$this->db()->update('users',
							array(
								'LIKE_COUNT'=>$like['count'],
								'VIDEOS_COUNT'=>$like['videos'],
								'POSTS_COUNT'=>$like['posts'],
								'PHOTOS_COUNT'=>$like['photos']
								)
							);

		$this->db()->where('FB_ID',$user_id);
		$this->db()->update('users_requests',array('status'=>'finish'));
	}
	function _requestData($session,$url='/me/') {
		$url = str_replace('https://graph.facebook.com/v2.3', '', $url);
		echo "$url<BR>";
		try {
			$request = new FacebookRequest(
			  $session,
			  'GET',
			  $url
			);
			$response = $request->execute();
			$graphObject = $response->getGraphObject();
			echo "<BR>done";
			return $graphObject;	
		} catch( Exception $ex ) {
		  // When validation fails or other local issues
			$this->_logErrors('Error While Getting : '.$url);
		  return false;
		}

	}
	function _requestDataRecursive($session, $data) {
		
		$data = $data->asArray();
		if (isset($data['paging']) && property_exists($data['paging'],'next')) {	
			$url = $data['paging']->next;
			$res = $this->_requestDataRecursive(
										$session,
										$this->_requestData($session,urldecode($url))
									)['data'];
			if (is_array($res) && isset($data['data']))
				$data['data'] = array_merge($data['data'],$res);
		}
 		
		return $data;
	}
	function _getUserData($user, $session) {
		/*
			making call to Facebook Graph API,
			Fetch Videos,Photos,Posts
			couting all likes that user recicve,
			loging all object likes(photos,videos,posts)
			loging all likers (pepole how liked)
		*/
	    $graphArr = Array();
	    $this->_markUserInProccesing($user['FB_ID']);
    	$data = $this->_requestData($session,
    		'/'.$user['FB_ID'].'/?fields=photos.limit(500){likes.summary(true){pic_square,link,name},privacy},
    		videos.limit(500){likes.summary(true){pic_square,link,name},privacy},
    		posts.limit(500){likes.summary(true){pic_square,link,name},privacy}');

    	if (!$data) {
    		$this->_errorAccessTokenValidation($user,"error while making initial request");
    	}

    	$types = ['videos','photos','posts'];
    	$like = array();
    	$count_all = 0;
		foreach ($types as $type) {
			if ($data->getProperty($type)) {
				$type_count = 0;
				$r = $this->_requestDataRecursive($session,$data->getProperty($type));
				$type_count = $this->_getLikes($r, $type, $user['FB_ID']);
				$count_all += $type_count;
				$like[$type] = $type_count;
			}
		}
		$like['count'] = $count_all;
		$this->updateUser($like,$user['FB_ID']);
	}
	function _getLikes($graphObject, $type, $user_id) {
		/*
			inserting data to db:
				1.insert every element to db + like number
				2.counting all likes for express view
		*/
		$count = 0;
		foreach ($graphObject['data'] as $object) {
			$count += $object->likes->summary->total_count;
			$this->insertElement(
				$object->id,
				$type, 
				$user_id,
				$object->likes->summary->total_count
			);

			$this->insertLikers(
				$user_id, 
				$type,
				$object->id,
				$object->likes->data
			);
		}
		return $count;
	}
	function _errorAccessTokenValidation($user, $error) {
		$this->_deletePreviousData($user['FB_ID']);
		$this->_setRequestWaiting($user['FB_ID']);
		$this->_logErrors("error accord ".$error);
	}
	function _logErrors($msg) {
		$arr = array(
				'LOG_MSG' => $msg,
				'TYPE' => 'ERROR'
		);
		$this->db()->insert('log',$arr);
	}
	function _log($msg) {
		$arr = array(
				'LOG_MSG' =>  $msg,
				'TYPE' =>  'regular'
		);
		$this->db()->insert('log',$arr);
	}
	function getFBUserLikes($user) {
		/*
			get user object contains:
				ACCESS_TOKEN, check if access_token still valid
			we will fetch users_video,users_posts,users_photos
		*/

		$today_dt = new DateTime(date("Y-m-d H:i:s"));
		$expire_dt = new DateTime($user['VALIDATION_DATE']);
		if ($today_dt < $expire_dt) {
			echo $user['ACCESS_TOKEN'];
			$session = new FacebookSession($user['ACCESS_TOKEN']); 
		    try
		    {
	    		$appid = '1649266495305734';
				$secret = 'c9498587ac9d2a7cee813abbaa8ed7c0';
		        $session->Validate($appid ,$secret);
		        $this->_log("starting fetch data for user:".$user['FB_ID']);
		        $this->_getUserData($user,$session);
		    }
		    catch( FacebookAuthorizationException $ex)
		    {
		        // Session is not valid any more, get a new one.
		        $this->_errorAccessTokenValidation($user,$ex);
		    }
		    catch(Exception $e) {
			  $this->_errorAccessTokenValidation($user,$e);
			}
		} else {
			$this->_errorAccessTokenValidation($user,'access_token_expired');
		}
	}
	function excute() {
		$user_to_excute = $this->_getWaitingUsers();
		if ($user_to_excute) {
			$this->_markWaitingUsers($user_to_excute);
			foreach($user_to_excute as $user) {
				$this->getFBUserLikes($user);
			}
			$this->_log("Finished Task:".implode(",", array_values($user_to_excute)));
		}
	}
}

$task = new FacebookCounter();
if ($_GET['debug']==1) {
	$task->_debug();
}
$task->excute();


