<?php
include_once 'aliyun-php-sdk-core/Config.php';  
use vod\Request\V20170321 as vod;

$video_id = @$_POST['video_id'];
if(!$video_id){
	json(-1,'error');
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
	
	$res = GetVideoPlayInfo($client, $regionId,$video_id);
    $cover_url = $res->VideoBase;
    $cover_url = $cover_url->CoverURL;
    $res = $res->PlayInfoList;
    $res = $res->PlayInfo;
    foreach($res as $key => $val){
        $video_type = $res[$key]->Definition;
        if($video_type == 'OD'){
            $result = $res[$key];
        }else{
            if($video_type == 'LD'){
                $result = $res[$key];
            }else{
                if($video_type == 'FD'){
                    $result = $res[$key];
                }
            }
        }
    }
	
	json(0,'success',$result);
	
}catch(Exception $e){
	json(-2,'error');
}











?>