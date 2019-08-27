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

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");

$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);
$client = new DefaultAcsClient($profile);
$video_id = @$_POST['video_id'];
$uid = @$_POST['uid'];
if(!$video_id)
{
    json(-1,"参数错误");
}
function GetVideoPlayInfo($client, $regionId,$video_id) {
    $request = new vod\GetVideoPlayInfoRequest();
    $request->setAcceptFormat('JSON');
    $request->setVideoId($video_id);            //视频ID
    $request->getRegionId($regionId);
    $request->setActionName('GetPlayInfo');
    $response = $client->getAcsResponse($request);
    return $response;
}
function del_video_stream($client, $regionId,$video_id,$JobIds)
{
    $request = new vod\DeleteStreamRequest();
    $request->setActionName('DeleteStream');
    $request->setVideoId($video_id);
    $request->getRegionId($regionId);
    $request->setJobIds($JobIds);
    $response = $client->getAcsResponse($request);
    return $response;

}
function  del_video_list($client, $regionId,$video_id)
{
    $request = new vod\DeleteVideoRequest();
    $request->setActionName('DeleteVideo');
    $request->getRegionId($regionId);
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
//    $res = @GetVideoPlayInfo($client,$regionId,$video_id);
//    $res = $res->PlayInfoList;
//    $res = $res->PlayInfo;
//    $jobids = '';
//    foreach($res as $key => $val){
//        if($key+1 != 1){
//            $jobids.=",".$res[$key]->JobId;
//        }else{
//            $jobids.=$res[$key]->JobId;
//        }
//    }

    //$dvs = @del_video_stream($client, $regionId,$video_id,$jobids);//保留记录，删除文件
    $dvl = @del_video_list($client, $regionId,$video_id);//记录文件一同删除

	$sql="";
	if($dvl->RequestId){
        $temp_arr = explode(",",$video_id);
		if(count($temp_arr) == 1){
			$sql = "delete from de_joke_video_posi where video_id = '".$video_id."';";
			$sql.="delete from de_joke_video_comment where video_id = '".$video_id."';";
			$sql.="delete from de_joke_video_upvote where video_id = '".$video_id."';";
			$st2 = $pdo->prepare($sql);
			$st2 -> execute();
		}else{
			$video_id = implode("','",$temp_arr);
			$sql = "delete from de_joke_video_posi where video_id in ('".$video_id."');";
			$sql.="delete from de_joke_video_comment where video_id in ('".$video_id."');";
			$sql.="delete from de_joke_video_upvote where video_id in ('".$video_id."');";
			$st2 = $pdo->prepare($sql);
			$st2 -> execute();
		}
        
	}

    json(0,'成功',$dvl);
}catch(Exception $e){
    json(-3,'操作失败');
    //echo $e->getMessage();
}