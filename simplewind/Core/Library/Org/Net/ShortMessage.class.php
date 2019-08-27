<?php
namespace Org\Net;
header("Content-type:text/html;charset=utf-8");
class ShortMessage{
	public function curlPost($url,$param_array,$ca_info){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($param_array));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);		//验证交换证书
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 		//检查SSL证书公用名是否存在，并且是否与提供的主机名匹配
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);		//设置实现协议为TLS1.0版本
		//curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3);		//设置SSL/TLS版本
		curl_setopt($ch, CURLOPT_CAINFO,  $ca_info); 		//设置根证书文件路径(最好是绝对路径) 
			
		$data = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if(!empty($error)){		//curl有错误
			echo $error;
		}else{		//输出请求结果
			$info = array("code"=> 0,"message"=>"短信发送成功");
			$info_res=json_encode($info);
			echo $info_res;
		}
	}

	/**
	* 基于curl的get访问方式，使用TLSv1加密,
	* 要求php打开curl扩展,要求php的版本至少在5.4以上(PHP 5.4才开始支持SSL),5.6以上推荐(完整的SSL支持)
	* $url @string 请求地址
	* $param_array @array 参数数组(Get方式的参数,Unicode编码后的总长度不得超过2048,否则会被服务器截断导致出错)
	* $ca_info 根证书文件路径 绝对路径
	*/
	public function curlGet($url,$param_array,$ca_info){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url . '?' . http_build_query($param_array));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);		//验证交换证书
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 		//检查SSL证书公用名是否存在，并且是否与提供的主机名匹配
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);		//设置实现协议为TLS1.0版本
		//curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3);		//设置SSL/TLS版本
		curl_setopt($ch, CURLOPT_CAINFO,  $ca_info); 		//设置根证书文件路径(最好是绝对路径)
		
		
		$data = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if(!empty($error)){		//curl有错误
			echo $error;
		}else{		//输出请求结果
			echo $data;
		}
	}





	//过期证书测试,发现php在这块能力不行
	/*function revoked_cert_test($type='get'){
		$url = 'https://revoked-demo.wosign.com/';
		$ca_info = 'F:/cert/cacert.pem';		//根证书文件路径，绝对路径	
		$param_arry = array();
		
		if($type =='get'){		//请求方式自选，get post都可以，推荐post
			curlGet($url,$param_arry,$ca_info);
		}else{
			
			curlPost($url,$param_arry,$ca_info);
		}
		
	}*/

	/*
	* 获取余额
	* $type @string 请求方式,post或者get,推荐post,get有参数长度2048限制
	*/
	public function getBalance($type='post'){
		$url 	= 'https://www.stongnet.com/sdkhttp/getbalance.aspx';		//https接口地址
		$ca_info = '/www/web/debo/public_html/simplewind/Core/Library/Org/Net/cacert.pem';		//根证书文件路径，绝对路径
		
		$reg_code = '101100-WEB-HUAX-207526';		//华兴软通注册码，请在这里填写您从客服那得到的注册码
		$reg_pw = 'NTDCVOZZ';		//华兴软通注册码对应的密码，请在这里填写您从客服那得到的密码
		
		$param_arry = array();
		$param_arry['reg'] = $reg_code;
		$param_arry['pwd'] = $reg_pw;
			
		if($type =='get'){
			$this->curlGet($url,$param_arry,$ca_info);
		}else{
			$this->curlPost($url,$param_arry,$ca_info);
		}
		
	}

	/*
	* 发送短信
	* $type @string 请求方式,post或者get,推荐post,get有参数长度2048限制
	*/
	public function sendSMS($type='post',$mobile,$code){
		$url	= 'https://www.stongnet.com/sdkhttp/sendsms.aspx';		//https接口地址
		$ca_info = '/www/web/debo/public_html/simplewind/Core/Library/Org/Net/cacert.pem';		//根证书文件路径，绝对路径
		
		$reg_code = '101100-WEB-HUAX-207526';		//华兴软通注册码，请在这里填写您从客服那得到的注册码
		$reg_pw = 'NTDCVOZZ';		//华兴软通注册码对应的密码，请在这里填写您从客服那得到的密码
		$source_add = '';		//子通道号（最长10位，可为空
		$phone = $mobile;		//手机号码（最多1000个），多个用英文逗号(,)隔开，不可为空
		/*
		 *  签名:工信部规定,签名表示用户的真实身份,请不要在签名中冒用别人的身份,如客户使用虚假身份我们将封号处理并以诈骗为由提交工信部备案，一切责任后果由客户承担
		 *  华兴软通短信系统要求签名必须附加在短信内容的尾部,以全角中文中括号包括,且括号之后不能再有空格,否则将导致发送失败
		 *  虽然在程序中,签名是附加在短信内容的尾部,但是真实短信送达到用户手机时,签名则可能出现在短信的头部,这是各地运营商的政策不同,会在它们自己的路由对签名的位置做调整
		 *  短信内容的长度计算会包括签名;签名内容的长度限制受政策变化,具体请咨询客服
		 *  写在程序里是让用户自定义签名的方式,还有一种方式是让客服绑定签名,这种方式签名不需要写在程序中,具体请咨询客服
		 */
        $signature = '【嘚啵】';      //签名
        $content = '您验证码是:' . $code ."有效期10分钟，请在有效期内使用。". $signature;	//短信内容,请严格按照客服定义的模板生成短信内容,否则发送将失败(含有中文，特殊符号等非ASCII码的字符，用户必须保证其为UTF-8编码格式)
        $param_arry = array();
        $param_arry['reg'] = $reg_code;
        $param_arry['pwd'] = $reg_pw;
        $param_arry['sourceadd'] = $source_add;
        $param_arry['phone'] = $phone;
        $param_arry['content'] = $content;

		if($type =='get'){
			$this->curlGet($url,$param_arry,$ca_info);
		}else{
			$this->curlPost($url,$param_arry,$ca_info);
		}
	}
}


//调用范例	D:\php7.0\php demo_http.php

//getBalance('post');		//post方式(推荐)
//sendSMS('post');		//post方式(推荐)

//getBalance('get');		//get(服务器限制Get方式的参数编码后总长度为2048)
//sendSMS('get');		//get(服务器限制Get方式的参数编码后总长度为2048)
