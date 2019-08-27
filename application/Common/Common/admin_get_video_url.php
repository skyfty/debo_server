<?php
include_once 'aliyun-php-sdk-core/Config.php';
use vod\Request\V20170321 as vod;

$video_id = @$_GET['video_id'];
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
    $res = GetVideoPlayInfo($client, $regionId,$video_id);
//    $cover_url = $res->VideoBase;
//    $cover_url = $cover_url->CoverURL;
    $res = $res->PlayInfoList;
    $res = $res->PlayInfo;

    $arr = array();
    foreach($res as $k => $v){
        if($v->Format == 'mp4' && $v->Definition == 'FD')
        {
            $res[$k]->video_id = $video_id;
            $arr = $res[$k];
        }
    }

    echo json_encode($arr);
} catch (Exception $e) {
    json(-2,'无此视频');
}
?>