<?php
namespace Common\Common;

include_once 'aliyun-php-sdk-core/Config.php';
use vod\Request\V20170321 as vod;

class Alivideo{
	private $regionId = 'cn-shanghai';
	private $access_key_id = 'LTAIqDHaGdKLblTM';
	private $access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
	
	public function __construct(){
		include_once 'aliyun-php-sdk-core/Config.php';
		$profile = DefaultProfile::getProfile($this->regionId, $this->access_key_id, $this->access_key_secret);
		$client = new DefaultAcsClient($profile);
	}

	
	function create_upload_video($client, $regionId) {
	   $request = new vod\CreateUploadVideoRequest();
	   //视频源文件标题(必选)
	   $request->setTitle("视频标题");
	   //视频源文件名称，必须包含扩展名(必选)
	   $request->setFileName("文件名称.mov");
	   //视频源文件字节数(可选)
	   $request->setFileSize(0);
	   //视频源文件描述(可选)
	   $request->setDescription("视频描述");
	   //自定义视频封面URL地址(可选)
	   $request->setCoverURL("http://cover.sample.com/sample.jpg");
	   //上传所在区域IP地址(可选)
	   $request->setIP("127.0.0.1");
	   //视频标签，多个用逗号分隔(可选)
	   $request->setTags("标签1,标签2");
	   //视频分类ID(可选)
	   $request->setCateId(0);
	   $response = $client->getAcsResponse($request);
	   return $response;
	}
	
	function refresh_upload_video($client, $regionId) {
	   $request = new vod\RefreshUploadVideoRequest();
	   //视频ID(必选)
	   $request->setVideoId("视频ID");
	   $response = $client->getAcsResponse($request);
	   return $response;
	}
	
	function test(){
		$createResponse = create_upload_video($client, $regionId);
		//上传凭证
		echo "UploadAuth=".$createResponse->UploadAuth."\n";
		//上传地址
		echo "UploadAddress=".$createResponse->UploadAddress."\n";
		//视频ID
		echo "VideoId=".$createResponse->VideoId."\n";
		//请求ID
		echo "RequestId=".$createResponse->RequestId."\n";
		$refreshResponse = refresh_upload_video($client, $regionId);
		//视频ID
		echo "VideoId=".$refreshResponse->VideoId."\n";
		//请求ID
		echo "RequestId=".$refreshResponse->RequestId."\n";
	}
	
}
?>