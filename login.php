<?php
    if(!$login->isLogin()) {
            $scope = array(
            'scope' => 'user_likes,user_photos,user_status,user_videos,user_posts,publish_actions'
        );  
        $login_path = $login->getLoginUrl($scope);
    } else {
        die();
    }
?>

<div class="continer">
    <div id="login" class='center-screen col-lg-8 col-sm-offset-4 col-sm-10 col-sm-offset-1'>
        <h1 class='text-center'>How Much Likes You Worth ? </h1>
        <div class="fHow Much Likes You Worth ?How Much Likes You Worth ?orm-group">

            <a class="btn btn-block btn-social btn-lg btn-facebook text-center" href="<?php echo $login_path;?>">
                <i class="fa fa-facebook"></i>
                Sign in with Facebook
            </a>
           
        </div>
    </div>
</div>  
        
