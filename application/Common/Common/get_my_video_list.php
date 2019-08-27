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

$uid = @$_POST['uid'];
$page = @$_POST['page'];
if(!$uid){
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
	
	try{
		
		$num = 20;
        $limit = ($page-1)*$num.",".$num;
		$sql = "select * from de_joke_video_posi where uid = ".$uid." order by time desc limit ".$limit;
        $st = $pdo->prepare($sql);
        $st -> execute();
        $result = $st ->fetchAll(PDO::FETCH_ASSOC);
		
		if($result){
			$res = array();
			foreach($result as $key => $val){
				//$val['video_id'];
				@$temp=GetVideoInfo($client, $regionId,$val['video_id']);
                $temp = $temp->Video;
				$temp->uid = $val['uid'];
				$image_info = getimagesize($temp->CoverURL);
				$temp->width = "".$image_info[0];
				$temp->height = "".$image_info[1];
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