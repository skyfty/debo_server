<?php
include_once 'aliyun-php-sdk-core/Config.php';  
use vod\Request\V20170321 as vod;

$video_id = @$_POST['video_id'];
if(!$video_id){
	json(-1,'参数错误');
}

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");
  
$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);  
  
$client = new DefaultAcsClient($profile);  
  
  
function create_upload_video($client, $regionId) {
	   $request = new vod\CreateUploadVideoRequest();
	   //视频源文件标题(必选)
	   $request->setTitle("test");
	   //视频源文件名称，必须包含扩展名(必选)
	   $request->setFileName("test.mp4");
	   //视频源文件字节数(可选)
	   $request->setFileSize(1024);
	   //视频源文件描述(可选)
	   $request->setDescription("test");
	   //自定义视频封面URL地址(可选)
	   $request->setCoverURL("http://cover.sample.com/sample.jpg");
	   //上传所在区域IP地址(可选)
	   $request->setIP("127.0.0.1");
	   //视频标签，多个用逗号分隔(可选)
	   $request->setTags("mp4");
	   //视频分类ID(可选)
	   $request->setCateId(0);
	   $response = $client->getAcsResponse($request);
	   return $response;
	}
	
	function refresh_upload_video($client, $regionId,$video_id) {
	   $request = new vod\RefreshUploadVideoRequest();
	   //视频ID(必选)
	   $request->setVideoId($video_id);
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
	
	
	$refreshResponse = refresh_upload_video($client, $regionId,$video_id);
	//上传凭证
	$UploadAuth = $refreshResponse->UploadAuth;
	//请求ID
	$RequestId = $refreshResponse->RequestId;

	$res = array(
		'upload_auth' => $UploadAuth,
		'request_id' => $RequestId,
	);
	
	json(0,'成功',$res);
?>