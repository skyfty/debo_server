<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/20 0020
 * Time: 下午 4:00
 */
namespace Appapi\Controller;

use Common\Controller\ApibaseController;
use Org\Net\AopClient;
use Common\Common\Easemob;

class CronController extends ApibaseController{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 处理聊天红包退还计划任务
     * */
    public function index(){

        //查询红包信息
        $find_info = M('red_packets')->where('now_num > 0 and is_overtime = 0')->order('time desc')->select();
        $arr = array();//存放超过24小时还没领取的红包id
        foreach($find_info as $key => $val){
            //$data = array();
            //$data_order = array();
            if(($val['time']+24*3600) <= time()){
                $arr[]=$val['id'];
                //将钱退还到
                $user = M('user')->where('id = '.$val['uid'])->setInc('coins',$val['remaining_sum']);
                if($user){
                    //生成退款订单号
                    $order_sn = date('YmdHis').rand(1000,9999);

                    //将红包剩余金钱清空
                    $data['now_num'] = 0;
                    $data['remaining_sum'] = 0.00;
                    $data['is_overtime'] = 1;
                    $data['back_time'] = time();
                    $data['order_snn'] = $order_sn;
                    $moeny_res = M('red_packets')->where('id = '.$val['id'])->save($data);

                    //生成退款订单
                    $data_order = array(
                        'uid'=>$val['uid'],
                        'order_price'=>$val['remaining_sum'],
                        'indent'=>5,
                        'order_sn'=>$order_sn,
                        'pay_status'=>2,
                        'create_time'=>time(),
                        'object_id' => $val['id']
                    );
                    $res3 = M('transaction')->add($data_order);
                }
            }
        }

    }


        //处理广告过期红包
    public function ad_bribery_back()
    {
        //查询红包信息
        $info = M('touch')->where("remaining_sum > 0 and time_range <= ".time()." and p_status in (1,2,3,5)")->select();
        // echo "<pre>";
        // print_r($info) ;
        foreach ($info as $key => $val)
        {
            $user = M('user')->where('id ='.$val['uid'])->setInc('coins',$val['remaining_sum']);
            if($user){
                //生成退款订单
                $order_sn = date('YmdHis').rand(1000,9999);
                //存入订单列表
                $order_data['uid'] = $val['uid'];
                $order_data['order_price'] = $val['remaining_sum'];
                $order_data['indent'] = 5;
                $order_data['order_sn'] = $order_sn;
                $order_data['order_price'] = $val['remaining_sum'];
                $order_data['pay_status'] = 2;
                $order_data['create_time'] = time();
                $order_data['object_id'] = $val['t_id'];

                $order = M('transaction')->add($order_data);
                
                if($order){
                    $res = M('touch')->where('t_id ='.$val['t_id'])->setfield('p_status',7);
                    // return $this->json(0,'退款成功');
                }else{
                   // return $this->json(-1,'参数有误');
                }
                
                
            }
        }
        
    }

