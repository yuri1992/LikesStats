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
class TaskListener extends Database {
	private $fb_id;
	public function __construct($fb_id) {
		$this->fb_id = $fb_id;

	} 
	private function TaskProcessingStatus() {
		// true if the task finished to count all user likes.

		$this->db()->where('FB_ID',$this->fb_id)->where('STATUS','finish');
		if ($this->db()->has('users_requests')) {
			return true;
		}
		return false;
	}
	public function response() {
		return $this->getUserLikesData();
	}
	public function getHtmlTopLikers($data) {
		$res = "<ul>";
		foreach ($data as $liker) {
			$res .= '<li><i class="row-li">
                        <img src="'.$liker['LIKER_PHOTO'].'">
                        <i class="fa fa-thumbs-o-up">

                        	'.$liker['LIKES_COUNT'].'
                        </i>
                        '.$liker['LIKER_NAME'].'
                    </i></li>';
		}
		$res .= "</ul>";
		return $res;
	}
	public function getElementId($element_id) {
		if (stripos($element_id, '_')) {
			return explode("_",$element_id)[1];
		}
		return $element_id;
	}
	public function getEmbbededHref($data,$type) {
		
		return 'https://www.facebook.com/'.$type.'/'.$this->getElementId($data['ELEMENT_ID']);
	
	}
	public function getHtmlMostPoupolarPost($data,$type) {
		#return "<p>".$this->getEmbbededHref($data,$type)."</p>";
		$res = '<div class="fb-post" data-href="'.$this->getEmbbededHref($data,$type).'" data-width="500"></div>';
		return $res;
	}
	public function getTopList($data, $type) {
		$res = array();
		foreach ($data as $element) {
			$res[] = array(
				'html' => $this->getHtmlMostPoupolarPost($element, $type),
				'href' => $this->getEmbbededHref($element, $type),
			);
		}
		return $res;
	}
	public function getUserDb() {
		$res = array();

		$this->db()->where('FB_ID',$this->fb_id);
		$res = $this->db()->getOne('users');	


		//top 10 likers
		$this->db()->where('FB_ID',$this->fb_id);
		$this->db()->groupBy("FB_ID_LIKER");
		$this->db()->orderBy("LIKES_COUNT","DESC");
		$res['top_likers'] = array(
			'html'=>$this->getHtmlTopLikers($this->db()->get('likes_log','10','count(*) as LIKES_COUNT,FB_ID_LIKER,LIKER_NAME,LIKER_PHOTO'))
			);

		//top 10 posts
		$this->db()->where('TYPE','posts')->where('FB_ID',$this->fb_id);
		$this->db()->orderBy("LIKE_COUNT","DESC");
		$data = $this->db()->get('likes',10);
		$res['top_posts'] = $this->getTopList($data,'posts');

		//top 10 posts
		$this->db()->where('TYPE','videos')->where('FB_ID',$this->fb_id);
		$this->db()->orderBy("LIKE_COUNT","DESC");
		$data = $this->db()->get('likes',10);

		$res['top_videos'] = $this->getTopList($data,'posts');
		
		//top 10 posts
		$this->db()->where('TYPE','photos')->where('FB_ID',$this->fb_id);
		$this->db()->orderBy("LIKE_COUNT","DESC");
		$data = $this->db()->get('likes',10);
		$res['top_photos'] = $this->getTopList($data,'posts');

		
		//top 10 elements
		$this->db()->orderBy("LIKE_COUNT","DESC");
		$res['top_elements'] = $this->getTopList($this->db()->get('likes','10'),'posts');


		return $res;
	}
	public function getUserLikesData() {
		//total likes,
		//total photo likes
		if ($this->TaskProcessingStatus()) {
			$res = $this->getUserDb();
			$res['status'] = 'finish';
			return $res;
		} else {
			return $this->stillWaitingForTask();
		}
	}
	private function stillWaitingForTask() {
		$res = array(
				'status' => 'processing'
			);
		return $res;
	}

}

$fb_id = $_GET['fb_id'];

if ($fb_id > 0) {
	$v = new TaskListener($fb_id);

	echo json_encode($v->response());
}

