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
$uid = @$_POST['uid'];
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
	
	try {
		//$res = GetVideoInfo($client, $regionId,$video_id);
        $num = 20;
        $limit = ($page-1)*$num.",".$num;

        $sql = "select * from de_joke_video_follow where uid = ".$uid." and follow = 1";
        $st = $pdo->prepare($sql);
        $st -> execute();
        $result = $st ->fetchAll(PDO::FETCH_ASSOC);

        if($result){
            //取出关注的所有uid
            $follow_uid = array();
            $follow_video_id = array();
            foreach($result as $key => $val){
                $follow_video_id[] = $val['video_id'];
            }

            $video_id_str = implode("','",$follow_video_id);
            //查询关注的视频信息
            $sql = "select * from de_joke_video_posi where video_id in ('".$video_id_str."')";
            $st2 = $pdo->prepare($sql);
            $st2 -> execute();
            $result2 = $st2 ->fetchAll(PDO::FETCH_ASSOC);
            if($result2){

                foreach($result2 as $kk => $vv){
                    $follow_uid[] = $vv['uid'];
                }
                $uid_str = implode(",",$follow_uid);

                $sql = "select * from de_joke_video_posi where uid in (".$uid_str.") order by time desc limit ".$limit;
                $st4 = $pdo->prepare($sql);
                $st4 -> execute();
                $result4 = $st4 ->fetchAll(PDO::FETCH_ASSOC);

                if($result4){
                    $res = array();
                    //查找用户信息
                    $sql2 = "select * from de_user where id in (".$uid_str.")";
                    $st3 = $pdo->prepare($sql2);
                    $st3 -> execute();
                    $result3 = $st3 ->fetchAll(PDO::FETCH_ASSOC);

                    foreach($result4 as $k1 => $v1){
                        @$temp=GetVideoInfo($client, $regionId,$v1['video_id']);
                        $temp = $temp->Video;
                        foreach($result3 as $k2 => $v3){
                            if($v1['uid'] == $v3['id']){
                                $temp->avatar=$v3['avatar'];
                                $temp->user_nickname=$v3['user_nickname'];
                                $temp->uid=$v3['id'];
                            }
                        }
						
						$sql5 = "select uid,count(*) as num from de_joke_video_upvote where video_id = '".$v1['video_id']."' and upvote = 1";
						$st5 = $pdo->prepare($sql5);
						$result5 = $st5 -> execute();
						$result5 = $st5->fetch(PDO::FETCH_ASSOC);
						$temp->upvote_num = $result5['num'];
						if($uid == $result5['uid']){
							$temp->is_my_upvote = "1";
						}else{
							$temp->is_my_upvote = "0";
						}
						
						$sql6 = "select count(*) as num from de_joke_video_comment where video_id = '".$val['video_id']."'";
						$st6 = $pdo->prepare($sql6);
						$result6 = $st6 -> execute();
						$result6 = $st6->fetch(PDO::FETCH_ASSOC);
						$temp->comment_num = $result6['num'];

                        $temp->type="3";
						
						$image_info = getimagesize($temp->CoverURL);
						$temp->width = "".$image_info[0];
						$temp->height = "".$image_info[1];

                        $res[] = $temp;
                    }

                    json(0,'成功',$res);
                }else{
                    json(-4,'没有更多了');
                }

            }else{
                json(-4,'没有更多了');
            }
        }else{
            json(-3,'您还没有关注的人');
        }

	} catch (Exception $e) {
		json(-2,'请求错误');
	}
?>