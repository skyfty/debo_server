<?php
include_once 'aliyun-php-sdk-core/Config.php';
use vod\Request\V20170321 as vod;

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");

$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);
$client = new DefaultAcsClient($profile);
$pageno = @$_GET['pageno'];
$status = @$_GET['status'];
 function get_info($client,$regionId,$pageno,$status=null)
{
    $request = new vod\GetVideoListRequest();
    $request->setActionName('GetVideoList');
    $request->setPageNo($pageno);
    if($status){
        $request->setStatus($status);
    }

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
    $res = get_info($client,$regionId,$pageno,$status);
    $total = $res->Total;
    $res = $res->VideoList;
    $res = $res->Video;

    $return_info['total'] = $total;
    $return_info['info_list'] = $res;

    echo json_encode($return_info);

}catch(Exception $e){
    //echo $e->getMessage();
    json(-2,'没有更多数据了');
}
