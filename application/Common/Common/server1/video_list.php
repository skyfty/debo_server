<?php
include_once 'aliyun-php-sdk-core/Config.php';  
include_once 'aliyun-php-sdk-core/RpcAcsRequest.php';  
use vod\Request\V20170321 as vod;

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/shanghai");
  
$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);  
  
$client = new DefaultAcsClient($profile);  
$request = new BC\ListImagesRequest();

$publicParams = array(
	"Format"        =>  "JSON",
	"Version"       =>  "2017-03-21",
	"AccessKeyId"   =>  "LTAIqDHaGdKLblTM",
	"Timestamp"     =>  date("Y-m-d\TH:i:s\Z"),
	"SignatureMethod"   =>  "HMAC-SHA1",
	"SignatureVersion"  =>  "1.0",
	"SignatureNonce"    =>  substr(md5(rand(1,99999999)),rand(1,9),14),
);
$res = $client->doAction('GetVideoList',null,$publicParams);
var_dump($res);



?>