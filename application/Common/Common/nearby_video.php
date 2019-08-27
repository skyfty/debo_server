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

$page = @$_POST['page'];
$city = @$_POST['city'];
$lat = @$_POST['lat'];
$lng = @$_POST['lng'];
$uid = @$_POST['uid'];
if(!$city || !$lat || !$lng || !$uid){
	json(-1,'参数错误');
}
if(!$page){
    $page = 1;
}

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");

$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);  
  
$client = new DefaultAcsClient($profile);
  
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
	
	try {
		//$res = GetVideoInfo($client, $regionId,$video_id);
        $num = 20;
        $limit = ($page-1)*$num.",".$num;

        $field = "*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$lat."*PI()/180-lat*PI()/180)/2),2)";
        $field.= "+COS(".$lat."*PI()/180)*COS(lat*PI()/180)*POW(SIN((".$lng."*PI()/180-lng*PI()/180)/2),2)))*1000) AS juli";

        //$where = "ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$lat."*PI()/180-lat*PI()/180)/2),2)";
        //$where.= "+COS(".$lat."*PI()/180)*COS(lat*PI()/180)*POW(SIN((".$lng."*PI()/180-lng*PI()/180)/2),2)))*1000) <= '".$distance."'";

        $sql = "select ".$field." from de_joke_video_posi where city like '%".$city."%' order by juli asc limit ".$limit;
        $st = $pdo->prepare($sql);
        $st -> execute();
        $result = $st ->fetchAll(PDO::FETCH_ASSOC);

        if($result){
            $arr = array();
            foreach($result as $key => $val){
                @$temp=GetVideoInfo($client, $regionId,$val['video_id']);
                $temp = $temp->Video;
                $temp->juli = ($val['juli']/1000)."km";
                $temp->type = "2";
				
				$sql = "select uid,count(*) as num from de_joke_video_upvote where video_id = '".$val['video_id']."' and upvote = 1";
				$st = $pdo->prepare($sql);
				$result = $st -> execute();
				$result = $st->fetch(PDO::FETCH_ASSOC);
				$temp->upvote_num = $result['num'];
				if($uid == $result['uid']){
					$temp->is_my_upvote = "1";
				}else{
					$temp->is_my_upvote = "0";
				}
				
				//查询发布者uid
				$sql = "select * from de_joke_video_posi where video_id = '".$val['video_id']."'";
				$st3 = $pdo->prepare($sql);
				$result3 = $st3 -> execute();
				$result3 = $st3->fetch(PDO::FETCH_ASSOC);
				if($result3){
					$temp->uid = $result3['uid'];
				}else{
					$temp->uid = "0";
					$result3['uid'] = 0;
				}
				
				/*$sql2 = "select * from de_joke_video_follow where uid = ".$uid." and follow_uid = ".$result3['uid'];
				$st2 = $pdo->prepare($sql2);
				$result2 = $st2 -> execute();
				$result2 = $st2->fetch(PDO::FETCH_ASSOC);
				if($result2['follow'] == 1){
					$temp->is_follow = "1";
				}else{
					$temp->is_follow = "0";
				}*/
				
				$sql2 = "select * from de_user where id = ".$result3['uid'];
				$st2 = $pdo->prepare($sql2);
				$result2 = $st2 -> execute();
				$result2 = $st2->fetch(PDO::FETCH_ASSOC);
				if($result2){
					$temp->avatar = $result2['avatar'];
					$temp->user_nickname = $result2['user_nickname'];
				}else{
					$temp->avatar = "";
					$temp->user_nickname = "";
				}
				
				$sql3 = "select count(*) as num from de_joke_video_comment where video_id = '".$val['video_id']."'";
				$st4 = $pdo->prepare($sql3);
				$result4 = $st4 -> execute();
				$result4 = $st4->fetch(PDO::FETCH_ASSOC);
				$temp->comment_num = $result4['num'];

				$image_info = getimagesize($temp->CoverURL);
				$temp->width = "".$image_info[0];
				$temp->height = "".$image_info[1];
				
                $arr[]=$temp;

            }

            json(0,'成功',$arr);
        }else{
            json(-3,'没有更多数据了');
        }

	} catch (Exception $e) {
		json(-2,'请求错误');
	}
?>