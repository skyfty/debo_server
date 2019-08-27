<?php
include_once 'aliyun-php-sdk-core/Config.php';  
use vod\Request\V20170321 as vod;

$dsn = "mysql:host=localhost;dbname=debo";
$username = "debo";
$password = "hkeFbw#kY";

try{
	$pdo = new PDO($dsn,$username,$password);
}catch(PDOException $e){
	json(-2,'数据库连接失败');
	exit;
}


$video_id = @$_POST['video_id'];
$uid = @$_POST['uid'];
$follow_uid = @$_POST['follow_uid'];
if(!$video_id || !$uid || !$follow_uid){
	json(-1,'参数错误');
}

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");
  
$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);  
  
$client = new DefaultAcsClient($profile);  
  
	function testGetVideoPlayAuth($client, $regionId,$video_id) {
	   $request = new vod\GetVideoPlayAuthRequest();
	   $request->setAcceptFormat('JSON');
	   $request->setRegionId($regionId);
	   $request->setVideoId($video_id);            //视频ID
	   $response = $client->getAcsResponse($request);
	   return $response;
	}

	
	function json($code,$message='',$data = array())
    {
        if(!is_numeric($code))
        {
            return "";
        }
        $result = array(
            'code' => $code,
            'message' => $message,
            'data' => $data
        );

        echo json_encode($result);
        exit;
    }
	
	try {
		$res = testGetVideoPlayAuth($client, $regionId,$video_id);
		$sql2 = "select * from de_joke_video_follow where uid = ".$uid." and follow_uid = ".$follow_uid;
		$st2 = $pdo->prepare($sql2);
		$result2 = $st2 -> execute();
		$result2 = $st2->fetch(PDO::FETCH_ASSOC);
		if($result2['follow'] == 1){
			$res->is_follow = "1";
		}else{
			$res->is_follow = "0";
		}
		
		$sql3 = "select count(*) as num from de_joke_video_comment where video_id = '".$video_id."'";
		$st4 = $pdo->prepare($sql3);
		$result4 = $st4 -> execute();
		$result4 = $st4->fetch(PDO::FETCH_ASSOC);
		$res->comment_num = $result4['num'];
		
		$sql = "select uid,count(*) as num from de_joke_video_upvote where video_id = '".$video_id."' and upvote = 1";
		$st = $pdo->prepare($sql);
		$result = $st -> execute();
		$result = $st->fetch(PDO::FETCH_ASSOC);
		$res->upvote_num = $result['num'];
		if($uid == $result['uid']){
			$res->is_my_upvote = "1";
		}else{
			$res->is_my_upvote = "0";
		}
		
		$sql4 = "select * from de_user where id = ".$follow_uid;
		$st4 = $pdo->prepare($sql4);
		$result4 = $st4 -> execute();
		$result4 = $st4->fetch(PDO::FETCH_ASSOC);
		if($result4){
			$res->avatar = $result4['avatar'];
		}else{
			$res->avatar = "";
		}
		
		//修改播放次数
		$sql9 = "update de_joke_video_posi set play_num=play_num+1 where video_id = '".$video_id."'";
		$st9 = $pdo->prepare($sql9);
		$result9 = $st9 -> execute();
		
		$sql10 = "select * from de_joke_video_posi where video_id = '".$video_id."'";
		$st10 = $pdo->prepare($sql10);
		$result10 = $st10 -> execute();
		$result10 = $st10->fetch(PDO::FETCH_ASSOC);
		$res->play_num = $result10['play_num'];
		
		json(0,'成功',$res);
	} catch (Exception $e) {
		json(-2,'请求错误');
	}
	
	/*$createResponse = create_upload_video($client, $regionId);
	//上传凭证
	//$createResponse = create_upload_video($client, $regionId);
	//上传凭证
	$UploadAuth = $createResponse->UploadAuth;
	//上传地址
	$UploadAddress = $createResponse->UploadAddress;
	//视频ID
	$VideoId = $createResponse->VideoId;
	//请求ID
	$RequestId = $createResponse->RequestId;

	$res = array(
		'upload_auth' => $UploadAuth,
		'upload_address' => $UploadAddress,
		'video_id' => $VideoId,
		'request_id' => $RequestId,
	);
	
	json(0,'成功',$res);*/
?>