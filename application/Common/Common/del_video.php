<?php
include_once 'aliyun-php-sdk-core/Config.php';  
use vod\Request\V20170321 as vod;

$dsn = "mysql:host=123.57.148.47;dbname=debo";
$username = "root";
$password = "Qcwlsl2017";
try{
    $pdo = new PDO($dsn,$username,$password);
}catch(PDOException $e){
    json(-2,'数据库连接失败');
    exit;
}

$video_id = @$_GET['video_id'];//最多10个视频
if(!$video_id){
	json(-1,'参数错误');
}

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");

$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);  
  
$client = new DefaultAcsClient($profile);

	function GetVideoPlayInfo($client, $regionId,$video_id) {
		$request = new vod\GetVideoPlayInfoRequest();
		$request->setAcceptFormat('JSON');
		$request->setVideoId($video_id);            //视频ID
		$request->getRegionId($regionId);
		$request->setActionName('GetPlayInfo');
		$response = $client->getAcsResponse($request);
		return $response;
	}

    function DeleteStream($client, $regionId,$video_id,$JobIds) {
        $request = new vod\DeleteStreamRequest();
        $request->setAcceptFormat('JSON');
        $request->setVideoId($video_id);            //视频ID
        $request->getRegionId($regionId);
        $request->setActionName('DeleteStream');
        $request->setJobIds($JobIds);
        $response = $client->getAcsResponse($request);
        return $response;
    }

    function DeleteVideo($client, $regionId,$video_id) {
        $request = new vod\DeleteVideoRequest();
        $request->setAcceptFormat('JSON');
        $request->getRegionId($regionId);
        $request->setActionName('DeleteVideo');
        $request->setVideoIds($video_id);
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
		
		@$temp=GetVideoPlayInfo($client, $regionId,$video_id);
		$temp=$temp->PlayInfoList;
		$temp=$temp->PlayInfo;
		$JobIds = "";
		foreach($temp as $key => $val){
            $JobIds.=$temp[$key]->JobId.",";
		}

        //@$del_stream=DeleteStream($client, $regionId,$video_id,$JobIds);
        //@$del_video=DeleteVideo($client, $regionId,$video_id);
		
		json(0,'成功',$temp);

	} catch (Exception $e) {
		json(-2,'请求错误,请重试');
	}


?>