<?php
/**
 * Created by PhpStorm.
 * User: zhangchaoxun
 * Date: 2017/11/15
 * Time: 13:40
 */
namespace Appapi\Controller;

use Common\Controller\ApibaseController;
use Common\Common\Easemob;

class CopartnerController extends ApibaseController{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *前端请求(用来做判断的)
     * */
    public function get_states(){
        $this->json(0,'获取成功',array('is_show'=>1));
    }

    /**
     *查询实名认证是否通过
     * */
    public function check_identity_certification(){
        $uid = I('uid');
        if(!$uid){
            $this->json(-1,'参数错误');
        }

        $find_info = M('identity_certification')->where("uid = ".$uid)->find();
        if(!$find_info){
            $verify_state = "3";//未通过审核
        }else{
            $verify_state = "".$find_info['state'];
        }

        $this->json(0,'成功',array('verify_state' => $verify_state,'purchase_limit' => "1000"));

    }

    /**
     * 合伙人首页接口
     * */
    public function copartner_index(){
        $uid = I('uid');
        $mobile = I('mobile');//用户本人手机号 做验证使用

        if(!$uid || !$mobile){
            $this->json(-1,'参数错误');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            $this->json(-2,'该用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            $this->json(-3,'用户信息不匹配');
        }

        //查询用户是否开通合伙人
        $is_open_copartner = M('copartner')->where("uid = ".$uid)->find();
        if(!$is_open_copartner){
            $this->json(-4,'请实名认证');
        }

        //查询昨日收益
        $find_yesterday_income = M('debo_coins_settle_accounts')->where("uid = ".$uid)->order('time desc')->limit(1)->find();
        if(!$find_yesterday_income){
            $income = "0";
        }else{
            $income = $find_yesterday_income['income'];
        }

        $res = array(
            'purchase_limit' => $is_open_copartner['purchase_limit'],//可购买的最高额度
            'debo_coins' => $is_open_copartner['debo_coins'],//嘚啵币总数（个）
            'income' => $income,//昨日收益个数
        );

        $this->json(0,'成功',$res);

    }

    /**
     *合伙人提现接口
     * */
    public function copartner_withdraw_cash(){
        $num = I('num');//嘚啵币个数
        $uid = I('uid');
        $mobile = I('mobile');//用户手机号 做验证使用
        if(!$num || !$uid || !$mobile){
            $this->json(-1,'参数错误');
        }
        if($num < 100) {
            $this->json(-6,'最低提现100元');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            $this->json(-2,'用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            $this->json(-3,'用户信息不匹配');
        }

      /*  if(!ctype_digit($num)){//检测是否都是数字（负数和小数会检测不通过）
            $this->json(-1,'参数错误');
        }*/


        //查询用户现有的嘚啵币
        $find_debo_coins = M('copartner')->where("uid = ".$uid)->find();

        if(!$find_debo_coins){
            $this->json(-4,'账户信息不存在');
        }

        // 获取定期金额
        $fixed_num = M('debo_coins_purchase')->where('uid = '.$uid.' and is_pay = 1 and regular <> 0 and regular <> -1')->sum('num');
        if($fixed_num) {
            $find_debo_coins['debo_coins'] = $find_debo_coins['debo_coins'] - $fixed_num;
        }

        if($num > $find_debo_coins['debo_coins']){
            $this->json(-5,'嘚啵币不足或嘚啵币在定期');
        }

        //生成订单
        $order_sn = date('YmdHis').rand(1000,9999);
        $data = array(
            'uid'=>$uid,
            'order_price'=>$num,
            'pay_type'=>0,
            'indent'=>17,
            'order_sn'=>$order_sn,
            'pay_status'=>2,
            'create_time'=>time(),
        );
        $trans_res = M('transaction')->add($data);

        if(!$trans_res){
            $this->json(-6,'提现失败');
        }

        //修改嘚啵币数量 和 钱包
        M('user')->where("id = ".$uid)->setInc('coins',$num);
        M('copartner')->where("uid = ".$uid)->setDec('debo_coins',$num);

        // 判断是否有自由存款如果有更新自由存款数量
        $debo_coins_purchase = M('debo_coins_purchase')->where('uid = '.$uid. ' and regular = 0 and num > 0')->select();
        if($debo_coins_purchase) {
            foreach ($debo_coins_purchase as $k => $v) {
                if($v['num'] >= $num) {
                    M('debo_coins_purchase')->where('id = '.$v['id'])->setDec('num',$num);
                    break;
                } elseif($v['num'] < $num) {
                    M('debo_coins_purchase')->where('id = '.$v['id'])->setDec('num',$v['num']);
                    $num = $num - $v['num'];
                }
            }
        }

        $this->json(0,'提现成功');

    }

    /**
     * 每日收益列表
     * */
    public function get_settle_accounts_list(){
        $uid = I('uid');
        $mobile = I('mobile');//做验证使用
        if(!$uid || !$mobile){
            $this->json(-1,'参数错误');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            $this->json(-2,'用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            $this->json(-3,'用户信息不匹配');
        }

        $page = I('page');
        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;
        $list = array();

        //查询每日收益
        $find_settle_accounts = M('debo_coins_settle_accounts')->alias('a')->field('a.id,a.uid,a.income,a.time,b.regular,b.money')->where("a.uid = ".$uid)->join('LEFT JOIN de_debo_coins_purchase b ON a.debo_coins_purchase_id=b.id')->order('a.time desc')->limit($limit)->select();
        if(!$find_settle_accounts){
            $list['total_income'] = "0";
            $list['income_list'] = array();

            $this->json(0,'成功',$list);
        }

        $total_income = M('debo_coins_settle_accounts')->where("uid = ".$uid)->sum('income');
        foreach($find_settle_accounts as $key => $val){
            $find_settle_accounts[$key]['time'] = date('Y-m-d',$val['time']);
            $find_settle_accounts[$key]['content'] = '收益'.$val['income'].'个金币';
            if($val['regular'] == '1') {
                $find_settle_accounts[$key]['regular'] = '三个月';
            }  elseif($val['regular'] == '2') {
                $find_settle_accounts[$key]['regular'] = '六个月';
            }  elseif($val['regular'] == '3') {
                $find_settle_accounts[$key]['regular'] = '九个月';
            }  elseif($val['regular'] == '4') {
                $find_settle_accounts[$key]['regular'] = '十二个月';
            } elseif($val['regular'] == '0') {
                $find_settle_accounts[$key]['regular'] = '自由存款';
            } elseif($val['regular'] == '-1') {
                $find_settle_accounts[$key]['regular'] = '已到期';
            }
            unset($find_settle_accounts[$key]['income']);
        }

        $list['total_income'] = $total_income;
        $list['income_list'] = $find_settle_accounts;

        $this->json(0,'成功',$list);
    }

    /**
     *获取购买利率
     * */
    public function get_rate() {
        $options = M('options')->field('option_value')->where(array('option_name'=>"site_options"))->find();
        $options = json_decode($options['option_value'],true);
        $data = array(
            'three_rate' => '三个月 | ' . $options['three_rate'] . '%',
            'six_rate' => '六个月 | ' . $options['six_rate'] . '%',
            'nine_rate' => '九个月 | ' . $options['nine_rate'] . '%',
            'twelve_rate' => '十二个月 | '. $options['twelve_rate'] . '%',
            'freedom' => '自由购买',
        );
        $this->json(0,'成功', $data);
    }

    /**
     *卖出嘚啵币接口
     * */
    public function sale_debo_coins(){
        $uid = I('uid');
        $debo_coins = I('debo_coins');//嘚啵币个数
        $money = I('money');//卖出的金额
        $mobile = I('mobile');//做验证使用
        if(!$uid || !$debo_coins || !$mobile || !$money){
            $this->json(-1,'参数错误');
        }

        if(!ctype_digit($debo_coins)){//检测是否都是数字（负数和小数会检测不通过）
            $this->json(-6,'卖出的数量只能是整数');
        }

        if($debo_coins >= $money) {
            $this->json(-7,'卖出的金额必选大于卖出的数量');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            $this->json(-2,'用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            $this->json(-3,'用户信息不匹配');
        }

        //查询嘚啵币账户信息
        $find_debo_coins_info = M('copartner')->where('uid = '.$uid)->find();
        if(!$find_debo_coins_info){
            $this->json(-4,'账户信息不存在');
        }

        // 获取定期金额
        $fixed_num = M('debo_coins_purchase')->where('uid = '.$uid.' and is_pay = 1 and regular <> 0 and regular <> -1')->sum('num');
        if($fixed_num) {
            $find_debo_coins_info['debo_coins'] = $find_debo_coins_info['debo_coins'] - $fixed_num;
        }

        if($debo_coins > $find_debo_coins_info['debo_coins']){
            $this->json(-5,'嘚啵币不足或嘚啵币在定期');
        }

        //商城
        $data = array(
            'uid' => $uid,
            'debo_coins' => $debo_coins,
            'time' => time(),
            'money' => $money,
        );
        $res = M('debo_coins_shop')->add($data);

        if(!$res){
            $this->json(-6,'出售失败');
        }

        //将原有嘚啵币减少
        M('copartner')->where("uid = ".$uid)->setDec('debo_coins',$debo_coins);

        $this->json(0,'出售成功');

    }

    /**
     * 嘚啵币商城列表（包括自己卖出的列表）
     * */
    public function debo_coins_shop_list(){
        $uid = I('uid');
        //$page = I('page');
        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        if($uid){
            $status = I('status');
            if(!$status){
                $status = 1;
            }
            $find_shop_list = M('debo_coins_shop')->where('status = '.$status.' and uid = '.$uid)->order('time desc')->limit($limit)->select();
            if(!$find_shop_list){
                $this->json(-1,'没有更多数据了');
            }
            foreach($find_shop_list as $key => $val){
                $find_shop_list[$key]['time'] = date('Y-m-d',$val['time']);
            }
            $this->json(0,'成功',$find_shop_list);
        }else{
            //查找商城列表
            $find_shop_list = M('debo_coins_shop')->where('status = 1')->limit($limit)->select();
            if(!$find_shop_list){
                $this->json(-1,'没有更多数据了');
            }
            foreach($find_shop_list as $key => $val){
                $find_shop_list[$key]['time'] = date('Y-m-d',$val['time']);
            }
            $this->json(0,'成功',$find_shop_list);
        }

    }

    /**
     * 商品下架接口
     * */
    public function remove_debo_coins_goods(){
        $uid = I('uid');
        $mobile = I('mobile');
        $debo_id = I('debo_id');
        if(!$uid || !$mobile){
            $this->json(-1,'参数错误');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            $this->json(-2,'用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            $this->json(-3,'用户信息不匹配');
        }

        //查询嘚啵币账户信息
        $find_debo_coins_info = M('copartner')->where('uid = '.$uid)->find();
        if(!$find_debo_coins_info){
            $this->json(-4,'账户信息不存在');
        }

        //清除单个
        if($debo_id){
            //查询商品信息
            $find_goods_info = M('debo_coins_shop')->where('id = '.$debo_id)->find();
            if($find_goods_info['uid'] != $uid){
                $this->json(-5,'商品信息不匹配');
            }

            //将嘚啵币归还原主
            $inc = M('copartner')->where("uid = ".$uid)->setInc('debo_coins',$find_goods_info['debo_coins']);
            if($inc){
                $res = M('debo_coins_shop')->where('id = '.$debo_id)->delete();
                if(!$res){
                    M('copartner')->where("uid = ".$uid)->setDec('debo_coins',$find_goods_info['debo_coins']);
                    $this->json(-6,'操作失败');
                }

                $this->json(0,'操作成功');
            }

        }else{

            //将嘚啵币归还原主
            $res = M('debo_coins_shop')->where("uid = ".$uid." and status = 2")->delete();
            if(!$res){
                $this->json(-6,'操作失败');
            }

            $this->json(0,'操作成功');

        }

    }

    /**
     *查询自己的嘚啵币交易记录
     * */
    public function find_debo_coins_transaction(){
        $uid = I('uid');
        $mobile = I('mobile');
        if(!$uid || !$mobile){
            $this->json(-1,'参数错误');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            $this->json(-2,'用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            $this->json(-3,'用户信息不匹配');
        }

        //查询嘚啵币账户信息
        $find_debo_coins_info = M('copartner')->where('uid = '.$uid)->find();
        if(!$find_debo_coins_info){
            $this->json(-4,'账户信息不存在');
        }

        //$page = I('page');
        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查询交易记录
        $find_transaction_info = M('debo_coins_purchase')->where('uid = '.$uid.' or con_uid = '.$uid)->order('create_time desc')->limit($limit)->select();
        if(!$find_transaction_info){
            $this->json(-5,'没有更多记录了');
        }

        foreach($find_transaction_info as $key => $val){
            $find_transaction_info[$key]['create_time'] = date('Y-m-d',$val['create_time']);
            $find_transaction_info[$key]['trans_type'] = "1";
        }

        $this->json(0,'成功',$find_transaction_info);

    }

    /**
     * 删除交易记录接口
     * */
    public function del_trans(){
        $uid = I('uid');
        $mobile = I('mobile');
        $debo_id = I('debo_id');

        if(!$uid || !$mobile){
            $this->json(-1,'参数错误');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            $this->json(-2,'用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            $this->json(-3,'用户信息不匹配');
        }

        //查询嘚啵币账户信息
        $find_debo_coins_info = M('copartner')->where('uid = '.$uid)->find();
        if(!$find_debo_coins_info){
            $this->json(-4,'账户信息不存在');
        }

        if($debo_id){
            M('debo_coins_purchase')->where('id = '.$debo_id)->delete();
            $this->json(0,'删除成功');
        }else{
            M('debo_coins_purchase')->where('uid = '.$uid)->delete();
            $this->json(0,'删除成功');
        }

    }

    /**
     *嘚啵币用户协议
     * */
    public function debo_coins_user_agreement(){
        $this->display();
    }

    /**
     *嘚啵币服务合同
     * */
    public function debo_coins_service_contract(){
        $this->display();
    }

    /**
     * 存储罐协议
     * */
    public function storage_agreement(){
        $this->display();
    }

    /**
     * 了解合伙人
     * */
    public function learn_about(){
         $this->display();
    }

}