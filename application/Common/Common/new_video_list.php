<?php
include_once 'aliyun-php-sdk-core/Config.php';  
include_once 'aliyun-php-sdk-core/RpcAcsRequest.php';  
include_once 'aliyun-php-sdk-core/Auth/ShaHmac1Signer.php';  
include_once 'aliyun-php-sdk-core/Auth/Credential.php';  
use vod\Request\V20170321 as vod;

$regionId = 'cn-shanghai';
$access_key_id = 'LTAIqDHaGdKLblTM';
$access_key_secret = 'mwJIDjwTqUf9FhitKV8H9HwNtdRcfA';
date_default_timezone_set("Asia/Shanghai");


$profile = DefaultProfile::getProfile($regionId, $access_key_id, $access_key_secret);  
$client = new DefaultAcsClient($profile);


$iSigner = new ShaHmac1Signer();

$str = "Format=JSON&Version=2017-03-21&AccessKeyId=".$access_key_id."&Timestamp=2017-11-11T05:03:00Z&SignatureMethod=HMAC-SHA1&SignatureVersion=1.0&SignatureNonce=".substr(md5(rand(1,99999999)),rand(1,9),14);
$str1 = "GET&%2F&AccessKeyId%3DLTAIqDHaGdKLblTM%26Action%3DGetVideoList%26Format%3DJSON%26SignatureMethod%3DHMAC-SHA1%26SignatureNonce%3D".substr(md5(rand(1,99999999)),rand(1,9),14)."%26SignatureVersion%3D1.0%26Status%3DNormal%26Timestamp%3D2017-11-11T3%253A48%253A00Z%26Version%3D2017-03-21";

$SignatureNonce = substr(md5(rand(1,99999999)),rand(1,9),14);
$Timestamp = date("Y-m-d\TH:i:s\Z");

$data = array(
	'AccessKeyId' => $access_key_id,
	'Action' => 'GetVideoList',
	'Format' => 'JSON',
	'SignatureMethod' => 'HMAC-SHA1',
	'SignatureNonce' => $SignatureNonce,
	'SignatureVersion' => '1.0',
	'Status' => 'Normal',
	'Timestamp' => $Timestamp,
	'Version' => '2017-03-21',
);
ksort($data);
$str2 = to_params($data);
$str2 = 'GET&%2F&'.percentEncode($str2);

$my_sign = $iSigner -> signString($str2,$access_key_secret."&");

$data2 = array(
	'AccessKeyId' => $access_key_id,
	'Action' => 'GetVideoList',
	'Format' => 'JSON',
	'Signature' => $my_sign,
	'SignatureMethod' => 'HMAC-SHA1',
	'SignatureNonce' => $SignatureNonce,
	'SignatureVersion' => '1.0',
	'Status' => 'Normal',
	'Timestamp' => $Timestamp,
	'Version' => '2017-03-21',
);
ksort($data2);
$str3 = to_params($data2);

function curl_get($url, $header = null)
{
	$my_curl = curl_init();
	curl_setopt($my_curl, CURLOPT_URL, $url);
	curl_setopt($my_curl, CURLOPT_RETURNTRANSFER, 1);

	if ($header) {
		$header_list = array();
		foreach ($header as $key => $value) {
			$header_list[] = "$key: $value";
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_list);
	}

	$str = curl_exec($my_curl);
	curl_close($my_curl);

	return $str;
}
function to_params($input)
{
	$index = 0;
	$pair = '';
	foreach ($input as $key => $value) {
		if ($index != 0) {
			$pair .= '&';
		}
		$pair .= "$key=".$value;
		++$index;
	}

	return $pair;
}
function percentEncode($str)
{
	$res = urlencode($str);
	$res = preg_replace('/\+/', '%20', $res);
	$res = preg_replace('/\*/', '%2A', $res);
	$res = preg_replace('/%7E/', '~', $res);
	return $res;
}

//$url = 'http://vod.cn-shanghai.aliyuncs.com/?Action=GetVideoList&Status=Normal&'.$str.'&Signature='.$my_sign;
$url = 'http://vod.cn-shanghai.aliyuncs.com/?'.$str3;
//echo $url;
echo curl_get($url);



?>