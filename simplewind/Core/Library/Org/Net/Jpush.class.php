<?php
/**
 * 极光推送
 */
namespace Org\Net;
class Jpush {
	private $_masterSecret = '';
	private $_appkeys = '';
	/**
	 * 构造函数
	 * @param string $username        	
	 * @param string $password        	
	 * @param string $appkeys        	
	 */
	function __construct($masterSecret = '', $appkeys = '') {
		$this->_masterSecret = $masterSecret;		
		$this->_appkeys = $appkeys;
	}
	
	/**
	 * 模拟post进行url请求
	 * @param string $url        	
	 * @param string $param        	
	 */
	function request_post($url = "", $param = "", $header = "") {
		if (empty ( $url ) || empty ( $param )) {			
			return false;
		}
                $base64 = base64_encode ( "$this->_appkeys:$this->_masterSecret" );		
		$header = array (
				"Authorization:Basic $base64",
				"Content-Type:application/json" 
		);
		$postUrl = $url;		
		$curlPost = $param;		
		$ch = curl_init (); // 初始化curl		
		curl_setopt ( $ch, CURLOPT_URL, $postUrl ); // 抓取指定网页		
		curl_setopt ( $ch, CURLOPT_HEADER, 0 ); // 设置header		
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 ); // 要求结果为字符串且输出到屏幕上		
		curl_setopt ( $ch, CURLOPT_POST, 1 ); // post提交方式		
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $curlPost );		
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );		
		// 增加 HTTP Header（头）里的字段		
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );		
		// 终止从服务端进行验证		
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );		
		$data = curl_exec ( $ch ); // 运行curl		
		curl_close ( $ch );		
		return $data;
	}
	public function getTags() {
            $url ='https://device.jpush.cn/v3/tags/';
            return json_decode($this->request_post($url));
        }
        public function getUsers($time_unit, $start, $duration) {
            $time_unit = strtoupper($time_unit);
            $url ='https://report.jpush.cn/v3/users?time_unit=' . $time_unit . '&start=' . $start . '&duration=' . $duration;
            return $this->request_post($url);
        }
	/*
	 * $receiver 接收者的信息
	 * all 字符串 该产品下面的所有用户. 对app_key下的所有用户推送消息
	 * tag(20个)Array标签组(并集): tag=>array('昆明','北京','曲靖','上海');
	 * tag_and(20个)Array标签组(交集): tag_and=>array('广州','女');
	 * alias(1000)Array别名(并集): alias=>array('93d78b73611d886a74*****88497f501','606d05090896228f66ae10d1*****310');
	 * registration_id(1000)注册ID设备标识(并集): registration_id=>array('20effc071de0b45c1a**********2824746e1ff2001bd80308a467d800bed39e');
	 *
	 */
	
	// $content 推送的内容。	
	// $extras 附加字段	
	// $m_time 保存离线时间的秒数默认为一天(可不传)单位为秒	
	// $message_type消息类型,0消息，1通知
	public function pushMessage($title = '', $message = '', $receiver = 'all', $message_type = 1, $extras = array(), $m_time = '86400', $platform = 'all') {
		$url = 'https://api.jpush.cn/v3/push';		
		$data = array ();		
		$data ['platform'] = $platform; // 目标用户终端手机的平台类型android,ios,winphone		
		$data ['audience'] = $receiver; // 目标用户		
		if ($message_type == 1) {			
			$data ['notification'] = array (					
					// 统一的模式--标准模式					
					"alert" => $message,					
					// 安卓自定义					
					"android" => array (							
							"alert" => $message,							
							"title" => $title,							
							"builder_id" => 1,							
							"extras" => $extras,
							"alert_type" => 4
					),					
					// ios的自定义					
					"ios" => array (							
							// "alert"=>$content,						
							"badge" => '1',							
							"sound" => "Wonderful.mp3",
							"mutable-content" => true
					)
					// "extras"=>array("type"=>$m_type, "txt"=>$m_txt)					 
			);
		} else {			
			// 苹果自定义---为了弹出值方便调测			
			$data ['message'] = array (					
					"title" => $title,					
					"msg_content" => $message,					
					"extras" => $extras 
			);
		}		
		// 附加选项		
		$data ['options'] = array (			
				"sendno" => time (),				
				"time_to_live" => $m_time, // 保存离线时间的秒数默认为一天				
				"apns_production" => 0 
		); 
		// 指定 APNS 通知发送环境：0开发环境，1生产环境。
		$param = json_encode ( $data );		
		$res = $this->request_post ( $url, $param);		
		if ($res === false) {			
			return false;
		}		
		$res_arr = json_decode ( $res, true );		
		return $res_arr;
	}
        
}
?>