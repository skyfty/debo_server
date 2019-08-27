<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/12 0012
 * Time: 下午 3:21
 */

namespace Appapi\Controller\Debo1;

use Common\Controller\ApibaseController;

class TransmitFileController extends ApibaseController
{

    public function __construct()
    {
        parent::__construct();
    }


    // app绑定
    public function private_bind()
    {
        $uid = I('uid');
        $client_id = I('client_id');
        $gateway = new \Org\Util\Gateway('websocket://0.0.0.0:8282');
        $gateway->bindUid($client_id, $uid);
        $message = '绑定成功' . $uid . '-' . $client_id;
        $message = json_encode(array(
            'type'      => 'msg',
            'message' => $message
        ));
        $gateway->sendToUid($uid, $message);
    }

    // app绑定
    public function group_bind()
    {
        $uid = I('uid');
        $client_id = I('client_id');
        $gateway = new \Org\Util\Gateway('websocket://0.0.0.0:8282');
        $gateway->bindUid($client_id, $uid);
        //查询群组
        $groups = M('chatgroup_member')->where(array('uid'=>I('uid')))->select();
        $group_id = array_column($groups,'group_id');
        foreach ($group_id as $k => $v) {
            $gateway->joinGroup($client_id, $v);
        }
        $message = '绑定成功' . $uid . '-' . $client_id;
        $gateway->sendToUid($uid, $message);
    }

    // 传输文件
    public function transmit_file() {
        if($_FILES['file']) {
            $f_uid = I('f_uid');
            $group_id = I('group_id');
            $gateway = new \Org\Util\Gateway('websocket://0.0.0.0:8282');
            $img = $this->base64EncodeImage($_FILES["file"]["tmp_name"]);
            $message = json_encode(array(
                'type'      => 'msg',
                'message' => $img
            ));
            if($f_uid) {
                $gateway->sendToUid($f_uid, $message);
            } elseif($group_id) {
                $gateway->sendToGroup($group_id, $message);
            }
            echo json_encode($message);exit;
        }
    }

    /**
     * @name 将图片转换成base64编码
     * @Author: 岳晓通
     * @ $image_file 这个是图片的路径
     * @ $base64_image 返回的是base64格式图片的编码
     * @data 2018年3月14日 15:18:27
     */
    function base64EncodeImage ($image_file) {
        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }


      public function xun_search() {

          Vendor('xunsearch.lib.XS');
          $xs = new \XS('source');    // demo  为项目名称，配置文件是：$sdk/app/demo.in i
          //$index = $xs->index;   //  获取索引对象
          $search = $xs->search;   //  获取搜索对象
          $search->setLimit(20);
          $docs = $search->setQuery( $keyword)->search();
      }
}
?>