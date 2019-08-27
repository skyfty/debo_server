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

$title = @$_POST['title'];
$filename = @$_POST['filename'];
$coverurl = @$_POST['coverurl'];
$describe = @$_POST['describe'];
$lat = @$_POST['lat'];
$lng = @$_POST['lng'];
$uid = @$_POST['uid'];
$city = @$_POST['city'];
if(!$title || !$filename || !$lat || !$lng || !$uid || !$city){
	json(-1,'参数错误');
}
if(!$coverurl){
	$coverurl = "";
}
if(!$describe){
	$describe = "";
}

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");
  
$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);  
  
$client = new DefaultAcsClient($profile);  
  
  
function create_upload_video($client, $regionId,$title,$filename,$coverurl,$describe) {
	
	   $request = new vod\CreateUploadVideoRequest();
	   //视频源文件标题(必选)
	   $request->setTitle($title);
	   //视频源文件名称，必须包含扩展名(必选)
	   $request->setFileName($filename);
	   //视频源文件字节数(可选)
	   $request->setFileSize(1024);
	   //视频源文件描述(可选)
	   $request->setDescription($describe);
	   //自定义视频封面URL地址(可选)
	   $request->setCoverURL($coverurl);
	   //上传所在区域IP地址(可选)
	   $request->setIP("127.0.0.1");
	   //视频标签，多个用逗号分隔(可选)
	   $request->setTags("db");
	   //视频分类ID(可选)
	   $request->setCateId(0);
	   $response = $client->getAcsResponse($request);
	   return $response;
	}
	
	function refresh_upload_video($client, $regionId) {
	   $request = new vod\RefreshUploadVideoRequest();
	   //视频ID(必选)
	   $request->setVideoId("1");
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
	
	
	$createResponse = create_upload_video($client, $regionId,$title,$filename,$coverurl,$describe);
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
	
	//存储视频信息
	/*try{
		$sql = "insert into de_joke_video_posi value ('','".$VideoId."','".$lat."','".$lng."','".time()."','".$uid."','".$city."')";
		$st = $pdo->prepare($sql);
		$result = $st -> execute();
		if(!$result){
			json(-3,'数据获取失败');
		}
	}catch(PDOException $e){
		json(-3,'数据获取失败');
	}*/
	
	json(0,'成功',$res);
?>