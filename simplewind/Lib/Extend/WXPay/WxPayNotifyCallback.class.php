<?php
/**
 * Created by PhpStorm.
 * User: tianyu
 * Date: 2015/07/31
 * Time: 13:17
 */
require_once "WxPay.class.php";
require_once 'WxPay.Notify.php';

class WxPayNotifyCallback extends WxPayNotify {
    //查询订单
    public function Queryorder($transaction_id) {
        return A("Home/Pay")->wxpay_Queryorder($transaction_id);
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg) {
        return A("Home/Pay")->wxpay_NotifyProcess($data, $msg);
    }
}