    // 处理嘚啵币定期利息
    public function debo_interest() {

        // 取出所有存定期的金额
        $debo_coins_purchase =  M('debo_coins_purchase')->where('regular <> -1 and is_pay = 1')->order('create_time desc')->select();

        $options = M('options')->field('option_value')->where(array('option_name'=>"site_options"))->find();
        $options = json_decode($options['option_value'],true);

        //查询是否结算
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $where['time'] = array('between',array($beginToday,$endToday));
        $debo_coins_settle_accounts = M('debo_coins_settle_accounts')->where($where)->select();
        if($debo_coins_settle_accounts) {
            return $this->json(-1,'今天已经结算过');
        }

        // 当前时间
        $time = time();
        foreach ($debo_coins_purchase as $k => $v) {
            $user_info = M('copartner')->where('uid = '.$v['uid'])->find();
            if($v['regular'] == 0) {
                if($v['num'] > 0 && $v['create_time']+24*3600) {
                    // 算日息
                    $income = round($v['num']*$options['rate']/100/30,2);
                    M('copartner')->where('uid = '.$v['uid'])->setInc('debo_coins',$income);
                    $debo_coins =  $user_info['debo_coins'] + $income;
                    $this->add_debo_coins_settle_accounts($v['uid'], $v['id'], $debo_coins, $user_info['debo_coins'], $income);
                } else {
                    // 改成没有收益存款
                    $this->set_regular($v['id']);
                }
            } elseif($v['regular'] == 1) {
                if($time > $v['create_time']+24*3600*90) {
                    // 改成没有收益存款
                    $this->set_regular($v['id']);
                    // 金额全部转成余额
                    $this->set_coins($v['num'],$v['uid']);
                } else {
                    if($time > $v['create_time']+24*3600) {
                        // 算日息
                        $income = round($v['num']*$options['three_rate']/100/30,2);
                        M('copartner')->where('uid = '.$v['uid'])->setInc('debo_coins',$income);
                        $debo_coins =  $user_info['debo_coins'] + $income;
                        $this->add_debo_coins_settle_accounts($v['uid'], $v['id'], $debo_coins, $user_info['debo_coins'], $income);
                    }
                }
            } elseif($v['regular'] == 2) {
                if($time > $v['create_time']+24*3600*180) {
                    // 改成没有收益存款
                    $this->set_regular($v['id']);
                    // 金额全部转成余额
                    $this->set_coins($v['num'],$v['uid']);
                } else {
                    if($time > $v['create_time']+24*3600) {
                        // 算日息
                        $income = round($v['num']*$options['six_rate']/100/30,2);
                        M('copartner')->where('uid = '.$v['uid'])->setInc('debo_coins',$income);
                        $debo_coins =  $user_info['debo_coins'] + $income;
                        $this->add_debo_coins_settle_accounts($v['uid'], $v['id'], $debo_coins, $user_info['debo_coins'], $income);
                    }
                }
            } elseif($v['regular'] == 3) {
                if($time > $v['create_time']+24*3600*270) {
                    // 改成没有收益存款
                    $this->set_regular($v['id']);
                    // 金额全部转成余额
                    $this->set_coins($v['num'],$v['uid']);
                } else {
                    if($time > $v['create_time']+24*3600) {
                        // 算日息
                        $income = round($v['num']*$options['nine_rate']/100/30,2);
                        M('copartner')->where('uid = '.$v['uid'])->setInc('debo_coins',$income);
                        $debo_coins =  $user_info['debo_coins'] + $income;
                        $this->add_debo_coins_settle_accounts($v['uid'], $v['id'], $debo_coins, $user_info['debo_coins'], $income);
                    }
                }
            } elseif($v['regular'] == 4) {
                if($time > $v['create_time']+24*3600*365) {
                    // 改成没有收益存款
                    $this->set_regular($v['id']);
                    // 金额全部转成余额
                    $this->set_coins($v['num'],$v['uid']);
                } else {
                    if($time > $v['create_time']+24*3600) {
                        // 算日息
                        $income = round($v['num']*$options['twelve_rate']/100/30,2);
                        M('copartner')->where('uid = '.$v['uid'])->setInc('debo_coins',$income);
                        $debo_coins =  $user_info['debo_coins'] + $income;
                        $this->add_debo_coins_settle_accounts($v['uid'], $v['id'], $debo_coins, $user_info['debo_coins'], $income);
                    }
                }
            }

        }

        // 执行存储款收益
        $calculation_income = new  \Appapi\Controller\PayController();
        $calculation_income->calculation_income();

    }


    public function set_regular($id) {
        M()->execute("UPDATE `de_debo_coins_purchase` SET regular = '-1' WHERE  id= ".$id);
    }

    public function add_debo_coins_settle_accounts($uid, $debo_coins_purchase_id, $debo_coins, $pre_debo_coins, $income) {
        M()->execute("INSERT INTO `de_debo_coins_settle_accounts` (`uid`,`debo_coins_purchase_id`,`debo_coins`,`pre_debo_coins`,`income`,`time`) VALUES ('".$uid."','".$debo_coins_purchase_id."','".$debo_coins."','".$pre_debo_coins."','".$income."','".time()."')");
    }

    public function set_coins($num,$uid) {
        M('user')->where(array('uid'=>$uid))->setInc('coins',$num);
        M('copartner')->where(array('uid'=>$uid))->setDec('debo_coins',$num);

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
         M('transaction')->add($data);
    }
}
?>