<?php
include_once 'aliyun-php-sdk-core/Config.php';  
include_once 'aliyun-php-sdk-core/RpcAcsRequest.php';  
include_once 'aliyun-php-sdk-core/Auth/ShaHmac1Signer.php';  
include_once 'aliyun-php-sdk-core/Auth/Credential.php';  
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

$page = @$_POST['page'];
$uid = @$_POST['uid'];
if(!$uid){
    json(-1,'参数错误');
}

if(!$page){
	$page = 1;
}

function GetVideoInfo($client,$regionId,$video_id) {
   $request = new vod\GetVideoInfoRequest();
   $request->setAcceptFormat('JSON');
   $request->setRegionId($regionId);
   $request->setVideoId($video_id);            //视频ID
   $request->setActionName('GetVideoInfo');
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
  
$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");

$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);  
  
$client = new DefaultAcsClient($profile);

try{
	
	$num = 20;
	$limit = ($page-1)*$num.",".$num;
	$sql = "select * from de_joke_video_posi ORDER BY RAND() limit ".$limit;
	$st = $pdo->prepare($sql);
	$st -> execute();
	$result = $st ->fetchAll(PDO::FETCH_ASSOC);
	
	if($result){
		$uids_arr = array();
		foreach($result as $kk => $vv){
			$uids_arr[]=$vv['uid'];
		}
		$uids_arr = array_unique($uids_arr);
		$uids_str = implode(",",$uids_arr);
		$find_user_info_sql = "select id,user_nickname,avatar from de_user where id in (".$uids_str.")";
		$st_user = $pdo->prepare($find_user_info_sql);
		$res_user_info = $st_user -> execute();
		$res_user_info = $st_user->fetchALL(PDO::FETCH_ASSOC);
		
		$res = array();
		foreach($result as $key => $val){
			//$val['video_id'];
//			@$temp=GetVideoInfo($client, $regionId,$val['video_id']);
//			$temp = $temp->Video;
            $temp = $val;
            $temp['VideoId'] = $val['video_id'];
			$temp['uid'] = $val['uid'];
//			$image_info = getimagesize($temp->CoverURL);
//			$temp->width = "".$image_info[0];
//			$temp->height = "".$image_info[1];
			
			$temp['type'] = "1";
			
			$sql2 = "select uid,count(*) as num from de_joke_video_upvote where video_id = '".$val['video_id']."' and upvote = 1";
			$st2 = $pdo->prepare($sql2);
			$result2 = $st2 -> execute();
			$result2 = $st2->fetch(PDO::FETCH_ASSOC);
			$temp['upvote_num'] = $result2['num'];
			if($uid == $result2['uid']){
				$temp['is_my_upvote'] = "1";
			}else{
				$temp['is_my_upvote'] = "0";
			}
			
			$sql3 = "select count(*) as num from de_joke_video_comment where video_id = '".$val['video_id']."'";
			$st4 = $pdo->prepare($sql3);
			$result4 = $st4 -> execute();
			$result4 = $st4->fetch(PDO::FETCH_ASSOC);
			$temp['comment_num'] = $result4['num'];
			
			foreach($res_user_info as $u_k => $u_v){
				if($val['uid'] == $u_v['id']){
					$temp['avatar'] = $u_v['avatar'];
					$temp['user_nickname'] = $u_v['user_nickname'];
				}
			}
			
			if(empty($temp['avatar'])){
                $temp['avatar'] = "";
			}
			if(empty($temp['user_nickname'])){
                $temp['user_nickname'] = "";
			}
			
			$res[]=$temp;
		}
		
		json(0,'成功',$res);
	}else{
		json(-3,'没有更多数据了');
	}
	
} catch (Exception $e) {
	json(-2,'请求错误,请重试');
}

?>