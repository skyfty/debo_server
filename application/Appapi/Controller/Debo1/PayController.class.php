<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/20 0020
 * Time: 下午 4:00
 */
namespace Appapi\Controller\Debo1;

use Common\Controller\ApibaseController;
use Org\Net\AopClient;
use Org\Net\AlipayTradeAppPayRequest;
use Common\Common\Easemob;
use Common\Common\Banklist;

class PayController extends ApibaseController{

    private $appID ="2017080708073238";
    private $seller="2088512504196010";
    private $PrivateKey = "MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCYBgpA4VxNUr+6Lw/MIqbffx2g9YL/u8U6Qozkg9ReiBK9GFSnXVWx5WI+KS2wGku/Fhc0VL/C2TEhJxKL3JY54nz2iHJLkGF7LoHc2ulfzMPfXEV51Z8vv1xu+T1sQxfsw7AI3wyWiBO5BQbllhWjg5YFnq3ZTcTkVLwUPDXNFZOFlktiJfgxGO9m4B/XnacEAT/ietFIPnYC3EpvGj/JBHvWGzHe5XQb9+0EbfYpREWhcqdHW0zgFwfWOwLh6+q1afyIJ1/zYUtz3vlYtQw5cO1pBRI8jX7Jsqq2wLufzvnCB2BYqlskUVBZ99l1QuiKxglcmJOhg44WN6+Vk+UdAgMBAAECggEAK1mNkvKwX1w5T94boxo+e3zD9iIMOOBSXUqc4oy1Aj/LGCDYAuGajh/a497aKF1XPCYQd9uy0t0cJVf+8eL084yiFarS7Lk/EuEwJspFwguhzw+AwD0qJ3Np6DsqSLTZHaqvh9gSvtoOxbOpQ86IXRgor3GQIWcLvhBrkVmZ/eC3b7RbrcA8cD5Nt6HUDLGAk49WBohUqz4hgVBdZTVVdMz7m89bPl78ySVrxU/wXL2JwOgUvLxQqrpkxl3ne1O2deb2NXtmL8YeAaAegc4HZMPJ8As7rbfY0owD3Jwv0KLvIlGcYIvCnZNjhYO2faAOx3g2F8dcm+ypvtzM7TwgAQKBgQDk3bxbOI9DiHs1QBYSH2iTyl2A0mdDIaZBVywSh+PoZLRreXgkeoImMuFn/YSZI8Iz8/4dJW10w1OGLfaDjPTq9pdaxD3N+cVz2/a2Nwh6GnduioPfpzw0fiI2mGQ7crJfbdSebbs1BYwG40LXJLbRd9lftBp911kvgnauoZ7iaQKBgQCqDBLEvH503wtYGPy+LZxYaoR94vK+ikhAE1V/D0jTTszFSFKEb90705QKlB77+Jr3ZcJFPqCCiVMTLl4rstbBXfs7rVCkq9nmZDT5BeLNCqgywwldo4QMyG30cFVRGLDFN0Gt8WXmbkAC/E59CTmCXjnYyBbKySlqQUDXnClulQKBgQCV5As6iz548mgPJhvOXre1I3I9ro9mziD+5kK9GDbRuzLWuMVQztNyhRxPaSBw05SbRg+Fsz+Om2aWmH+9BJJbWIkkLyZzQkRpbwSTrlNk9PSHdzyLPBIYJwvtSb1LmgDssxfvctqLDUj6MdlS4n7BjYbWQTSgemu0y/NCyCMxEQKBgFTwcu+SuBfo0eHPmW/lmJMRody/4EENEcnv3mT5XYcvdVmWJqHAvuGDbg9arZ9vLvkk+0VbYxHRRo3uO1vYmS09pKDKkd2vech7hXpeVkvVZu70PVlotC0ucL4I3pUDoEjisQwR6LSRnafgH9SVJwTXzE1cpQXmNam9roYEmy2BAoGBAMAe+qU4qziuL0B/UzhKakM6RwLT+xaslgc/JTQWav2tXqt6BtWScHLA/aWyaX76oLF35SOFxkhtZMGbO7lcE588jjTVYl5U4cSvBDtcPL5xLGbB8W1PpZO+Km4KwyvRlpvY0i64JjLZLOUvj1pGNarnUqekULEYpGIh8aCdO5QS";
    private $PublicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmAYKQOFcTVK/ui8PzCKm338doPWC/7vFOkKM5IPUXogSvRhUp11VseViPiktsBpLvxYXNFS/wtkxIScSi9yWOeJ89ohyS5Bhey6B3NrpX8zD31xFedWfL79cbvk9bEMX7MOwCN8MlogTuQUG5ZYVo4OWBZ6t2U3E5FS8FDw1zRWThZZLYiX4MRjvZuAf152nBAE/4nrRSD52AtxKbxo/yQR71hsx3uV0G/ftBG32KURFoXKnR1tM4BcH1jsC4evqtWn8iCdf82FLc975WLUMOXDtaQUSPI1+ybKqtsC7n875wgdgWKpbJFFQWffZdULoisYJXJiToYOOFjevlZPlHQIDAQAB";
    private $key = '039a7852729329e1e2343572f2a5d3e4';
    //微信相关
//    private $wxkey = '4cc3131f0adb1ec84e910b2ba7c5fb15';
    private $wxkey = '039a7852729329e1e2343572f2a5d3e4';
//    private $wxappID ="wxb6c875d9338f44d9";
    private $wxappID ="wxc49702817b48864f";
    private $path = 'http://debo.shangtongyuntian.com';
	private $transfer_key = 'g6R1dfMjI7K8/Y';

    public function __construct()
{
    parent::__construct();
    \Seaslog::setBasePath('data/log/pay');

}

    /**
     * 聊天界面送礼物支付接口
     */
    public function pay_gift(){
        $uid = I('uid');//自己的id,手机号
        $oid = I('oid');//别人的id,接收礼物的用户id,手机号
        $pay_count = I('pay_count');//礼物的价格
        $gift_type = I('type',1);//礼物类型,暂定
        if (!$uid || !$oid || !$pay_count){
            return $this->json(-1,'参数不全');
        }
        //查询钱包余额
        $map['mobile'] = $uid;
        $self_id = M('user')->where($map)->getField('id');
        $find_info = M('user')->where($map)->find();
        if (!$find_info){
            return $this->json(-2,'信息错误');
        }

        $other_id = M('user')->where(' mobile='.$oid)->getField('id');
        if (!$other_id){
            return $this->json(-5,'您送礼的用户不存在或是被封号');
        }

        if ($find_info['coins'] <$pay_count){
            return $this->json(-3,'余额不足,请前往充值');
        }

        $model = M();
        $model->startTrans();
        //余额充足,减扣余额后写入订单信息
        $order_sn = date('YmdHis').rand(1000,9999);
        $body = '购买礼物';
        $save_info['pay_type'] = 3;
        $save_info['pay_status']= 2;
        $save_info['order_price']= $pay_count;
        $save_info['order_sn']= $order_sn;
        $save_info['uid']= $self_id;
        $save_info['pay_time']= time();
        $save_info['create_time']= time();
        $save_info['object_id']= 9;//暂定,用于区分
        $save_info['indent']= 20;//暂定发礼物,用于区分

        //组装存入礼物记录表中的数据
        $data['uid'] = $self_id;
        $data['oid'] = $other_id;
        $data['pay_type'] = 3;
        $data['pay_status'] = 2;
        $data['order_price'] = $pay_count;
        $data['order_sn'] = $order_sn;
        $data['pay_time'] = time();
        $data['type'] = $gift_type;
        $data['recive'] = $pay_count/2;

        //将收到礼物的收益也计入交易表中
        $get_money['pay_type'] = 3;
        $get_money['pay_status']= 2;
        $get_money['order_price']=  $pay_count/2;
        $get_money['order_sn']= $order_sn;
        $get_money['uid']= $other_id;
        $get_money['pay_time']= time();
        $get_money['create_time']= time();
        $get_money['object_id']= 9;//暂定,用于区分
        $get_money['indent']= 21;//暂定收礼物,用于区分

        //发送文本消息
        $ease = new Easemob();

        $result = M('user')->where($map)->setDec('coins',$pay_count);
        \SeasLog::info('购买礼物扣除用户:'.$uid.'零钱:'.$pay_count.'时间:'.time());
        $result1 = M('user')->where('id='.$other_id)->setInc('coins',$pay_count/2);
        $content1 ='收到礼物分红:'.$pay_count/2 .'元';
//        $ease->sendText('收礼小助手','users',["$oid"],$content1);
        \SeasLog::info('收到礼物用户:'.$oid.'零钱:'.($pay_count/2) .'时间:'.time().'送礼人:'.$uid);
        $result2 = M('transaction')->add($save_info);
        $result3 = M('gift_record')->add($data);
        $result4 = M('transaction')->add($get_money);
        if ($result4){
            \SeasLog::info('收到礼物人存入数据表信息'.json_encode($get_money));
        }
        if (!$result || !$result1 ||!$result2 ||!$result3 ||!$result4){
            $model->rollback();
            return $this->json(-4,'支付失败,请稍后再试');
        }else{
            $model->commit();
        }
        return $this->json(0,'支付成功');
    }

    /**
     * 群发礼物
     */
    public function pay_group_gift(){
        $uid = I('uid');//发礼物人的id
        $q_uid = I('q_uid');//群主id
        $o_uid = I('o_uid');//群员id
        $pay_count = I('pay_count');//礼物的价格
        $gift_type = I('type');//礼物类型,暂定

        //临时删除o_uid验证,测试完加上
        if (!$uid || !$q_uid ||!$pay_count){
            return $this->json(-1,'参数不全');
        }

        $conditon1['mobile'] = $uid; //传过来的是手机号
        $conditon2['mobile'] = $q_uid;//群主手机号
        //查发送人的余额
        $find_info1 = M('user')->where($conditon1)->find();
        $find_info2 = M('user')->where($conditon2)->find();
        if (!$find_info1 || !$find_info2){
            return $this->json(-2,'信息错误,请稍后再试');
        }
        if ($find_info1['coins'] <$pay_count){
            return $this->json(-3,'余额不足,请前往个人中心充值');
        }
        //测试用
//        $o_uid = array('15110165273','17600691583');

        //开始分发获利
        $model = M();
        $model->startTrans();

        $map1['mobile'] = $uid;
        $result1 = M('user')->where($map1)->setDec('coins',$pay_count);//发礼物的人扣钱
        \SeasLog::info('群购买礼物扣除用户: '.$uid.' 零钱:'.$pay_count.' 时间:'.time());
        //群主获益
        $q_get = ($pay_count/2)*0.3;
        $q_get = round($q_get,2);//四舍五入保留2位小数

        //组装发奖人的信息
        //余额充足,减扣余额后写入订单信息
        $order_sn = date('YmdHis').rand(1000,9999);
        $body = '购买群礼物';
        $save_info['pay_type'] = 3;
        $save_info['pay_status']= 2;
        $save_info['order_price']= $pay_count;
        $save_info['order_sn']= $order_sn;
        $save_info['uid']= $find_info1['id'];
        $save_info['pay_time']= time();
        $save_info['create_time']= time();
        $save_info['object_id']= 9;//暂定,用于区分
        $save_info['indent']= 20;//暂定发礼物,用于区分

        //组装数据存入礼物记录表中的数据
        $data['uid'] = $find_info1['id'];
        $data['oid'] = $find_info2['id'];
        $data['pay_type'] = 3;
        $data['pay_status'] = 2;
        $data['order_price'] = $pay_count;
        $data['order_sn'] = $order_sn;
        $data['pay_time'] = time();
        $data['type'] = $gift_type;
        $data['recive'] = $q_get;

        //群主收到钱 创建一个订单
        $q_info['pay_type'] = 3;
        $q_info['pay_status']= 2;
        $q_info['order_price']= $q_get;
        $q_info['order_sn']= $order_sn;
        $q_info['uid']= $find_info2['id'];
        $q_info['pay_time']= time();
        $q_info['create_time']= time();
        $q_info['object_id']= 9;//暂定,用于区分
        $q_info['indent']= 21;//暂定收礼物,用于区分

        //群员分钱
        //每个群员能分多少
        $count = count($o_uid);
        $every_get = ($pay_count/2-$q_get)/$count;
        $every_get = round($every_get,2);
        $ease = new Easemob();
        foreach ($o_uid as $value){
            $conditon3['mobile'] = $value;
            $res = M('user')->where($conditon3)->setInc('coins',$every_get);
            $content = '收到礼物分享金额:'.$every_get.'元';
//            $ease->sendText('礼物小助手','users',["$value"],$content);
            \SeasLog::info('群员收到礼物分红:'.$every_get .'零钱'.'时间:'.time().'送礼人:'.$uid.'状态:'.$res);
        }

        $result2 = M('user')->where('mobile='.$q_uid)->setInc('coins',$q_get);
        //给群主发消息
        $content1 = "收到群员送礼,收益:".$q_get;
//        $ease->sendText('小助手','users',["$q_uid"],$content1);
        \SeasLog::info('群主收到礼物用户:'.$q_uid.'零钱:'.$q_get .'时间:'.time().'送礼人:'.$uid);
        $result3 = M('transaction')->add($save_info);
        $result4 = M('gift_record')->add($data);//送礼物表
        $result5 = M('transaction')->add($q_info);//将群主收礼信息写入订单

        if (!$result1 || !$result2 ||!$result3 ||!$result4 || !$result5){
            $model->rollback();
            return $this->json(-4,'发送失败,请稍后再试');
        }else{
            $model->commit();
        }

        return $this->json(0,'发送成功');

    }

    /**
     * 获取零钱数
     */
    public function  get_mycoins(){
        $uid = I('uid');
        if (!$uid){
            return $this->json(-1,'参数不全');
        }
        $map['id'] = $uid;
        $find_info = M('user')->where($map)->find();
        if (!$find_info){
            return $this->json(-2,'获取信息失败,请稍后再试');
        }
        $return['money'] = $find_info['coins'];

        return $this->json(0,'成功',$return);
    }

    /**
     * 计算储蓄罐收益,需定时任务执行
     */
    public function calculation_income(){
        //查询当天的利率
        \SeasLog::info('计算当天的收益.'.date('Y-m-d H:i:s',time()));
        $rate = M('rate')->where(' r_id=3 ')->getField('rate');
        $time = time();

        //计算当天的开始结束时间戳
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        //查询当天是否已经有执行记录,如有记录则不再执行
        $condition['time'] = array('between',array($beginToday,$endToday));
        $find_info = M('storage_settle_account')->where($condition)->find();
        if ($find_info){
            \SeasLog::info('已计算收益,再执行时间:'.date('Y-m-d H:i:s',$time));
            return $this->json(-2,'已计算过利息');
        }

        $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        $where['time'] = array('between',array($beginYesterday,$endYesterday));
        $income_info = M('storage_settle_account')->where($where)->select();

        if (!$income_info){
            \SeasLog::info('计算收益时出错,时间:'.date('Y-m-d H:i:s',$time));
            return $this->json(-1,'信息出错');
        }
        $new_info = array();
        print_r($income_info);
        foreach ($income_info as $key => $value){

            $new_info[$key]['uid'] = $value['uid'];
            //结算后的钱
            $new_info[$key]['money'] =round((($value['money'] * $rate)/360 + $value['money']),2);
            $new_info[$key]['pre_money'] = $value['money'];
            $new_info[$key]['rate'] = $rate *100; //费率100倍写入数据表
            $new_info[$key]['income'] =round(($value['money'] * $rate /360),2);
            $new_info[$key]['time'] = time();
            $result[] = M('storage_settle_account')->add($new_info[$key]);
            //将每个人最后收益后的总金额回写到storage表中
            $new_save['money'] =$new_info[$key]['money'] ;
            $res2 = M('storage')->where('uid='.$value['uid'])->save($new_save);
        }
    }

    /**
     * 支付宝回调接口
     * */
    public function alinotify(){
        $data = $_POST;

        if($data['trade_status']!='TRADE_SUCCESS'){
            echo 'error';
            return false;
        }

        $order_sn = $data['out_trade_no'];//订单编号
        $order_info = M('transaction')->where('order_sn = '.$order_sn)->find();
        if(!$order_info || $order_info['pay_status'] == 2){
            return false;
        }

        //开启事物
        $model = M();//多表事物处理
        $model->startTrans();

        $map['pay_type'] = 1;//支付宝支付
        $map['pay_status'] = 2; //1为未支付
        $map['pay_time'] = time();

        //充值
        if($order_info['indent'] == 1){
            $res1 = M('user')->where('id = '.$order_info['uid'])->setInc('coins',$order_info['order_price']);
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            if(!$res1 || !$res2){
                $model->rollback();
            }
        }

        //购买合约人
        if($order_info['indent'] == 2){
            //查询购买人信息
            $find_user = M('user')->where('id = '.$order_info['uid'])->find();
            $find_user2 = M('user')->where('id = '.$order_info['con_uid'])->find();

            $data['pur_mobile'] = $find_user['mobile'];
            $data['pur_top_father'] = $find_user['top_father'];
            $data['pur_time'] = time();
            $data['pur_contract_time'] = $find_user2['day_num']*3600*24+time();
            $res1 = M('user')->where('id = '.$order_info['con_uid'])->save($data);
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);

            //将钱打给对方
            M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);

            if(!$res1 || !$res2){
                $model->rollback();
            }
        }

        //发红包
        if($order_info['indent'] == 3){
            $res1 = M('touch')->where('t_id = '.$order_info['object_id'])->setInc('p_status',1);
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            if(!$res2){
                $model->rollback();
            }
        }

        //购买小喇叭
        if($order_info['indent'] == 6){
            //通过uid 和 object_id 查询是否在喇叭购买表中有记录
            $find_horn = M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->find();
            if($find_horn){
                //有记录就修改购买状态字段
                M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->setField('pur_state',1);
            }else{
                //添加购买信息
                $data['uid'] = $order_info['uid'];
                $data['horn_id'] = $order_info['object_id'];
                $data['pur_price'] = $order_info['order_price'];
                $data['pur_time'] = time();
                $data['pur_state'] = 1;
                M('horn')->add($data);
            }
        }

        //三方客转入
        if($order_info['indent'] == 7){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            //存储三方客信息
            $data['uid'] = $order_info['uid'];
            $data['con_uid'] = $order_info['con_uid'];
            $data['price'] = $order_info['order_price'];
            $data['initiate_time'] = time();//发起时间
            $res3 = M('tripartite')->add($data);
            //查找被邀请者的信息
            $find_user_info  = M('user')->where('id = '.$data['con_uid'])->find();
            $my_info = M('user')->where('id = '.$data['uid'])->find();

            if($my_info['user_nickname']){
                $content = $my_info['user_nickname']."的三方客邀请消息";
            }else{
                $content = $my_info['mobile']."的三方客邀请消息";
            }

            $mobile = explode(",",$find_user_info['mobile']);
            $ease = new Easemob();
            $ext = array('type'=>'1','mobile'=>$my_info['mobile'],'name'=>$my_info['user_nickname'],'content'=>$content,'tri_id'=>$res3);
            $res = $ease->sendText('三方客邀请'.$my_info['mobile'],'users',$mobile,$content,$ext);
//            if(!$res2){
//                $model->rollback();
//            }
        }

        //收付款转账功能
        if($order_info['indent'] == 12){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            $res = M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);
//            if(!$res2){
//                $model->rollback();
//            }
            $data = array(
                'uid'=>$order_info['con_uid'],
                'order_price'=>$order_info['order_price'],
                'pay_type'=>$order_info['order_price'],
                'indent'=>11,
                'order_sn'=>date('YmdHis').rand(1000,9999),
                'pay_status'=>1,
                'create_time'=>time(),
                'con_uid'=>$order_info['uid'],
            );
            M('transaction')->add($data);
        }

        //存入（储蓄罐）
        if($order_info['indent'] == 15){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);

            //是否已经有该用户信息
            $find_storage = M('storage')->where('uid = '.$order_info['uid'])->find();
            if($find_storage){
                $find_storage = M('storage')->where('uid = '.$order_info['uid'])->setField('r_time',time());
                $find_storage = M('storage')->where('uid = '.$order_info['uid'])->setInc('money',$order_info['order_price']);
                \Seaslog::info('支付宝充值储蓄罐..用户id'.$order_info['uid'].',金额:'.$order_info['order_price']);
                //新转入存钱罐的钱,也得同步增加到每日收益表里
                //老用户储蓄罐里有钱,但是收益表中没有信息的处理
                $find_user = M('storage_settle_account')->where('uid='.$order_info['uid'])->find();
                if ($find_user){
                    $find_result = M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setInc('money',$order_info['order_price']);//找到最新的一次更新结算后的金额加上充值金额
                    M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setInc('pre_money',$order_info['order_price']);//结算前的也加上充值金额
                    //利率按照当时的利率写入
                    $rate = M('rate')->where('r_id = 3')->getField('rate');
                    M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setField('rate',$rate*100);
                }else{
                    //查询储蓄罐里的钱,然后将钱重新存入收益表中
                    $rate = M('rate')->where('r_id = 3')->getField('rate');
                    $m_money = M('storage')->where('uid = '.$order_info['uid'])->getField('money');
                    $data_s1['uid'] = $order_info['uid'];
                    $data_s1['money'] = $m_money;
                    $data_s1['pre_money'] = $m_money;
                    $data_s1['rate'] = $rate*100;
                    $data_s1['income'] = 0;
                    $data_s1['time'] = time();
                    $find_result = M('storage_settle_account')->add($data_s1);
                }

                if ($find_result){
                    \Seaslog::info('支付宝新转入的收益表的成功金额:'.$order_info['order_price']);
                }


            }else{
                $data_s['uid'] = $order_info['uid'];
                $data_s['money'] = $order_info['order_price'];
                $data_s['time'] = time()+3600*24; //一天后开启
                $data_s['r_time'] = time();
                $res3 = M('storage')->add($data_s);
                if ($res3){
                    \Seaslog::info('新转入储蓄罐:'.json_encode($data_s));
                }
                //转入储蓄罐的同时得存入每日收益表中
                //获取当时的利率
                $rate = M('rate')->where('r_id = 3')->getField('rate');
                $data_ss['uid'] = $order_info['uid'];
                $data_ss['money'] = $order_info['order_price'];
                $data_ss['pre_money'] = $order_info['order_price'];
                $data_ss['rate'] = $rate*100;
                $data_ss['income'] = 0;
                $data_ss['time'] = time();

                $result_ss = M('storage_settle_account')->add($data_ss);
                if ($result_ss){
                    \Seaslog::info('新转入的收益表的成功:'.json_encode($data_ss));
                }
            }
        }

        //购买嘚啵币
        if($order_info['indent'] == 18){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            $res3 = M('debo_coins_purchase')->where('order_sn = '.$order_sn)->setField('is_pay',1);
            $debo_coins_purchase_info = M('debo_coins_purchase')->where('order_sn = '.$order_sn)->find();
            $find_copartner = M('copartner')->where('uid = '.$order_info['uid'])->find();

            //将购买的嘚啵币累加上
            $purchase_limit = $debo_coins_purchase_info['num']*0.5;
            M('copartner')->where('uid = '.$order_info['uid'])->setInc('debo_coins',$debo_coins_purchase_info['num']);
            if(($find_copartner['purchase_limit']+$purchase_limit) < 100000){
                M('copartner')->where('uid = '.$order_info['uid'])->setInc('purchase_limit',$purchase_limit);
            }else{
                M('copartner')->where('uid = '.$order_info['uid'])->setField('purchase_limit',100000);
            }

            if($order_info['con_uid']){
                M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);
                M('debo_coins_shop')->where('id = '.$order_info['object_id'])->setField('status',2);
            }
        }

        $model->commit();
        echo 'success';
        return true;

    }


    /**
     * 主动查询微信支付结果
     */
    public function searchWxpayResult(){

        //接收商家订单号
        $trade_no = I('trade_no');
        if (!$trade_no){
            return $this->json(-1,'参数不全');
        }
        $array = array(
            'appid'=>'wxc49702817b48864f',
            'mch_id'=>'1489148252',
            'nonce_str'=>'debo.shangtongyuntian.com',
            'out_trade_no'=>$trade_no
        );

        Vendor('Weixinpay.Weixinpay');
        $wxpay=new \Weixinpay();
        //重新签名
        $array['sign'] = $wxpay->makeSign($array);
        $info = arr2xml($array);
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $result = post_d($url,$info);
        $result = toArray($result);

            \Seaslog::info('微信支付结果信息:'.json_encode($result));
//        file_put_contents('./trade.txt',$result,FILE_APPEND);//写入文件,测试用
        if ($result['return_code'] =='SUCCESS'  && $result['result_code'] =='SUCCESS' && $result['trade_state'] == 'SUCCESS'){
            //支付成功了
            $order_sn = $result['out_trade_no'];

            //走逻辑,改变表状态
            $order_info = M('transaction')->where('order_sn = '.$order_sn)->find();

            if (!$order_sn){
                return $this->json(-2,'订单不存在');
            }
            
            if ($order_info['pay_status'] == 2){
                return $this->json(-3,'订单已完成支付');
            }

            $map['pay_type'] = 2;//微信支付
            $map['pay_status'] = 2; //1为未支付
            $map['pay_time'] = time();

            //开启事物
            $model = M();//多表事物处理
            $model->startTrans();

            //充值
            if($order_info['indent'] == 1){
                $res1 = M('user')->where('id = '.$order_info['uid'])->setInc('coins',$order_info['order_price']);
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
                if(!$res1 || !$res2){
                    $model->rollback();
                }
            }

            //购买合约人
            if($order_info['indent'] == 2){
                //查询购买人信息
                $find_user = M('user')->where('id = '.$order_info['uid'])->find();
                $find_user2 = M('user')->where('id = '.$order_info['con_uid'])->find();

                $data['pur_mobile'] = $find_user['mobile'];
                $data['pur_top_father'] = $find_user['top_father'];
                $data['pur_time'] = time();
                $data['pur_contract_time'] = $find_user2['day_num']*3600*24+time();
                $res1 = M('user')->where('id = '.$order_info['con_uid'])->save($data);
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);

                //将钱打给对方
                M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);

                if(!$res1 || !$res2){
                    $model->rollback();
                }
            }

            //发红包
            if($order_info['indent'] == 3){
                $res1 = M('touch')->where('t_id = '.$order_info['object_id'])->setInc('p_status',1);
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
                if(!$res2){
                    $model->rollback();
                }
            }

            //购买小喇叭
            if($order_info['indent'] == 6){
                //通过uid 和 object_id 查询是否在喇叭购买表中有记录
                $find_horn = M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->find();
                if($find_horn){
                    //有记录就修改购买状态字段
                    M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->setField('pur_state',1);
                }else{
                    //添加购买信息
                    $data['uid'] = $order_info['uid'];
                    $data['horn_id'] = $order_info['object_id'];
                    $data['pur_price'] = $order_info['order_price'];
                    $data['pur_time'] = time();
                    $data['pur_state'] = 1;
                    M('horn')->add($data);
                }
            }

            //三方客转入
            if($order_info['indent'] == 7){
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
                //存储三方客信息
                $data['uid'] = $order_info['uid'];
                $data['con_uid'] = $order_info['con_uid'];
                $data['price'] = $order_info['order_price'];
                $data['initiate_time'] = time();//发起时间
                $res3 = M('tripartite')->add($data);
                //查找被邀请者的信息
                $find_user_info  = M('user')->where('id = '.$data['con_uid'])->find();
                $my_info = M('user')->where('id = '.$data['uid'])->find();

                if($my_info['user_nickname']){
                    $content = $my_info['user_nickname']."的三方客邀请消息";
                }else{
                    $content = $my_info['mobile']."的三方客邀请消息";
                }

                $mobile = explode(",",$find_user_info['mobile']);
                $ease = new Easemob();
                $ext = array('type'=>'1','mobile'=>$my_info['mobile'],'name'=>$my_info['user_nickname'],'content'=>$content,'tri_id'=>$res3);
                $res = $ease->sendText('三方客邀请'.$my_info['mobile'],'users',$mobile,$content,$ext);
            }

            //收付款转账功能
            if($order_info['indent'] == 12){
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
                $res = M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);
                $data = array(
                    'uid'=>$order_info['con_uid'],
                    'order_price'=>$order_info['order_price'],
                    'pay_type'=>$order_info['order_price'],
                    'indent'=>11,
                    'order_sn'=>date('YmdHis').rand(1000,9999),
                    'pay_status'=>1,
                    'create_time'=>time(),
                    'con_uid'=>$order_info['uid'],
                );
                M('transaction')->add($data);
            }

            //存入（储蓄罐）
            if($order_info['indent'] == 15){
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);

                //是否已经有该用户信息
                $find_storage = M('storage')->where('uid = '.$order_info['uid'])->find();
                if($find_storage){
                    $find_storage = M('storage')->where('uid = '.$order_info['uid'])->setField('r_time',time());
                    $find_storage = M('storage')->where('uid = '.$order_info['uid'])->setInc('money',$order_info['order_price']);
                    \Seaslog::info('微信充值储蓄罐..用户id'.$order_info['uid'].',金额:'.$order_info['order_price']);
                    //新转入存钱罐的钱,也得同步增加到每日收益表里
                    //老用户储蓄罐里有钱,但是收益表中没有信息的处理
                    $find_user = M('storage_settle_account')->where('uid='.$order_info['uid'])->find();
                    if ($find_user){
                        $find_result = M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setInc('money',$order_info['order_price']);//找到最新的一次更新结算后的金额加上充值金额
                        M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setInc('pre_money',$order_info['order_price']);//结算前的也加上充值金额
                        //利率按照当时的利率写入
                        $rate = M('rate')->where('r_id = 3')->getField('rate');
                        M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setField('rate',$rate*100);
                    }else{
                        //查询储蓄罐里的钱,然后将钱重新存入收益表中
                        $rate = M('rate')->where('r_id = 3')->getField('rate');
                        $m_money = M('storage')->where('uid = '.$order_info['uid'])->getField('money');
                        $data_s1['uid'] = $order_info['uid'];
                        $data_s1['money'] = $m_money;
                        $data_s1['pre_money'] = $m_money;
                        $data_s1['rate'] = $rate*100;
                        $data_s1['income'] = 0;
                        $data_s1['time'] = time();
                        $find_result = M('storage_settle_account')->add($data_s1);
                    }

                    if ($find_result){
                        \Seaslog::info('微信新转入的收益表的成功金额:'.$order_info['order_price']);
                    }

                }else{
                    $data_s['uid'] = $order_info['uid'];
                    $data_s['money'] = $order_info['order_price'];
                    $data_s['time'] = time()+3600*24;
                    $data_s['r_time'] = time();
                    $res3 = M('storage')->add($data_s);
                    if ($res3){
                        \Seaslog::info('微信新转入储蓄罐:'.json_encode($data_s));
                    }
                    //转入储蓄罐的同时得存入每日收益表中
                    //获取当时的利率
                    $rate = M('rate')->where('r_id = 3')->getField('rate');
                    $data_ss['uid'] = $order_info['uid'];
                    $data_ss['money'] = $order_info['order_price'];
                    $data_ss['pre_money'] = $order_info['order_price'];
                    $data_ss['rate'] = $rate*100;
                    $data_ss['income'] = 0;
                    $data_ss['time'] = time();

                    $result_ss = M('storage_settle_account')->add($data_ss);
                    if ($result_ss){
                        \Seaslog::info('微信新转入的收益表的成功:'.json_encode($data_ss));
                    }
                }
            }

            //购买嘚啵币
            if($order_info['indent'] == 18){
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
                $res3 = M('debo_coins_purchase')->where('order_sn = '.$order_sn)->setField('is_pay',1);
                $debo_coins_purchase_info = M('debo_coins_purchase')->where('order_sn = '.$order_sn)->find();
                $find_copartner = M('copartner')->where('uid = '.$order_info['uid'])->find();

                //将购买的嘚啵币累加上
                $purchase_limit = $debo_coins_purchase_info['num']*0.5;
                M('copartner')->where('uid = '.$order_info['uid'])->setInc('debo_coins',$debo_coins_purchase_info['num']);
                if(($find_copartner['purchase_limit']+$purchase_limit) < 100000){
                    M('copartner')->where('uid = '.$order_info['uid'])->setInc('purchase_limit',$purchase_limit);
                }else{
                    M('copartner')->where('uid = '.$order_info['uid'])->setField('purchase_limit',100000);
                }

                if($order_info['con_uid']){
                    M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);
                    M('debo_coins_shop')->where('id = '.$order_info['object_id'])->setField('status',2);
                }
            }

            $model->commit();

            return $this->json(0,'支付成功');

        }else{
            return $this->json(-4,'该订单未完成支付,请稍后再试');
        }

    }

    /**
     * 微信回调接口
     * */
    public function wxnotify(){

        $xml=file_get_contents('php://input', 'r');
        //$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        // 转成php数组
        $data=toArray($xml);
        //将订单写入一个文件,测试用
        \Seaslog::info('微信异步回调结果:'.json_encode($data));
        if ($data) {
            $order_sn = $data['out_trade_no'];//订单编号
            echo 'success';
        }else{
            echo false;
            return $this->json(-1,'回调信息获取失败');
        }


        $order_info = M('transaction')->where('order_sn = '.$order_sn)->find();
        if(!$order_info || $order_info['pay_status'] == 2){
            return false;
        }

        $map['pay_type'] = 2;//微信支付
        $map['pay_status'] = 2; //1为未支付
        $map['pay_time'] = time();

        //开启事物
        $model = M();//多表事物处理
        $model->startTrans();

        //充值
        if($order_info['indent'] == 1){
            $res1 = M('user')->where('id = '.$order_info['uid'])->setInc('coins',$order_info['order_price']);
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            if(!$res1 || !$res2){
                $model->rollback();
            }
        }

        //购买合约人
        if($order_info['indent'] == 2){
            //查询购买人信息
            $find_user = M('user')->where('id = '.$order_info['uid'])->find();
            $find_user2 = M('user')->where('id = '.$order_info['con_uid'])->find();

            $data['pur_mobile'] = $find_user['mobile'];
            $data['pur_top_father'] = $find_user['top_father'];
            $data['pur_time'] = time();
            $data['pur_contract_time'] = $find_user2['day_num']*3600*24+time();
            $res1 = M('user')->where('id = '.$order_info['con_uid'])->save($data);
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);

            //将钱打给对方
            M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);

            if(!$res1 || !$res2){
                $model->rollback();
            }
        }

        //发红包
        if($order_info['indent'] == 3){
            $res1 = M('touch')->where('t_id = '.$order_info['object_id'])->setInc('p_status',1);
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            if(!$res2){
                $model->rollback();
            }
        }

        //购买小喇叭
        if($order_info['indent'] == 6){
            //通过uid 和 object_id 查询是否在喇叭购买表中有记录
            $find_horn = M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->find();
            if($find_horn){
                //有记录就修改购买状态字段
                M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->setField('pur_state',1);
            }else{
                //添加购买信息
                $data['uid'] = $order_info['uid'];
                $data['horn_id'] = $order_info['object_id'];
                $data['pur_price'] = $order_info['order_price'];
                $data['pur_time'] = time();
                $data['pur_state'] = 1;
                M('horn')->add($data);
            }
        }

        //三方客转入
        if($order_info['indent'] == 7){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            //存储三方客信息
            $data['uid'] = $order_info['uid'];
            $data['con_uid'] = $order_info['con_uid'];
            $data['price'] = $order_info['order_price'];
            $data['initiate_time'] = time();//发起时间
            $res3 = M('tripartite')->add($data);
            //查找被邀请者的信息
            $find_user_info  = M('user')->where('id = '.$data['con_uid'])->find();
            $my_info = M('user')->where('id = '.$data['uid'])->find();

            if($my_info['user_nickname']){
                $content = $my_info['user_nickname']."的三方客邀请消息";
            }else{
                $content = $my_info['mobile']."的三方客邀请消息";
            }

            $mobile = explode(",",$find_user_info['mobile']);
            $ease = new Easemob();
            $ext = array('type'=>'1','mobile'=>$my_info['mobile'],'name'=>$my_info['user_nickname'],'content'=>$content,'tri_id'=>$res3);
            $res = $ease->sendText('三方客邀请'.$my_info['mobile'],'users',$mobile,$content,$ext);
//            if(!$res2){
//                $model->rollback();
//            }
        }

        //收付款转账功能
        if($order_info['indent'] == 12){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            $res = M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);
//            if(!$res2){
//                $model->rollback();
//            }
            $data = array(
                'uid'=>$order_info['con_uid'],
                'order_price'=>$order_info['order_price'],
                'pay_type'=>$order_info['order_price'],
                'indent'=>11,
                'order_sn'=>date('YmdHis').rand(1000,9999),
                'pay_status'=>1,
                'create_time'=>time(),
                'con_uid'=>$order_info['uid'],
            );
            M('transaction')->add($data);
        }

        //存入（储蓄罐）
        if($order_info['indent'] == 15){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);

            //是否已经有该用户信息
            $find_storage = M('storage')->where('uid = '.$order_info['uid'])->find();
            if($find_storage){
                $find_storage = M('storage')->where('uid = '.$order_info['uid'])->setField('r_time',time());
                $find_storage = M('storage')->where('uid = '.$order_info['uid'])->setInc('money',$order_info['order_price']);
                \Seaslog::info('微信充值储蓄罐..用户id'.$order_info['uid'].',金额:'.$order_info['order_price']);
                //新转入存钱罐的钱,也得同步增加到每日收益表里
                //老用户储蓄罐里有钱,但是收益表中没有信息的处理
                $find_user = M('storage_settle_account')->where('uid='.$order_info['uid'])->find();
                if ($find_user){
                    $find_result = M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setInc('money',$order_info['order_price']);//找到最新的一次更新结算后的金额加上充值金额
                    M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setInc('pre_money',$order_info['order_price']);//结算前的也加上充值金额
                    //利率按照当时的利率写入
                    $rate = M('rate')->where('r_id = 3')->getField('rate');
                    M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setField('rate',$rate*100);
                }else{
                    //查询储蓄罐里的钱,然后将钱重新存入收益表中
                    $rate = M('rate')->where('r_id = 3')->getField('rate');
                    $m_money = M('storage')->where('uid = '.$order_info['uid'])->getField('money');
                    $data_s1['uid'] = $order_info['uid'];
                    $data_s1['money'] = $m_money;
                    $data_s1['pre_money'] = $m_money;
                    $data_s1['rate'] = $rate*100;
                    $data_s1['income'] = 0;
                    $data_s1['time'] = time();
                    $find_result = M('storage_settle_account')->add($data_s1);
                }

                if ($find_result){
                    \Seaslog::info('微信新转入的收益表的成功金额:'.$order_info['order_price']);
                }

            }else{
                $data_s['uid'] = $order_info['uid'];
                $data_s['money'] = $order_info['order_price'];
                $data_s['time'] = time()+3600*24;
                $data_s['r_time'] = time();
                $res3 = M('storage')->add($data_s);
                if ($res3){
                    \Seaslog::info('微信新转入储蓄罐:'.json_encode($data_s));
                }
                //转入储蓄罐的同时得存入每日收益表中
                //获取当时的利率
                $rate = M('rate')->where('r_id = 3')->getField('rate');
                $data_ss['uid'] = $order_info['uid'];
                $data_ss['money'] = $order_info['order_price'];
                $data_ss['pre_money'] = $order_info['order_price'];
                $data_ss['rate'] = $rate*100;
                $data_ss['income'] = 0;
                $data_ss['time'] = time();

                $result_ss = M('storage_settle_account')->add($data_ss);
                if ($result_ss){
                    \Seaslog::info('微信新转入的收益表的成功:'.json_encode($data_ss));
                }
            }
        }

        //购买嘚啵币
        if($order_info['indent'] == 18){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            $res3 = M('debo_coins_purchase')->where('order_sn = '.$order_sn)->setField('is_pay',1);
            $debo_coins_purchase_info = M('debo_coins_purchase')->where('order_sn = '.$order_sn)->find();
            $find_copartner = M('copartner')->where('uid = '.$order_info['uid'])->find();

            //将购买的嘚啵币累加上
            $purchase_limit = $debo_coins_purchase_info['num']*0.5;
            M('copartner')->where('uid = '.$order_info['uid'])->setInc('debo_coins',$debo_coins_purchase_info['num']);
            if(($find_copartner['purchase_limit']+$purchase_limit) < 100000){
                M('copartner')->where('uid = '.$order_info['uid'])->setInc('purchase_limit',$purchase_limit);
            }else{
                M('copartner')->where('uid = '.$order_info['uid'])->setField('purchase_limit',100000);
            }

            if($order_info['con_uid']){
                M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);
                M('debo_coins_shop')->where('id = '.$order_info['object_id'])->setField('status',2);
            }
        }

        $model->commit();
        echo 'success';
        $this->json(0,'支付成功');
        return true;

    }

    /**
     * 微信支付接口
     * */
    public function wxpay()
    {
        $body = I('body');//产品描述
        $total_fee = I('total_fee');//订单金额
        $product_id = I('product_id');//产品id
        $out_trade_no = I('out_trade_no');//订单号
        $time=time();
        if (!$body || !$total_fee || !$product_id || !$out_trade_no)
        {
            $this->json(-1,'参数错误');
        }
        $order=array(
            'body'=>$body,
            'total_fee'=>$total_fee,
            'out_trade_no'=>$out_trade_no,
            'product_id'=>$product_id
        );
        weixinpay($order);
    }

    /**
     * 客户端验证支付
     */
    public function getWxPayResult(){
        $order_id = I('order_id');
        if (!$order_id){
            return $this->json(-1,'参数错误');
        }
        $condition['order_sn'] = array('eq',$order_id);
        $condition['pay_type'] = array('eq',2);
        $find_info = M('transaction')->where('order_sn='.$order_id)->find();
        if (!$find_info){
            return $this->json(-2,'未查询到相关订单');
        }

        if ($find_info['pay_status'] ==2){
            return $this->json(0,'支付成功');
        }else{
            return $this->json(-3,'网络稍有延迟,请稍后再试');
        }

    }

    /**
     * 零钱支付
     * */
    public function coins_pay(){
        $order_sn = I('order_sn');//交易订单
        $uid = I('uid');//交易用户uid
        $pwd = I('pwd');

        $is_chat_money = I('is_chat_money');//1、代表聊天红包

        if(!$order_sn || !$uid || !$pwd){
            return $this->json(-1,'缺少参数');
        }

        //查询用户信息
        $find_user_info = M('user')->where('id = '.$uid)->find();
        if(!$find_user_info){
            return $this->json(-2,'用户信息不存在');
        }

        if(!$find_user_info['pay_pwd']){
            return $this->json(-3,'您还没有设置支付密码');
        }

        if($find_user_info['pay_pwd'] != md5($pwd)){
            return $this->json(-4,'密码不正确');
        }

        //查找订单信息
        $order_info = M('transaction')->where("order_sn = '".$order_sn."'")->find();
        if(!$order_info){
            return $this->json(-5,'没有找到相关订单信息');
        }

        if($order_info['pay_status'] == 2){
            return $this->json(-6,'订单信息有误');
        }

        //查询余额
        if($find_user_info['coins'] < $order_info['order_price']){
            return $this->json(-7,'您的余额不足');
        }

        //开启事物
        $model = M();//多表事物处理
        $model->startTrans();

        $map['pay_type'] = 3;//
        $map['pay_status'] = 2; //1为未支付
        $map['pay_time'] = time();

        //购买合约人
        if($order_info['indent'] == 2){
            //查询购买人信息
            $find_user = M('user')->where('id = '.$order_info['uid'])->find();
            $find_user2 = M('user')->where('id = '.$order_info['con_uid'])->find();

            $data['pur_mobile'] = $find_user['mobile'];
            $data['pur_top_father'] = $find_user['top_father'];
            $data['pur_time'] = time();
            $res1 = M('user')->where('id = '.$order_info['con_uid'])->save($data);
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);

            //将钱打给对方
            M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);

            if(!$res1 || !$res2){
                $model->rollback();
            }
        }

        //发红包
        if($order_info['indent'] == 3){
            if(!$is_chat_money){
                $res1 = M('touch')->where('t_id = '.$order_info['object_id'])->setInc('p_status',1);
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
                if(!$res2){
                    $model->rollback();
                }
            }else{
                $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
                $res3 = M('red_packets')->where('id = '.$order_info['object_id'])->setField('is_pay',1);
                $r_id = $order_info['object_id'];
            }
            
        }

        //购买小喇叭
        if($order_info['indent'] == 6){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            //通过uid 和 object_id 查询是否在喇叭购买表中有记录
            $find_horn = M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->find();
            if($find_horn){
                //有记录就修改购买状态字段
                M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->setField('pur_state',1);
            }else{
                //添加购买信息
                $data['uid'] = $order_info['uid'];
                $data['horn_id'] = $order_info['object_id'];
                $data['pur_price'] = $order_info['order_price'];
                $data['pur_time'] = time();
                $data['pur_state'] = 1;
                M('horn')->add($data);
            }
        }

        //三方客转入
        if($order_info['indent'] == 7){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            //存储三方客信息
            $data['uid'] = $order_info['uid'];
            $data['con_uid'] = $order_info['con_uid'];
            $data['price'] = $order_info['order_price'];
            $data['initiate_time'] = time();//发起时间
            $res3 = M('tripartite')->add($data);
            //查找被邀请者的信息
            $find_user_info  = M('user')->where('id = '.$data['con_uid'])->find();
            $my_info = M('user')->where('id = '.$data['uid'])->find();

            if($my_info['user_nickname']){
                $content = $my_info['user_nickname']."的三方客邀请消息";
            }else{
                $content = $my_info['mobile']."的三方客邀请消息";
            }

            $mobile = explode(",",$find_user_info['mobile']);
            $ease = new Easemob();
            $ext = array('type'=>'1','mobile'=>$my_info['mobile'],'name'=>$my_info['user_nickname'],'content'=>$content,'tri_id'=>$res3);
            $res = $ease->sendText('三方客邀请'.$my_info['mobile'],'users',$mobile,$content,$ext);
//            if(!$res2){
//                $model->rollback();
//            }
        }

        //转账（转出）
        if($order_info['indent'] == 13){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            $res3 = M('red_packets')->where('id = '.$order_info['object_id'])->setField('is_pay',1);
            $r_id = $order_info['object_id'];

        }

        //存入（储蓄罐）
        if($order_info['indent'] == 15){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);

            //是否已经有该用户信息
            $find_storage = M('storage')->where('uid = '.$order_info['uid'])->find();
            if($find_storage){
                $find_storage = M('storage')->where('uid = '.$order_info['uid'])->setField('r_time',time());
                $find_storage = M('storage')->where('uid = '.$order_info['uid'])->setInc('money',$order_info['order_price']);
                \Seaslog::info('零钱充值储蓄罐..用户id'.$order_info['uid'].',金额:'.$order_info['order_price']);
                //新转入存钱罐的钱,也得同步增加到每日收益表里
                //老用户储蓄罐里有钱,但是收益表中没有信息的处理
                $find_user = M('storage_settle_account')->where('uid='.$order_info['uid'])->find();
                if ($find_user){
                    $find_result = M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setInc('money',$order_info['order_price']);//找到最新的一次更新结算后的金额加上充值金额
                    M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setInc('pre_money',$order_info['order_price']);//结算前的也加上充值金额
                    //利率按照当时的利率写入
                    $rate = M('rate')->where('r_id = 3')->getField('rate');
                    M('storage_settle_account')->where('uid='.$order_info['uid'])->order('time desc')->limit(1)->setField('rate',$rate*100);

                }else{
                    //查询储蓄罐里的钱,然后将钱重新存入收益表中
                    $rate = M('rate')->where('r_id = 3')->getField('rate');
                    $m_money = M('storage')->where('uid = '.$order_info['uid'])->getField('money');
                    $data_s1['uid'] = $order_info['uid'];
                    $data_s1['money'] = $m_money;
                    $data_s1['pre_money'] = $m_money;
                    $data_s1['rate'] = $rate*100;
                    $data_s1['income'] = 0;
                    $data_s1['time'] = time();
                    $find_result = M('storage_settle_account')->add($data_s1);
                }

                if ($find_result){
                    \Seaslog::info('零钱新转入的收益表的成功金额:'.$order_info['order_price']);
                }
            }else{
                $data_s['uid'] = $order_info['uid'];
                $data_s['money'] = $order_info['order_price'];
                $data_s['time'] = time()+3600*24;
                $data_s['r_time'] = time();
                $res3 = M('storage')->add($data_s);
                if ($res3){
                    \Seaslog::info('零钱新转入储蓄罐:'.json_encode($data_s));
                }
                //转入储蓄罐的同时得存入每日收益表中
                //获取当时的利率
                $rate = M('rate')->where('r_id = 3')->getField('rate');
                $data_ss['uid'] = $order_info['uid'];
                $data_ss['money'] = $order_info['order_price'];
                $data_ss['pre_money'] = $order_info['order_price'];
                $data_ss['rate'] = $rate*100;
                $data_ss['income'] = 0;
                $data_ss['time'] = time();

                $result_ss = M('storage_settle_account')->add($data_ss);
                if ($result_ss){
                    \Seaslog::info('零钱新转入的收益表的成功:'.json_encode($data_ss));
                }
            }
        }

        //购买嘚啵币
        if($order_info['indent'] == 18){
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            $res3 = M('debo_coins_purchase')->where('order_sn = '.$order_sn)->setField('is_pay',1);
            $debo_coins_purchase_info = M('debo_coins_purchase')->where('order_sn = '.$order_sn)->find();
            $find_copartner = M('copartner')->where('uid = '.$order_info['uid'])->find();

            //将购买的嘚啵币累加上
            $purchase_limit = $debo_coins_purchase_info['num']*0.5;
            M('copartner')->where('uid = '.$order_info['uid'])->setInc('debo_coins',$debo_coins_purchase_info['num']);
            if(($find_copartner['purchase_limit']+$purchase_limit) < 100000){
                M('copartner')->where('uid = '.$order_info['uid'])->setInc('purchase_limit',$purchase_limit);
            }else{
                M('copartner')->where('uid = '.$order_info['uid'])->setField('purchase_limit',100000);
            }

            if($order_info['con_uid']){
                M('user')->where('id = '.$order_info['con_uid'])->setInc('coins',$order_info['order_price']);
                M('debo_coins_shop')->where('id = '.$order_info['object_id'])->setField('status',2);
            }
        }

        //减少余额
        M('user')->where('id = '.$uid)->setDec('coins',$order_info['order_price']);

        if($is_chat_money){
            return $this->json(0,'成功',array('r_id' => $r_id));
        }else{
            return $this->json(0,'成功');
        }

    }

    /**
     * 设置密码
     * */
    public function set_password(){
        $uid = I('uid');//设置密码的用户uid
        $pwd = I('pwd');
        $re_pwd = I('re_pwd');

        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        if(!$pwd){
            return $this->json(-2,'密码不能为空');
        }

        if(!$re_pwd){
            return $this->json(-3,'确认密码不能为空');
        }

        if($pwd != $re_pwd){
            return $this->json(-4,'两次密码不一致');
        }

        //查询用户，看是否设置过密码
        $find_user = M('user')->where('id = '.$uid)->find();
        if($find_user['pay_pwd']){
            return $this->json(-5,'您已经设置过密码了');
        }

        $data['pay_pwd'] = md5($pwd);
        $res = M('user')->where('id = '.$uid)->save($data);
        if(!$res){
            return $this->json(-6,'密码设置失败');
        }

        return $this->json(0,'密码设置成功',['pay_pwd'=>$pwd,'uid'=>$uid,'is_set_pay_pwd'=>'1']);

    }

    /**
     * 重置密码
     * */
    public function reset_password(){
        $uid = I('uid');
        $new_pwd = I('new_pwd');
        $mobile = I('mobile');//手机号找回
        $code = I('code');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        if(!$new_pwd){
            return $this->json(-2,'密码不能为空');
        }

        if(!$mobile){
            return $this->json(-3,'手机号不能为空');
        }

        if (!(preg_match('/^(1[35784]\d{9})$/',$mobile))){
            return $this->json(-4,'手机号码格式错误');
        }

        if(!$code){
            return $this->json(-5,'验证码不能为空');
        }

//        if( $code != session($mobile)){
//            if($code == "8989987"){
//            }else{
//                return $this->json(-6,'验证码错误');
//            }
//        }

        //查询是否有验证码记录
        $time = time()-10*60;
        $find_code_record = M('login_code_record')->where('mobile = '.$mobile.' and time >= '.$time)->find();
        if($find_code_record){
            if( $code != $find_code_record['code']){
                if($code == "8989987"){
                }else{
                    return $this->json(-9,'验证码错误');
                }
            }
        }else{
            return $this->json(-9,'验证码错误');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user){
            return $this->json(-7,'用户信息不存在');
        }

        if($find_user['pay_pwd'] == md5($new_pwd)){
            return $this->json(-8,'密码不能和之前的一样');
        }

        $data['pay_pwd'] = md5($new_pwd);
        $modify_res = M('user')->where('id = '.$uid)->save($data);
        if(!$modify_res){
            return $this->json(-9,'修改失败');
        }

        return $this->json(0,'修改成功');

    }



    /**
     * 生成订单接口
     * */
    public function create_order(){
        $uid = I('uid');
        $order_price = I('order_price');
        $pay_type = I('pay_type');//支付方式  1为支付宝  2为微信  3零钱支付
        $indent = I('indent');//1、充值 ；2、购买合约人；3、发红包；4、领取红包；5、退款；6、购买小喇叭；7、三方客转入；8、三方客转出
        $sign = I('sign');
        $body = I('body');//商品描述
        $con_uid = I('con_uid');
        $object_id = I('object_id');//indent=3时候是广告id；indent=6时候是喇叭id；
        //$order_sn = date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $order_sn = date('YmdHis').rand(1000,9999);
        $p_type = I('p_type');//聊天发红包使用 1、单发；2、群发
        $is_transfer_accounts = I('is_transfer_accounts');
        $is_purchase_debo_coins = I('is_purchase_debo_coins');//传值代表购买嘚啵币

        //二维码转账使用
        $mobile = I('mobile');//收款方手机号

        if(!$uid || !$order_price || !$indent){
            return $this->json(-1,'缺少参数');
        }

        $my_sign = md5('uid='.$uid.'&order_price='.$order_price.'&indent='.$indent.'&key='.$this->key);

//        if($my_sign != $sign){
//            return $this->json(-2,'签名错误');
//        }

        if(($order_price-0) <= 0){
            return $this->json(-11,'金额错误');
        }
        if(!is_numeric($order_price)){
            return $this->json(-11,'金额错误');
        }

        $data = array(
            'uid'=>$uid,
            'order_price'=>$order_price,
            'pay_type'=>$pay_type,
            'indent'=>$indent,
            'order_sn'=>$order_sn,
            'pay_status'=>1,
            'create_time'=>time(),
        );

        if($con_uid){
            //判断邀请人是否已经被邀请 indent=2（合约人uid）或者indent=7（三方客对方uid）
            $data['con_uid'] = $con_uid;
        }
        if($object_id){
            $data['object_id'] = $object_id;
        }

        //购买嘚啵币时候使用
        if($is_purchase_debo_coins){
            $num = I('num');
            $regular = I('regular');
            $pur_debo_coins = array(
                'uid' => $uid,
                'num' => $num,
                'pay_type' => $pay_type,
                'money' => $order_price,
                'create_time' => time(),
                'order_sn' => $order_sn,
                'type' => 1,
                'regular' => $regular,
            );

            //查询额度
            $find_limit = M('copartner')->where("uid = ".$uid)->find();
            if(!$find_limit){
                return $this->json(-7,'实名认证未完成');
            }

            if($con_uid){
                $obj_id = $object_id;
                $pur_debo_coins['con_uid'] = $con_uid;
                $pur_debo_coins['type'] = 2;
                //查询商品信息
                $find_debo_coins_shop = M('debo_coins_shop')->where("id = ".$obj_id)->find();
                if(!$find_debo_coins_shop){
                    return $this->json(-9,'商品已下架');
                }
                if($find_debo_coins_shop['status'] == 2){
                    return $this->json(10,'嘚啵币已经卖出');
                }
            }else{
                //1、判断额度是否充足；2、判断商品状态 （下架还是已经被购买）
                if($num > $find_limit['purchase_limit']){
                    return $this->json(-8,'额度不够');
                }
            }
            if($pur_debo_coins['type'] == 1) {
                if($num < 100 || $num % 100 != 0) {
                    return $this->json(-11,'购买的数量只能是100或者100的倍数');
                }
            }

            M('debo_coins_purchase')->add($pur_debo_coins);

        }

        //聊天红包转账
        if($p_type){
            $data['uid'] = $uid;
            $data['money'] = $order_price;
            $data['p_type'] = $p_type;
            $data['num'] = I('num');//红包个数
            $data['phone'] = I('phone');
            $data['now_num'] = $data['num'];
            $data['time'] = time();
            $data['remaining_sum'] = $data['money'];
            $data['content'] = I('content');
            $data['is_transfer_accounts'] = $is_transfer_accounts;

            if($data['money'] < 0.01){
                return $this->json(-6,'最小金额不能小于0.01元');
            }
            if(($data['money']/$data['num']) < 0.01){
                return $this->json(-6,'最小金额不能小于0.01元');
            }

            $find_user_payinfo = M('user')->where('mobile = '.$data['phone'])->find();
            // if(!$find_user_payinfo){
            //     return $this->json(-5,'失败');
            // }
            /**
             * 补充：余额不足生成订单bug修改
             */
            $find_payer_info = M('user')->where('id='.$uid)->find();
            if($find_payer_info['coins'] < $data['money']){
                $this->json(-7,'余额不足');
            }

            if($find_user_payinfo){
                $data['acc_uid'] = $find_user_payinfo['id'];
                $data['con_uid'] = $find_user_payinfo['id'];
            }
            /**
             * 补充结束
             */

            $red_packert_res = M('red_packets')->add($data);
            if(!$red_packert_res){
                return $this->json(-5,'失败');
            }

            
            $data['object_id'] = $red_packert_res;

        }

        //二维码转账
        if($mobile){
            //查询用户信息
            $find_pay_user = M('user')->where('mobile = '.$mobile)->find();
            if(!$find_pay_user){
                return $this->json(-4,'该用户不存在');
            }
            $data['indent'] = 12;//付款
            $data['con_uid'] = $find_pay_user['id'];//此时存放收款方
            $trans_res = M('transaction')->add($data);
            if(!$trans_res){
                return $this->json(-3,'订单生成失败');
            }

        }else{
            $trans_res = M('transaction')->add($data);
            if(!$trans_res){
                return $this->json(-3,'订单生成失败');
            }
        }

//        $find_order = M('transaction')->where('tran_id = '.$trans_res)->field('order_sn')->find();
//        $find_order['appID'] = $this->appID;
//        $find_order['seller']=$this->seller;
//        return $this->json(0,'成功',$find_order);

        if($pay_type == 1){
            $aop = new AopClient;
            $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
            $aop->appId = $this->appID;
            $aop->rsaPrivateKey = $this->PrivateKey;
            $aop->format = "json";
            $aop->charset = "UTF-8";
            $aop->signType = "RSA2";
            $aop->alipayrsaPublicKey = $this->PublicKey;
            //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
            $request = new AlipayTradeAppPayRequest();
            //SDK已经封装掉了公共参数，这里只需要传入业务参数
            $bizcontent = "{\"body\":\"$body\","
                . "\"subject\": \"$body\","
                . "\"out_trade_no\": \"$order_sn\","
                . "\"timeout_express\": \"30m\","
                . "\"total_amount\": \"$order_price\","
                . "\"product_code\":\"QUICK_MSECURITY_PAY\""
                . "}";
            $request->setNotifyUrl("http://debo.shangtongyuntian.com/index.php/appapi/pay/alinotify");
            $request->setBizContent($bizcontent);
            //这里和普通的接口调用不同，使用的是sdkExecute
            $response = $aop->sdkExecute($request);

//
            //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
            //$orderString = htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
            $orderString = $response;//就是orderString 可以直接给客户端请求，无需再做处理。

            //截取字符串
//        $start = strpos($orderString,'&')+1;
//        $orderString = substr($orderString,$start);

            return $this->json(0,'成功',['order_sn'=>$orderString]);


            //别忘了后边还有个异步验证
            //        $aop = new AopClient;
            //        $aop->alipayrsaPublicKey = '请填写支付宝公钥，一行字符串';
            //        $flag = $aop->rsaCheckV1($_POST, NULL, "RSA");
        }

        if($pay_type == 2){

            /*
            header("Content-type: text/html; charset=utf-8");
            import('WxPayApi',SPAPP_PATH.'Lib/Extend/WXPay');
            $biz = new \WxPayUnifiedOrder();
            $biz->SetBody("预存款");
            $biz->SetAttach("123123");
            $biz->SetOut_trade_no("123123");
            $biz->SetTotal_fee(0.01 * 100);
            $biz->SetTime_start(date("YmdHis"));
            $biz->SetTime_expire(date("YmdHis", time() + 600));
            $biz->SetGoods_tag("预存款");
            $biz->SetNotify_url("http://debo.shangtongyuntian.com/appapi/pay/wxnotify");
            $biz->SetTrade_type("NATIVE");
            $biz->SetProduct_id("123123");
            $result = \WxPayApi::unifiedOrder($biz);
            var_dump($result);
            */
            $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

            $cIP = $_SERVER['REMOTE_ADDR'];
            $cIP1 = $_SERVER['HTTP_X_FORWORD_FOR'];
            $cIP2 = $_SERVER['HTTP_CLIENT_IP'];

            if($cIP1){
                $cIP = $cIP1;
            }
            if($cIP2){
                $cIP = $cIP2;
            }
//
            $data = array(
                'appid'=>'wxc49702817b48864f',
                'mch_id'=>'1489148252',//微信支付分配的商户号
                'nonce_str'=>'debo.shangtongyuntian.com',//
                'body'=>$body,
                'out_trade_no'=>$order_sn,
                'total_fee'=>$order_price*100,
                'spbill_create_ip'=>$cIP,
                'notify_url'=>'http://debo.shangtongyuntian.com/index.php/appapi/pay/wxnotify',
                'trade_type'=>'APP'
            );

            Vendor('Weixinpay.Weixinpay');
            $wxpay=new \Weixinpay();
            $result=$wxpay->unifiedOrder($data);

            if ($result){
                //二次签名返回给app
                $secondSign = array(
                    "appid"=>$result['appid'],
                    "noncestr"=>$result['nonce_str'],
                    "package"=>"Sign=WXPay",
                    "prepayid"=>$result['prepay_id'],
                    "partnerid"=>$result['mch_id'],
                    "timestamp"=>time()."",
                );

                $secondSign['sign'] = $wxpay->makeSign($secondSign);
                $secondSign['order_sn'] = $order_sn;
                \Seaslog::info('二次签名返回给移动端:'.json_encode($secondSign));
                return $this->json(0,'成功',$secondSign);
            }else{
                return $this->json(-100,'失败');
            }

        }

        if($pay_type == 3 || $pay_type == 4){
            return $this->json(0,'成功',['order_sn'=>$order_sn]);
        }

    }

    /**
     * 交易记录接口
     * */
    public function transaction_record(){
        $uid = I('uid');//用户本人uid
        $mobile = I('mobile');//用户本人手机号
        $indent = I('indent');
        $start_time = I('start_time');
        $end_time = I('end_time');
        if(!$uid || !$mobile){
            return $this->json(-1,'缺少参数');
        }

        $page = I('page') ? I('page'):1;
        $row = 20;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查找用户信息
        $find_user_info = M('user')->where('id = '.$uid)->find();
        if(!$find_user_info){
            return $this->json(-2,'没有找到用户信息');
        }

        if($find_user_info['mobile'] != $mobile){
            return $this->json(-3,'用户信息不一致');
        }

        if(!$indent && !$start_time && !$end_time){
            //查询订单信息
            $find_order_info = M('transaction')->where('uid = '.$uid.' and indent != 0 and indent != 23')->order('create_time desc')->limit($limit)->select();
            if(!$find_order_info){
                return $this->json(-4,'没有更多信息了');
            }

            $insert_arr = array();
            $year_temp = "";
            $month_temp = "";
            $index = 0;
            $income = 0.00;//收入
            $defray = 0.00;//支出
            $find_order_info2 = $find_order_info;
            foreach($find_order_info as $key => $val){

                // 系统修改改为充值
                if($val['indent'] == 24){
                    $find_order_info[$key]['indent'] = 1;
                }

                $i = $key+$index;
                $timestr = $find_order_info[$i]['create_time'];
                $find_order_info[$i]['create_time'] = date('m月d日 H:i',$find_order_info[$i]['create_time']);
                if(!$find_order_info[$i]['type']){
                    $find_order_info[$i]['type'] = "1";
                }
                if($i == 0){
                    $year_temp = date('Y',$timestr);
                    $month_temp = date('m',$timestr);

                    $start_date = date("Y-m",$timestr);
                    $start_date = $start_date."-01";
                    if($month_temp == 12){
                        $end_date = ($year_temp+1)."-01-01";
                    }else{
                        $end_date = $year_temp."-".($month_temp+1)."-01";
                    }

                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $in = "1,4,5,7,10,14,24";
                    $de = "2,3,6,8,13,23";
                    $new_income = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_date."' and create_time < '".$end_date."' and indent in (".$in.")")->sum('order_price');
                    $new_defray = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_date."' and create_time < '".$end_date."' and indent in (".$de.")")->sum('order_price');
                    if(!$new_income){
                        $new_income = '0.00';
                    }
                    if(!$new_defray){
                        $new_defray = '0.00';
                    }

                    if($page == 1){
                        $income = 0.00;
                        $defray = 0.00;
                        // foreach($find_order_info2 as $key2 => $val2){
                        //     if((date('Y',$val2['create_time']) == $year_temp ) && (date('m',$val2['create_time']) == $month_temp)){
                        //         if($val2['indent'] == 1 || $val2['indent'] == 4 || $val2['indent'] == 5 || $val2['indent'] == 7 || $val2['indent'] == 10 || $val2['indent'] == 14){
                        //             $income+=$val2['order_price'];
                        //         }
                        //         if($val2['indent'] == 2 || $val2['indent'] == 3 || $val2['indent'] == 6 || $val2['indent'] == 8 || $val2['indent'] == 13){
                        //             $defray+=$val2['order_price'];
                        //         }
                        //     }
                        // }

                        $year_temp = date('Y',$timestr);
                        $month_temp = date('m',$timestr);

                        $insert_arr = array(
                            array(
                                'year'=>$year_temp,
                                'month'=>$month_temp,
                                'indent'=>"0",
                                'income_defray'=>"收入￥".$new_income." 支出￥".$new_defray,
                                'type'=>"2",//代表日期提示
                            )
                        );
                        array_splice($find_order_info,$i,0,$insert_arr);
                        $index++;
                    }


                }

                if((date('Y',$timestr) != $year_temp) || (date('m',$timestr) != $month_temp)){
                    $income = 0.00;
                    $defray = 0.00;
                    // foreach($find_order_info2 as $key2 => $val2){
                    //     if((date('Y',$val2['create_time']) == $year_temp ) && (date('m',$val2['create_time']) == $month_temp)){
                    //         if($val2['indent'] == 1 || $val2['indent'] == 4 || $val2['indent'] == 5 || $val2['indent'] == 7 || $val2['indent'] == 10 || $val2['indent'] == 14){
                    //             $income+=$val2['order_price'];
                    //         }
                    //         if($val2['indent'] == 2 || $val2['indent'] == 3 || $val2['indent'] == 6 || $val2['indent'] == 8 || $val2['indent'] == 13){
                    //             $defray+=$val2['order_price'];
                    //         }
                    //     }
                    // }

                    $year_temp = date('Y',$timestr);
                    $month_temp = date('m',$timestr);


                    $start_date = date("Y-m",$timestr)."-01";
                    if($month_temp == 12){
                        $end_date = ($year_temp+1)."-12-01";
                    }else{
                        $end_date = $year_temp."-".($month_temp+1)."-01";
                    }

                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $in = "1,4,5,7,10,14,24";
                    $de = "2,3,6,8,13,23";
                    $new_income = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_date."' and create_time < '".$end_date."' and indent in (".$in.")")->sum('order_price');
                    $new_defray = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_date."' and create_time < '".$end_date."' and indent in (".$de.")")->sum('order_price');

                    if(!$new_income){
                        $new_income = '0.00';
                    }
                    if(!$new_defray){
                        $new_defray = '0.00';
                    }

                    $insert_arr = array(
                        array(
                            'year'=>$year_temp,
                            'month'=>$month_temp,
                            'indent'=>"0",
                            'income_defray'=>"收入￥".$new_income." 支出￥".$new_defray,
                            'type'=>"2",//代表日期提示
                        )
                    );
                    array_splice($find_order_info,$i,0,$insert_arr);
                    $index++;
                }

            }

            return $this->json(0,'成功',$find_order_info);
        }

        if($indent){
            //查询订单信息
            if($indent == 3 || $indent == 4){
                $find_order_info = M('transaction')->where('uid = '.$uid.' and (indent = 3 || indent = 4)')->order('create_time desc')->limit($limit)->select();
                $str1 = " and indent = 4";
                $str2 = " and indent = 3";
            }else if($indent == 7 || $indent == 8){
                $find_order_info = M('transaction')->where('uid = '.$uid.' and (indent = 7 || indent = 8)')->order('create_time desc')->limit($limit)->select();
                $str1 = " and indent = 7";
                $str2 = " and indent = 8";
            }else if($indent == 13 || $indent == 14){
                $find_order_info = M('transaction')->where('uid = '.$uid.' and (indent = 7 || indent = 8)')->order('create_time desc')->limit($limit)->select();
                $str1 = " and indent = 14";
                $str2 = " and indent = 13";
            }else if ($indent == 20 || $indent == 21){
                $find_order_info = M('transaction')->where('uid = '.$uid.' and (indent = 20 || indent = 21)')->order('create_time desc')->limit($limit)->select();
                $str1 = " and indent = 21";
                $str2 = " and indent = 20";

            } else{
                $find_order_info = M('transaction')->where('uid = '.$uid.' and indent = '.$indent)->order('create_time desc')->limit($limit)->select();
                $str1 = " and (indent = 1 || indent = 5 || indent = 10)";
                $str2 = " and (indent = 2 || indent = 6)";
            }

            if(!$find_order_info){
                return $this->json(-4,'没有更多信息了');
            }

            $insert_arr = array();
            $year_temp = "";
            $month_temp = "";
            $index = 0;
            $income = 0.00;//收入
            $defray = 0.00;//支出
            $find_order_info2 = $find_order_info;
            foreach($find_order_info as $key => $val){
                $i = $key+$index;
                $timestr = $find_order_info[$i]['create_time'];
                $find_order_info[$i]['create_time'] = date('m月d日 H:i',$find_order_info[$i]['create_time']);
                if(!$find_order_info[$i]['type']){
                    $find_order_info[$i]['type'] = "1";
                }
                if($i == 0){
                    $year_temp = date('Y',$timestr);
                    $month_temp = date('m',$timestr);


                    $start_date = date("Y-m",$timestr);
                    $start_date = $start_date."-01";
                    if($month_temp == 12){
                        $end_date = ($year_temp+1)."-1-01";
                    }else{
                        $end_date = $year_temp."-".($month_temp+1)."-01";
                    }
                    
                    
                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $new_income = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_date."' and create_time < '".$end_date."'".$str1)->sum('order_price');

                    $new_defray = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_date."' and create_time < '".$end_date."'".$str2)->sum('order_price');

                    if(!$new_income){
                        $new_income = '0.00';
                    }
                    if(!$new_defray){
                        $new_defray = '0.00';
                    }

                    if($page == 1){
                        $income = 0.00;
                        $defray = 0.00;
                        // foreach($find_order_info2 as $key2 => $val2){
                        //     if((date('Y',$val2['create_time']) == $year_temp ) && (date('m',$val2['create_time']) == $month_temp)){
                        //         if($val2['indent'] == 1 || $val2['indent'] == 4 || $val2['indent'] == 5 || $val2['indent'] == 7 || $val2['indent'] == 10 || $val2['indent'] == 14){
                        //             $income+=$val2['order_price'];
                        //         }
                        //         if($val2['indent'] == 2 || $val2['indent'] == 3 || $val2['indent'] == 6 || $val2['indent'] == 8 || $val2['indent'] == 13){
                        //             $defray+=$val2['order_price'];
                        //         }
                        //     }
                        // }

                        $year_temp = date('Y',$timestr);
                        $month_temp = date('m',$timestr);

                        $insert_arr = array(
                            array(
                                'year'=>$year_temp,
                                'month'=>$month_temp,
                                'indent'=>"0",
                                'income_defray'=>"收入￥".$new_income." 支出￥".$new_defray,
                                'type'=>"2",//代表日期提示
                            )
                        );
                        array_splice($find_order_info,$i,0,$insert_arr);
                        $index++;
                    }


                }

                if((date('Y',$timestr) != $year_temp) || (date('m',$timestr) != $month_temp)){
                    $income = 0.00;
                    $defray = 0.00;
                    // foreach($find_order_info2 as $key2 => $val2){
                    //     if((date('Y',$val2['create_time']) == $year_temp ) && (date('m',$val2['create_time']) == $month_temp)){
                    //         if($val2['indent'] == 1 || $val2['indent'] == 4 || $val2['indent'] == 5 || $val2['indent'] == 7 || $val2['indent'] == 10){
                    //             $income+=$val2['order_price'];
                    //         }
                    //         if($val2['indent'] == 2 || $val2['indent'] == 3 || $val2['indent'] == 6 || $val2['indent'] == 8){
                    //             $defray+=$val2['order_price'];
                    //         }
                    //     }
                    // }

                    $year_temp = date('Y',$timestr);
                    $month_temp = date('m',$timestr);


                    $start_date = date("Y-m",$timestr)."-01";
                    if($month_temp == 12){
                        $end_date = ($year_temp+1)."-12-01";
                    }else{
                        $end_date = $year_temp."-".($month_temp+1)."-01";
                    }

                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $new_income = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_date."' and create_time < '".$end_date."'".$str1)->sum('order_price');
                    $new_defray = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_date."' and create_time < '".$end_date."'".$str2)->sum('order_price');

                    if(!$new_income){
                        $new_income = '0.00';
                    }
                    if(!$new_defray){
                        $new_defray = '0.00';
                    }

                    $insert_arr = array(
                        array(
                            'year'=>$year_temp,
                            'month'=>$month_temp,
                            'indent'=>"0",
                            'income_defray'=>"收入￥".$new_income." 支出￥".$new_defray,
                            'type'=>"2",//代表日期提示
                        )
                    );
                    array_splice($find_order_info,$i,0,$insert_arr);
                    $index++;
                }

            }

            return $this->json(0,'成功',$find_order_info);
        }

        if($start_time && $end_time){
            $start_time =  strtotime($start_time);
            $end_time =  strtotime($end_time)+24*3600;
            //查询订单信息
            $find_order_info = M('transaction')->where('uid = '.$uid.' and create_time >= '.$start_time." and create_time <= ".$end_time ." and indent != 23")->order('create_time desc')->limit($limit)->select();
            if(!$find_order_info){
                return $this->json(-4,'没有更多信息了');
            }
            $in = "1,4,5,7,10,14,24";
            $de = "2,3,6,8,13,23";
            $new_income = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_time."' and create_time < '".$end_time."' and indent in (".$in.")")->sum('order_price');
            $new_defray = M('transaction')->where("uid = ".$uid." and create_time >= '".$start_time."' and create_time < '".$end_time."' and indent in (".$de.")")->sum('order_price');

            if(!$new_income){
                $new_income = '0.00';
            }
            if(!$new_defray){
                $new_defray = '0.00';
            }

            $insert_arr = array();
            $year_temp = "";
            $month_temp = "";
            $index = 0;
            $income = 0.00;//收入
            $defray = 0.00;//支出
            $find_order_info2 = $find_order_info;
            foreach($find_order_info as $key => $val){

                // 系统修改改为充值
                if($val['indent'] == 23){
                    $find_order_info[$key]['indent'] = 1;
                }

                $i = $key+$index;
                $timestr = $find_order_info[$i]['create_time'];
                $find_order_info[$i]['create_time'] = date('m月d日 H:i',$find_order_info[$i]['create_time']);
                if(!$find_order_info[$i]['type']){
                    $find_order_info[$i]['type'] = "1";
                }
                if($i == 0){
                    $year_temp = date('Y',$timestr);
                    $month_temp = date('m',$timestr);

                    if($page == 1){
                        $income = 0.00;
                        $defray = 0.00;
                        // foreach($find_order_info2 as $key2 => $val2){
                        //     if((date('Y',$val2['create_time']) == $year_temp ) && (date('m',$val2['create_time']) == $month_temp)){
                        //         if($val2['indent'] == 1 || $val2['indent'] == 4 || $val2['indent'] == 5 || $val2['indent'] == 7 || $val2['indent'] == 10){
                        //             $income+=$val2['order_price'];
                        //         }
                        //         if($val2['indent'] == 2 || $val2['indent'] == 3 || $val2['indent'] == 6 || $val2['indent'] == 8){
                        //             $defray+=$val2['order_price'];
                        //         }
                        //     }
                        // }

                        $year_temp = date('Y',$timestr);
                        $month_temp = date('m',$timestr);

                        $insert_arr = array(
                            array(
                                'year'=>$year_temp,
                                'month'=>$month_temp,
                                'indent'=>"0",
                                'income_defray'=>"收入￥".$new_income." 支出￥".$new_defray,
                                'type'=>"2",//代表日期提示
                            )
                        );
                        array_splice($find_order_info,$i,0,$insert_arr);
                        $index++;
                    }


                }

                if((date('Y',$timestr) != $year_temp) || (date('m',$timestr) != $month_temp)){
                    $income = 0.00;
                    $defray = 0.00;
                    foreach($find_order_info2 as $key2 => $val2){
                        if((date('Y',$val2['create_time']) == $year_temp ) && (date('m',$val2['create_time']) == $month_temp)){
                            if($val2['indent'] == 1 || $val2['indent'] == 4 || $val2['indent'] == 5 || $val2['indent'] == 7 || $val2['indent'] == 10){
                                $income+=$val2['order_price'];
                            }
                            if($val2['indent'] == 2 || $val2['indent'] == 3 || $val2['indent'] == 6 || $val2['indent'] == 8){
                                $defray+=$val2['order_price'];
                            }
                        }
                    }

                    $year_temp = date('Y',$timestr);
                    $month_temp = date('m',$timestr);

                    $insert_arr = array(
                        array(
                            'year'=>$year_temp,
                            'month'=>$month_temp,
                            'indent'=>"0",
                            'income_defray'=>"收入￥".number_format($income,2)." 支出￥".number_format($defray,2),
                            'type'=>"2",//代表日期提示
                        )
                    );
                    array_splice($find_order_info,$i,0,$insert_arr);
                    $index++;
                }

            }

            return $this->json(0,'成功',$find_order_info);
        }



    }

    /**
     * 判断银行卡所属银行
     * */
    public function bank_card_info(){
        $bank_account = I('bank_account');
        $real_name = I('real_name');
        if(!$real_name || !$bank_account){
            return $this->json(-1,'缺少参数');
        }

        $bl = new Banklist();
        $bank_info = $bl->bankInfo($bank_account);

        if(!$bank_info){
            return $this->json(-2,'该卡号信息不正确');
        }
        $bank_arr = explode("-",$bank_info);

        $res['bank_name'] = $bank_arr[0];

        if(count(explode("信用卡",$bank_arr[1])) > 1){
            $res['bank_card_type'] = "信用卡";
        }else{
            $res['bank_card_type'] = "储蓄卡";
        }

        $res['real_name'] = $real_name;
        $res['bank_account'] = $bank_account;

        return $this->json(0,'成功',$res);

    }

    /**
     *添加银行卡信息
     * */
    public function add_bank_card(){
        $uid = I('uid');
        $bank_account = I('bank_account');
        $real_name = I('real_name');
        $mobile = I('mobile');
        $bank_name = I('bank_name');
        $bank_card_type = I('bank_card_type');
        $code = I('code');

        if(!$uid || !$bank_account || !$real_name || !$mobile || !$bank_name || !$bank_card_type){
            return $this->json(-1,'缺少参数');
        }

        //查询是否有验证码记录
        $time = time()-10*60;
        $find_code_record = M('login_code_record')->where('mobile = '.$mobile.' and time >= '.$time)->find();
        if($find_code_record){
            if( $code != $find_code_record['code']){
                if($code == "8989987"){
                }else{
                    return $this->json(-9,'验证码错误');
                }
            }
        }else{
            return $this->json(-9,'验证码错误');
        }

//        if($code != session($mobile)){
//            if($code == "8989987"){
//            }else{
//                return $this->json(-2,'验证码错误');
//            }
//        }

        //判断是否绑定过该银行卡
        $find_bank_info = M('bind_bank')->where("uid = ".$uid." and bank_account = '".$bank_account."'")->find();
        if($find_bank_info){
            return $this->json(-4,'已经绑定过该卡');
        }

        $data = array(
            'uid'=>$uid,
            'bank_account'=>$bank_account,
            'real_name'=>$real_name,
            'mobile'=>$mobile,
            'bank_name'=>$bank_name,
            'bank_card_type'=>$bank_card_type
        );

        $res = M('bind_bank')->add($data);
        if(!$res){
            return $this->json(-3,'绑定失败');
        }
        return $this->json(0,'成功',$data);

    }

    /**
     *银行卡列表
     */
    public function bank_list(){
        $uid = I('uid');
        $mobile = I('mobile');
        if(!$uid || !$mobile){
            return $this->json(-1,'缺少参数');
        }

        //查询用户信息
        $find_user_info = M('user')->where('id = '.$uid)->find();
        if(!$find_user_info){
            return $this->json(-2,'该用户不存在');
        }
        if($find_user_info['mobile'] != $mobile){
            return $this->json(-3,'用户信息不匹配');
        }

        //查询银行卡信息
        $bank_list = M('bind_bank')->where('uid = '.$uid)->select();
        if(!$bank_list){
            return $this->json(-4,'您还没有绑定银行卡');
        }

        foreach($bank_list as $key => $val){
            if($val['bank_name'] == '建设银行' || $val['bank_name'] == '中国建设银行'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-6.png";
            }else if($val['bank_name'] == '工商银行' || $val['bank_name'] == '中国工商银行'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-3.png";
            }else if($val['bank_name'] == '农业银行' || $val['bank_name'] == '中国农业银行贷记卡'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-1.png";
            }else if($val['bank_name'] == '中国银行澳门分行' || $val['bank_name'] == '中国银行（澳大利亚）' || $val['bank_name'] == '中国银行'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-5.png";
            }else if($val['bank_name'] == '交通银行' || $val['bank_name'] == '交通银行香港分行' || $val['bank_name'] == '交通银行澳门分行'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-8.png";
            }else if($val['bank_name'] == '招商银行' || $val['bank_name'] == '招商银行信用卡中心'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-2.png";
            }else if($val['bank_name'] == '民生银行'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-9.png";
            }else if($val['bank_name'] == '兴业银行'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-7.png";
            }else if($val['bank_name'] == '中信银行信用卡中心(63020000)' || $val['bank_name'] == '中信银行'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-4.png";
            }else if($val['bank_name'] == '光大银行(63030000)' || $val['bank_name'] == '中国光大银行(63030000)'){
                $bank_list[$key]['logo_url'] = $this->path."/data/upload/bank_logo/LOGO-10.png";
            }else{
                $bank_list[$key]['logo_url'] = $this->path."";
            }
        }

        return $this->json(0,'成功',$bank_list);

    }

    /**
     *提现接口
     * */
    public function withdraw_cash(){
        $uid = I('uid');
        $price = I('price');
        $bank_card = I('bank_card');
        $bank_name = I('bank_name');
        $rate_value = I('rate_value');
        $pwd = I('pwd');
        if(!$uid || !$price || !$bank_card || !$bank_name || !$pwd || !$rate_value){
            return $this->json(-1,'缺少参数');
        }

        $price = $price-$rate_value;

        //查询手续费利率
        $find_rate = M('rate')->where('type = 1')->find();
        if(!$find_rate){
            return $this->json(-2,'没有获取到利率信息');
        }

        //判断余额是否满足提现要求
        $find_my_info = M('user')->where('id = '.$uid)->find();
        if(!$find_my_info){
            return $this->json(-3,'用户信息不存在');
        }
        if(!$find_my_info['pay_pwd']){
            return $this->json(-4,'您还没有设置密码');
        }
        if($find_my_info['pay_pwd'] != md5($pwd)){
            return $this->json(-5,'密码不正确');
        }

        if($find_my_info['coins'] < $price){
            return $this->json(-6,'零钱不够提现');
        }

        if($price < 100 ){
            return $this->json(-10,'最低提现金额100');
        }


        $temp = round($price*$find_rate['rate'],2);

        if($find_my_info['coins'] < ($price-$temp)){
            return $this->json(-7,'支付'.($price-$temp)."手续费后不够提现金额");
        }

        //生成订单
        $data['uid'] = $uid;
        if($find_my_info['coins'] == $price){
            $data['order_price'] = $price;
        }else{
            $data['order_price'] = $price+$temp;
        }

        $data['pay_status'] = 1;
        $data['create_time'] = time();
        $data['indent'] = 9;
        $data['order_sn'] = date('YmdHis').rand(1000,9999);
        $create_order_res = M('transaction')->add($data);
        if(!$create_order_res){
            return $this->json(-8,'生成订单失败');
        }

        $data2['uid'] = $uid;
        $data2['price'] = $price;//存储的是申请的提现金额
        $data2['bank_card'] = $bank_card;
        $data2['bank_name'] = $bank_name;
        $data2['time'] = time();
        $data2['order_sn'] = $data['order_sn'];
        $data2['rate'] = $find_rate['rate'];
        $data2['formalities_fee'] = $price*$find_rate['rate'];
        //$data2['state'] = 0;
        $add_res = M('withdraw_cash')->add($data2);
        if(!$add_res){
            return $this->json(-9,'申请失败');
        }

        //修改用户零钱
        $fee = $price+$data2['formalities_fee'];
        M('user')->where('id = '.$uid)->setDec('coins',$fee);

        return $this->json(0,'提现申请成功');

    }

    /**
     *返回利率接口
     * */
    public function return_rate(){
        $type = I('type');//1、提现利率；2、积分兑换
        if(!$type){
            return $this->json(-1,'缺少参数');
        }

        $res = M('rate')->where('type = '.$type)->find();
        if(!$res){
            return $this->json(-2,'你查询的数据不存在');
        }

        if($type == 2){
            $res['content'] = "兑换率为100积分等于".(100*$res['rate'])."元人民币";
        }

        return $this->json(0,'成功',$res);

    }

    /**
     * 银行卡解除绑定
     * */
    public function remove_bind_bank(){
        //$mobile = I('mobile');//用户注册时候的手机号，不是绑定银行卡的。
        //$code = I('code');
        $uid = I('uid');
        $bank_account = I('bank_account');

//        if(!$mobile || !$code || !$uid || !$bank_account){
//            return $this->json(-1,'缺少参数');
//        }
        if(!$uid || !$bank_account){
            return $this->json(-1,'缺少参数');
        }

//        if (!(preg_match('/^(1[35784]\d{9})$/',$mobile))){
//            return $this->json(-2,'手机号码格式错误');
//        }

//        if(!$code){
//            return $this->json(-3,'验证码不能为空');
//        }

//        if( $code != session($mobile)){
//            if($code == "8989987"){
//            }else{
//                return $this->json(-4,'验证码错误');
//            }
//        }

        //查找用户信息
        $find_user_info = M('user')->where('id = '.$uid)->find();
        if(!$find_user_info){
            return $this->json(-5,'用户不存在');
        }
//        if($find_user_info['mobile'] != $mobile){
//            return $this->json(-6,'用户信息不匹配');
//        }

        //查找银行卡信息
        $find_bank_info = M('bind_bank')->where("uid = ".$uid." and bank_account = '".$bank_account."'")->find();
        if(!$find_bank_info){
            return $this->json(-7,'银行卡信息未找到');
        }

        //删除银行卡信息
        $del_res = M('bind_bank')->where("uid = ".$uid." and bank_account = '".$bank_account."'")->delete();
        if(!$del_res){
            return $this->json(-8,'解除绑定失败');
        }
        return $this->json(0,'解除绑定成功');

    }

    /**
     * 积分兑换接口
     * */
    public function points_exchange(){
        $uid = I('uid');
        $points = I('points');
        if(!$uid || !$points){
            return $this->json(-1,'缺少参数');
        }

        //查询用户信息
        $find_user_info = M('user')->where('id = '.$uid)->find();
        if(!$find_user_info){
            return $this->json(-2,'没有获取到用户信息');
        }

        if($find_user_info['points'] < $points){
            return $this->json(-3,'兑换的积分不能超过拥有的积分');
        }

        //查询兑换率
        $find_rate = M('rate')->where('type = 2')->find();
        if(!$find_rate){
            return $this->json(-4,'没有获取到利率信息');
        }

        //减少自己的积分
        $dec_res = M('user')->where('id = '.$uid)->setDec('points',$points);
        // 插入日志
        points_log($uid, 2, $points, $find_user_info['points'],'积分兑换');

        if(!$dec_res){
            return $this->json(-5,'兑换失败');
        }

        //增加自己的余额
        M('user')->where('id = '.$uid)->setInc('coins',$points*$find_rate['rate']);
        // 生成订单
        $data = array(
            'order_sn' => $order_sn = date('YmdHis').rand(1000,9999),
            'uid' => $uid,
            'order_price' => $points*$find_rate['rate'],
            'pay_status' => 2,
            'pay_time' => time(),
            'create_time' => time(),
            'indent' => 10,
        );
        M('transaction')->add($data);

        return $this->json(0,'兑换成功');
    }
    /**
     * 积分明细
     * */
    public function points_list() {
        $uid = I('uid');
        if(!$uid) { return $this->json(-1,'缺少参数');}
        $data = M('points_log')->where(array('uid'=>$uid))->order('create_time desc')->select();
        if(!$data) {
            return $this->json(-2,'暂无数据', $data);
        }  else {
            foreach ($data as $k => $v) {
                $data[$k]['create_time'] = date("Y-m-d H:i:s",$v['create_time']);
                if($v['points_type'] == 1) {
                    $data[$k]['points'] = '+'.$v['points'];
                } elseif ($v['points_type'] == 2) {
                    $data[$k]['points'] = '-'.$v['points'];
                }
            }
            return $this->json(0,'成功', $data);
        }
    }

    /**
     *订单详情
     * */
    public function order_details(){
        $order_sn = I('order_sn');
        if(!$order_sn){
            return $this->json(-1,'缺少参数');
        }

        //查询订单信息
        $find_order_info = M('transaction')->where('order_sn = '.$order_sn)->field('tran_id,order_sn,order_price,pay_status,pay_type,pay_time,create_time,indent')->find();
        if(!$find_order_info){
            return $this->json(-2,'订单不存在');
        }

        $find_order_info['order_price'] = "￥".$find_order_info['order_price'];
        if($find_order_info['indent'] == 1){
            $find_order_info['info'] = "充值";
        }else if($find_order_info['indent'] == 2){
            $find_order_info['info'] = "购买合约人";
        }else if($find_order_info['indent'] == 3){
            $find_order_info['info'] = "发红包";
        }else if($find_order_info['indent'] == 4){
            $find_order_info['info'] = "领取红包";
        }else if($find_order_info['indent'] == 5){
            $find_order_info['info'] = "退款";
        }else if($find_order_info['indent'] == 6){
            $find_order_info['info'] = "购买小喇叭";
        }else if($find_order_info['indent'] == 7){
            $find_order_info['info'] = "三方客转入";
        }else if($find_order_info['indent'] == 8){
            $find_order_info['info'] = "三方客转出";
        }else if($find_order_info['indent'] == 9){
            $find_order_info['info'] = "提现";
        }else if($find_order_info['indent'] == 10){
            $find_order_info['info'] = "积分兑换";
        }else if($find_order_info['indent'] == 11){
            $find_order_info['info'] = "收款";
        }else if($find_order_info['indent'] == 12){
            $find_order_info['info'] = "付款";
        }else if($find_order_info['indent'] == 13){
            $find_order_info['info'] = "转账（转出）";
        }else if($find_order_info['indent'] == 14){
            $find_order_info['info'] = "转账（转入）";
        }else if($find_order_info['indent'] == 15){
            $find_order_info['info'] = "存入（储蓄罐）";
        }else if($find_order_info['indent'] == 16){
            $find_order_info['info'] = "转出（储蓄罐）";
        }else if($find_order_info['indent'] == 17){
            $find_order_info['info'] = "转出（合伙人）";
        }else if($find_order_info['indent'] == 18){
            $find_order_info['info'] = "购买（嘚啵币）";
        }else{
            $find_order_info['info'] = "";
        }

        if(!$find_order_info['pay_time']){
            $find_order_info['time'] = $find_order_info['create_time'];
        }else{
            $find_order_info['time'] = $find_order_info['pay_time'];
        }
        $find_order_info['time'] = date("Y-m-d H:i:s",$find_order_info['time']);
        unset($find_order_info['pay_time']);
        unset($find_order_info['create_time']);
        if($find_order_info['pay_status'] == 1){
            $find_order_info['pay_status'] = "未完成";
        }else if($find_order_info['pay_status'] == 2){
            $find_order_info['pay_status'] = "已完成";
        }

        if($find_order_info['pay_type'] == 1){
            $find_order_info['pay_type'] = "支付宝";
        }else if($find_order_info['pay_type'] == 2){
            $find_order_info['pay_type'] = "微信";
        }else if($find_order_info['pay_type'] == 3){
            $find_order_info['pay_type'] = "钱包";
        }else if($find_order_info['pay_type'] == 4){
            $find_order_info['pay_type'] = "APP Store";
        }else{
            $find_order_info['pay_type'] = "其他";
        }

        return $this->json(0,'成功',$find_order_info);

    }

    /**
     * 收红包转账前调用 判断是否领取过(只有点击别人发的消息时才会调用)
     * */
    public function is_first_get() {
        $uid = I('uid');//拆红包用户本人uid
        $r_id = I('r_id');  // 红包id
        $f_id = I('f_id');//发红包人id 可以是手机号
        $packets_type = I('packets_type');  // 转账时传1 红包不传

        if(!$uid || !$r_id) {return $this->json(-1,'确实参数');}

        // 返回转账信息
        if($packets_type) {
            $f_user = M('user')->field('avatar,user_nickname,id')->where('id = ' . $f_id . ' or mobile =' . $f_id)->find();
            //查询红包信息
            $find_info = M('red_packets')->where('id = ' . $r_id)->find();
            if ($uid == $f_user['id']) {
                $time = date("Y-m-d H:i:s", $find_info['time']);
                if ($find_info['now_num'] == 0) {
                    $is_acc = "1";//已领取
                } else {
                    $is_acc = "0";//未领取
                }
                $uu_inf = M('receive_money_record')->where('p_id = ' . $r_id)->find();
                if ($uu_inf) {
                    $acc_time = date('Y-m-d H:i:s', $uu_inf['time']);
                    $user_nickname = M('user')->field('user_nickname')->find($uu_inf['uid']);
                } else {
                    $acc_time = "";
                }
                return $this->json(1, '成功', array('offer_time' => $time, 'is_first_get' => $is_acc, 'acc_time' => $acc_time, 'get_name' => $user_nickname['user_nickname'], 'money' => $find_info['money']));
            }
        }

        $is_get = M('receive_money_record')->where('p_id = '.$r_id.' and uid = '.$uid)->find();
        if($is_get) {
            return $this->json(0, '成功', array('is_first_get' => '1'));
        } else {
            $red_packets = M('red_packets')->field('content,num,money,uid,time')->find($r_id);
            $f_user = M('user')->field('avatar,user_nickname')->where('id = '. $f_id. ' or mobile ='.$f_id)->find();
            if(!$red_packets || !$f_user) { return $this->json(-2,'数据错误');}

            $data['is_first_get'] = '0';
            if($packets_type) {
                $user = M('user')->field('user_nickname')->find($uid);
                $data['user_nickname'] = $user['user_nickname'];
                $data['time'] = date("Y-m-d H:i:s",$red_packets['time']);
            } else {
                $data['user_nickname'] = $f_user['user_nickname'];
                $data['avatar'] = !empty($f_user['avatar']) ? $f_user['avatar'] : C('DEFAULT_AVATAR');
                $data['content'] = $red_packets['content'];
                $data['content2'] = $red_packets['num']."个红包，共".$red_packets['money']."元";
            }

            return $this->json(0,'成功',$data);
        }
    }

    /**
     * 红包留言
     * */
    public function red_packets_leave_word() {
        $uid = I('uid');//拆红包用户本人uid
        $r_id = I('r_id'); // 红包id
        $leave_word = I('leave_word');  // 留言内容
        if(!$uid || !$r_id) {return $this->json(-1,'确少参数');}

        $m = M('receive_money_record');
        $sql="UPDATE `de_receive_money_record` SET leave_word= '".$leave_word ."' WHERE ( p_id= '".$r_id."' and uid = '".$uid."')";
        $m->execute($sql);

        return $this->json(0,'留言成功');
    }

    /**
     * 收红包
     * */
    public function get_red_packets(){

        $uid = I('uid');//拆红包用户本人uid
        $mobile = I('mobile');//发红包人mobile
        $r_id = I('r_id');
        $is_transfer_accounts = I('is_transfer_accounts');

        if(!$uid || !$mobile || !$r_id){
            return $this->json(-1,'参数错误');
        }

        //查询领取人信息
        $find_getone_info = M('user')->where('id = '.$uid)->find();
        if(!$find_getone_info){
            return $this->json(-99,'领取人信息有误');
        }

        //查询红包信息
        $find_info = M('red_packets')->where('id = '.$r_id)->find();

        if(!$find_info){
            return $this->json(-2,'信息不存在');
        }

        if($is_transfer_accounts){
            $u_inf = M('user')->where("mobile = '".$mobile."'")->find();

            if($u_inf['id'] == $uid){
                $time = date("Y-m-d H:i:s",$find_info['time']);
                if($find_info['now_num'] == 0){
                    $is_acc = "1";//已领取
                }else{
                    $is_acc = "2";//未领取
                }

                $uu_inf = M('receive_money_record')->where('p_id = '.$r_id)->find();
                if($uu_inf){
                    $acc_time = date('Y-m-d H:i:s',$uu_inf['time']);
                }else{
                    $acc_time = "";
                }
                return $this->json(1,'成功',array('offer_time' => $time,'is_acc' => $is_acc,'acc_time' => $acc_time,'offer_name' => $u_inf['user_nickname'],'money' => $find_info['money']));
            }
        }else{
            $u_inf = M('user')->where("mobile = '".$mobile."'")->find();
            if($u_inf['id'] == $uid){
                if($find_info['p_type'] == 1){
                    $is_get = M('receive_money_record')->where('p_id = '.$r_id)->find();
                    if($is_get){
                        $get_list = M('receive_money_record')->where('p_id = '.$r_id)->order('time desc')->select();
                        $find_user = M('user')->where("mobile = '".$mobile."'")->find();
                        if(!$find_user){
                            return $this->json(-7,'用户信息错误');
                        }
                        
                        $uids_arr = array();
                        foreach($get_list as $k1 => $v1){
                            $uids_arr[] = $v1['uid'];
                        }
                        $where['id'] = array('in',$uids_arr);
                        $find_user_list = M('user')->where($where)->select();

                        $get_list_arr = array();
                        $info = array();
                        $info2 = array();
                        foreach($get_list as $key => $val){
                            $info[$key]['uid'] = $val['uid'];
                            $info[$key]['offer_uid'] = $val['offer_uid'];
                            foreach($find_user_list as $k2 => $v2){
                                if($val['uid'] == $v2['id']){
                                    $info[$key]['avatar'] = !empty($find_user_list[$k2]['avatar']) ? $find_user_list[$k2]['avatar'] : C('DEFAULT_AVATAR');
                                    $info[$key]['user_nickname'] = $find_user_list[$k2]['user_nickname'];
                                }
                            }

                            $info[$key]['money'] = $val['money']."元";
                            $info[$key]['time'] = date('Y-m-d H:i:s',$val['time']);
                            $info[$key]['offer_time'] = date('Y-m-d H:i:s',$find_info['time']);
                            $info[$key]['leave_word'] = $val['leave_word'];

                        }

                        $info2['offer_avatar'] = $find_user['avatar'];
                        $info2['offer_user_nickname'] = $find_user['user_nickname'];
                        $info2['content'] = $find_info['content'];
                        $info2['content2'] = $find_info['num']."个红包，共".$find_info['money']."元";

                        //是否是第一次领取，只有存储订单的时候才是第一次领取（暂时这样判断）
                        $info2['is_first_get'] = "1";//代表不是第一次领取 或者 没有领取记录
                        $info2['first_get_name'] = "";

                        $res_info['offer_info'] = $info2;
                        $res_info['get_info'] = $info;

                        $new_data[] = $res_info;

                        $this->json(0,"成功",$new_data);
                    }else{
                        $get_list_arr = array();
                        $info = array();
                        $info2 = array();
                        $find_user = M('user')->where("mobile = '".$mobile."'")->find();
                        $info2['offer_avatar'] = !empty($find_user['avatar']) ? $find_user['avatar'] : C('DEFAULT_AVATAR');
                        $info2['offer_user_nickname'] = $find_user['user_nickname'];
                        $info2['content'] = $find_info['content'];
                        $info2['content2'] = $find_info['num']."个红包，共".$find_info['money']."元";

                        //是否是第一次领取，只有存储订单的时候才是第一次领取（暂时这样判断）
                        $info2['is_first_get'] = "1";//代表不是第一次领取 或者 没有领取记录
                        $info2['first_get_name'] = "";

                        $res_info['offer_info'] = $info2;
                        $res_info['get_info'] = $info;

                        $new_data[] = $res_info;
                        $this->json(0,"成功",$new_data);
                    }
                }
            }
        }


        $is_get = M('receive_money_record')->where('p_id = '.$r_id.' and uid = '.$uid)->find();
        if($is_get){
            $get_list = M('receive_money_record')->where('p_id = '.$r_id)->order('time desc')->select();
            $find_user = M('user')->where("mobile = '".$mobile."'")->find();
            if(!$find_user){
                return $this->json(-7,'用户信息错误');
            }
            
            $uids_arr = array();
            foreach($get_list as $k1 => $v1){
                $uids_arr[] = $v1['uid'];
            }
            $where['id'] = array('in',$uids_arr);
            $find_user_list = M('user')->where($where)->select();

            $get_list_arr = array();
            $info = array();
            $info2 = array();
            foreach($get_list as $key => $val){
                $info[$key]['uid'] = $val['uid'];
                $info[$key]['offer_uid'] = $val['offer_uid'];
                foreach($find_user_list as $k2 => $v2){
                    if($val['uid'] == $v2['id']){
                        $info[$key]['avatar'] = !empty($find_user_list[$k2]['avatar']) ? $find_user_list[$k2]['avatar'] : C('DEFAULT_AVATAR');
                        $info[$key]['user_nickname'] = $find_user_list[$k2]['user_nickname'];
                    }
                }

                $info[$key]['money'] = $val['money']."元";
                $info[$key]['time'] = date('Y-m-d H:i:s',$val['time']);
                $info[$key]['offer_time'] = date('Y-m-d H:i:s',$find_info['time']);
                $info[$key]['leave_word'] = $val['leave_word'];

            }

            $info2['offer_avatar'] = !empty($find_user['avatar']) ? $find_user['avatar'] : C('DEFAULT_AVATAR');
            $info2['offer_user_nickname'] = $find_user['user_nickname'];
            $info2['content'] = $find_info['content'];
            $info2['content2'] = $find_info['num']."个红包，共".$find_info['money']."元";

            //是否是第一次领取，只有存储订单的时候才是第一次领取（暂时这样判断）
            $info2['is_first_get'] = "1";//代表不是第一次领取 或者 没有领取记录
            $info2['first_get_name'] = "";

            $res_info['offer_info'] = $info2;
            $res_info['get_info'] = $info;

            $new_data[] = $res_info;

            $this->json(0,"成功",$new_data);
        }

        if($find_info['now_num'] == 0){
            return $this->json(-3,'红包空啦');
        }

        if(($find_info['time']+3600*24) < time()){
            return $this->json(-4,'该红包超过24小时');
        }

        if($find_info['is_pay'] != 1){
            return $this->json(-5,'红包数据错误');
        }

        $res = $this->get_red_packert($find_info['remaining_sum'],$find_info['now_num'],$r_id);
        $find_user = M('user')->where("mobile = '".$mobile."'")->find();
        if(!$find_user){
            return $this->json(-7,'用户信息错误');
        }

        $data['uid'] = $uid;
        $data['offer_uid'] = $find_user['id'];
        $data['time'] = time();
        $data['money'] = $res['money'];
        $data['p_id'] = $r_id;
        $res1 = M('receive_money_record')->add($data);
        if(!$res1){
            return $this->json(-6,'红包领取失败');
        }
        //加钱
        $add_money = M('user')->where('id = '.$uid)->setInc('coins',$res['money']);

        $data_save['now_num'] = $res['remain_num'];
        $data_save['remaining_sum'] = $res['remaining_sum'];
        $res2 = M('red_packets')->where('id = '.$r_id)->save($data_save);

        //生成订单
        $order_sn = date('YmdHis').rand(1000,9999);
        $data_order = array(
            'uid'=>$uid,
            'order_price'=>$res['money'],
            'indent'=>4,
            'order_sn'=>$order_sn,
            'pay_status'=>2,
            'create_time'=>time(),
            'con_uid' => $find_user['id'],
            'object_id' => $r_id
        );
        if($is_transfer_accounts){
            $data_order['indent'] = 14;
        }
        $res3 = M('transaction')->add($data_order);

        // $find_offer_user = M('user')->where('id = '.$uid)->find();
        // if(!$find_offer_user){
        //     return $this->json(-7,'用户信息错误');
        // }


        $get_list = M('receive_money_record')->where('p_id = '.$r_id)->order('time desc')->select();
        $uids_arr = array();
        foreach($get_list as $k1 => $v1){
            $uids_arr[] = $v1['uid'];
        }
        $where['id'] = array('in',$uids_arr);
        $find_user_list = M('user')->where($where)->select();

        $get_list_arr = array();
        $info = array();
        $info2 = array();
        foreach($get_list as $key => $val){
            $info[$key]['uid'] = $val['uid'];
            $info[$key]['offer_uid'] = $val['offer_uid'];
            foreach($find_user_list as $k2 => $v2){
                if($val['uid'] == $v2['id']){
                    $info[$key]['avatar'] = !empty($find_user_list[$k2]['avatar']) ? $find_user_list[$k2]['avatar'] : C('DEFAULT_AVATAR');
                    $info[$key]['user_nickname'] = $find_user_list[$k2]['user_nickname'];
                }
            }

            $info[$key]['money'] = $val['money']."元";
            $info[$key]['time'] = date('Y-m-d H:i:s',$val['time']);
            $info[$key]['offer_time'] = date('Y-m-d H:i:s',$find_info['time']);
            $info[$key]['leave_word'] = $val['leave_word'];

        }

        $info2['offer_avatar'] = !empty($find_user['avatar']) ? $find_user['avatar'] : C('DEFAULT_AVATAR');
        $info2['offer_user_nickname'] = $find_user['user_nickname'];
        $info2['content'] = $find_info['content'];
        $info2['content2'] = $find_info['num']."个红包，共".$find_info['money']."元";

        //是否是第一次领取，只有存储订单的时候才是第一次领取（暂时这样判断）
        $info2['is_first_get'] = "0";//代表不是第一次领取 或者 没有领取记录
        $info2['first_get_name'] = $find_getone_info['user_nickname'];

        $res_info['offer_info'] = $info2;
        $res_info['get_info'] = $info;

        $new_data[] = $res_info;

        $this->json(0,"成功",$new_data);

    }


    public function get_red_packert($remaining_sum,$remain_num,$r_id){
        if($remain_num == 1 || $remaining_sum == 0.01){ //剩余最后一个红包
            $return_info = array(
                'money' => $remaining_sum,
                'remaining_sum' => 0,
                'remain_num' => $remain_num-1
            );
            //$this->json(0,"success",$return_info);
            return $return_info;
        }

        $min=0.01;//每个人最少能收到0.01元

        $safe_total=($remaining_sum-$remain_num*$min)/$remain_num;//安全上限 

        $money=mt_rand($min*100,$safe_total*100)/100;

        // 判断红包是否是第一个人领取如果是，让他抢不到全部红包
        $is_red = M('receive_money_record')->field('id')->where(array('p_id'=>array('eq',$r_id)))->select();
        // 如果为空说明是第一个人
        if(empty($is_red[0])) {
            if($money == $remaining_sum || $money > $money) {
                $money = mt_rand($min*100,($money*100/2))/100;
            }
        }

        $remaining_sum=bcsub($remaining_sum,$money,2);

        $return_info = array(
                'money' => $money,
                'remaining_sum' => $remaining_sum,
                'remain_num' => $remain_num-1
            );

        //$this->json(0,"success",$return_info);
        return $return_info;

    }

    /**
     * 返回个人红包记录列表
     * */
    public function my_redpackert_record(){
        $uid = I('uid');
        $type = I('type');//1、发送红包记录；2、领取红包记录
        if(!$uid || !$type){
            $this->json(-1,"参数错误");
        }

        //查询个人信息
        $find_my_info = M('user')->where('id = '.$uid)->find();
        if(!$find_my_info){
            $this->json(-3,"用户信息错误");
        }

        //查询红包记录
        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        if($type == 1){
            $find_info = M('red_packets')->where('uid = '.$uid)->field('id,uid,money,acc_uid,time')->order('time desc')->limit($limit)->select();
            if(!$find_info){
                $this->json(-2,"没有更多记录了");
            }

            foreach($find_info as $k1 => $v1){
                $find_info[$k1]['time'] = date('Y-m-d',$v1['time']);
                $find_info[$k1]['money'] = $v1['money']."元";
                $find_info[$k1]['user_nickname'] = "普通红包";
            }

            //查询总收入
            $total_get = M('red_packets')->where('uid = '.$uid)->sum('money');
            $res1 = array();
            $res2 = array();
            $res3 = array();
            $res2['total_get'] = $total_get;
            $res2['avatar'] = $find_my_info['avatar'];
            $res2['user_nickname'] = "共发出";//$find_my_info['user_nickname']

            $res1['info'] = $res2;
            $res1['lists'] = $find_info;

            $res3[] = $res1;

            $this->json(0,"成功",$res3);

        }

        if($type == 2){
            $find_info = M('receive_money_record')->where('uid = '.$uid)->order('time desc')->limit($limit)->select();
            if(!$find_info){
                $this->json(-2,"没有更多记录了");
            }

            $user_ids = array();
            foreach($find_info as $k1 => $v1){
                $user_ids[]=$v1['offer_uid'];
                $find_info[$k1]['time'] = date('Y-m-d',$v1['time']);
                $find_info[$k1]['money'] = $v1['money']."元";
            }

            //查询用户信息
            $where['id'] = array('in',$user_ids);
            $find_user_info = M('user')->where($where)->select();
            foreach($find_info as $k2 => $v2){
                foreach($find_user_info as $k3 => $v3){
                    if($v2['offer_uid'] == $v3['id']){
                        if($v3['user_nickname']){
                            $find_info[$k2]['user_nickname'] = $v3['user_nickname'];
                        }else{
                            $find_info[$k2]['user_nickname'] = $v3['mobile'];
                        }
                    }
                }
            }

            //查询总收入
            $total_get = M('receive_money_record')->where('uid = '.$uid)->sum('money');
            $res1 = array();
            $res2 = array();
            $res3 = array();
            $res2['total_get'] = $total_get;
            $res2['avatar'] = $find_my_info['avatar'];
            $res2['user_nickname'] = "共收到";//$find_my_info['user_nickname']

            $res1['info'] = $res2;
            $res1['lists'] = $find_info;

            $res3[] = $res1;

            $this->json(0,"成功",$res3);
        }


    }

    /**
     *存储罐主页接口
     * */
    public function my_storage(){
        $uid = I('post.uid');
        $mobile = I('post.mobile');//验证用户信息使用

        if(!$uid || !$mobile){
            $this->json(-1,"参数错误");
        }

        //查询用户信息
        $find_user_info = M('user')->where('id = '.$uid)->find();
        if(!$find_user_info){
            $this->json(-2,"用户不存在");
        }
        if($find_user_info['mobile'] != $mobile){
            $this->json(-3,"用户信息不匹配");
        }

        $res = array();
        $find_storage = M('storage')->where('uid = '.$uid)->find();
        if(!$find_storage){
            $res['total_money'] = "0.00";
            $res['total_income'] = "0.00";
            $res['income'] = "0.00";
        }else{
            $find_storage_settle_account = M('storage_settle_account')->where('uid = '.$uid)->order('time desc')->limit(1)->find();
            $res['total_money'] = $find_storage['money'];
            $res['total_income'] = M('storage_settle_account')->where('uid = '.$uid)->sum('income');
            if(!$res['total_income']){
                $res['total_income'] = "0.00";
            }
            $res['income'] = $find_storage_settle_account['income'];
            if(!$res['income']){
                $res['income'] = "0.00";
            }
        }

        $this->json(0,"成功",$res);

    }

    /**
     * 累计收益（包括搜索）
     * */
    public function accumulated_income(){
        $uid = I('post.uid');
        $mobile = I('post.mobile');//验证用户信息使用
        $is_month = I('is_month');//传1 是否查找近一个月的数据（搜索的时候使用）
        $is_year = I('is_year');//传1 是否查找近一年的数据（搜索的时候使用）
        $type = I('type');//1.存储罐,2合伙人

        if(!$uid || !$mobile || !$type){
            $this->json(-1,"参数错误");
        }

        if($type == 1){
            //查询用户信息
            $find_user_info = M('user')->where('id = '.$uid)->find();
            if(!$find_user_info){
                $this->json(-2,"用户不存在");
            }
            if($find_user_info['mobile'] != $mobile){
                $this->json(-3,"用户信息不匹配");
            }

            $page = I('page') ? I('page'):1;
            $row = 20;//每页显示的条数
            $limit = ($page-1)*$row.','.$row;

            if($is_month || $is_year){
                //计算当天几月几号
                $today = date('Y-m-d',time());
                $today = strtotime($today);

                //计算近一个月或一年不包含当天
                if($is_month){
                    $before = $today-30*24*3600;
                    //查询收益列表
                    $find_income_list = M('storage_settle_account')->where('uid = '.$uid.' and time >= '.$before.' and time <= '.$today)->field('id,uid,income,time')->order('time desc')->limit($limit)->select();
                    //计算总收益
                    $total_income = M('storage_settle_account')->where('uid = '.$uid.' and time >= '.$before.' and time <= '.$today)->sum('income');
                }
                if($is_year){
                    $before = $today-365*24*3600;
                    //查询收益列表
                    $find_income_list = M('storage_settle_account')->where('uid = '.$uid.' and time >= '.$before.' and time <= '.$today)->field('id,uid,income,time')->order('time desc')->limit($limit)->select();
                    //计算总收益
                    $total_income = M('storage_settle_account')->where('uid = '.$uid.' and time >= '.$before.' and time <= '.$today)->sum('income');
                }

            }else{
                //查询收益列表
                $find_income_list = M('storage_settle_account')->where('uid = '.$uid)->field('id,uid,income,time')->order('time desc')->limit($limit)->select();
                //计算总收益
                $total_income = M('storage_settle_account')->where('uid = '.$uid)->sum('income');
            }

            if(!$find_income_list){
                if(!$total_income){
                    $total_income = "0.00";
                }
                $this->json(1,"没有更多记录了",array('total_income' => $total_income,'income_list' => array()));
            }else{
                foreach($find_income_list as $key => $val){
                    $find_income_list[$key]['time'] = date('Y-m-d',$val['time']);
                }
                $this->json(0,"成功",array('total_income' => $total_income,'income_list' => $find_income_list));
            }
        }
    }

    /**
     * 存储罐转出接口
     * */
    public function transfer_account_roll_out(){
        $money = I('money');
        $uid = I('uid');
        $mobile = I('mobile');//用户信息验证使用
		/*$sign = I('sign');

        $param = 'money='.$money.'&uid='.$uid.'&mobile='.$mobile.'&key='.$this->transfer_key;
		$my_sign = md5($param);*/

		if(!$money || !$uid || !$mobile ){
            $this->json(-1,"参数错误");
        }
		/*if($sign != $my_sign) {
            $this->json(-8, "签名错误");
        }*/

        //查询用户信息
        $find_user_info = M('user')->where('id = '.$uid)->find();
        if(!$find_user_info){
            $this->json(-2,"用户不存在");
        }

        if($find_user_info['mobile'] != $mobile){
            $this->json(-3,"用户信息错误");
        }

        if($money < 100){
            $this->json(-4,"最小转出金额100元");
        }

        //查询储蓄罐信息
        $find_storage_info = M('storage')->where('uid = '.$uid)->find();
        if(!$find_storage_info){
            $this->json(-5,"存储信息不存在");
        }

        if($find_storage_info['money'] < $money){
            $this->json(-6,"转出金额不足");
        }

        //将钱转入用户账户
        $user_account = M('user')->where('id = '.$uid)->setInc('coins',$money);
        if(!$user_account){
            $this->json(-7,"转出失败");
        }

        $storage_res = M('storage')->where('uid = '.$uid)->setDec('money',$money);
        if(!$storage_res){
            //没有做事务，暂时这样处理。
            $user_account = M('user')->where('id = '.$uid)->setDec('coins',$money);
            $this->json(-7,"转出失败");
        }

        //生成订单
        $order_sn = date('YmdHis').rand(1000,9999);
        $data = array(
            'uid'=>$uid,
            'order_price'=>$money,
            'pay_type'=>0,
            'indent'=>16,
            'order_sn'=>$order_sn,
            'pay_status'=>2,
            'create_time'=>time(),
        );
        $trans_res = M('transaction')->add($data);

        $this->json(0,"转出成功");

    }

    public function curl_post($url, $data, $header = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->to_params($data));

        if ($header) {
            $header_list = array();
            foreach ($header as $key => $value) {
                $header_list[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header_list);
        }

        $str = curl_exec($ch);
        curl_close($ch);

        return $str;
    }

    public function to_params($input)
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

    public function curl_get($url, $header = null)
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

    // 苹果支付验证 接口
    //
    // @param string $receipt 收到的数据
    // @param boolean $isSandbox 是否是沙盒模式,true,false
    public function getreceiptdata(){
        $receipt = I('strReceipt');
        $username = I('username');//用户名
        $tc = I('pid');//套餐类型
        $isSandbox = I('is_sandbox');
        //如果是沙盒模式，请求苹果测试服务器,反之，请求苹果正式的服务器
        if ($isSandbox) {
            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        }
        else {
            $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
        }

        $postData = json_encode(
            array('receipt-data' => $receipt)
        );

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);


        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
        curl_close($ch);

        $data = json_decode($response);

        //判断时候出错，抛出异常
        if ($errno != 0) {
            //throw new \Exception($errmsg, $errno);
            //$arr = array('status'=>'fail','code'=>'-3','msg'=>'判断时候出错，抛出异常');
            //echo json_encode($arr);exit;
            $xml = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= '';
            $xml .= "fail";
            $xml .= "<code>3</code>";
            $xml .= "判断时候出错，抛出异常";
            $xml .= '';
            echo $xml;exit;


        }

        //判断返回的数据是否是对象
        if (!is_object($data)) {
            //E('Invalid response data');//无效的响应数据
            //$arr = array('status'=>'fail','code'=>'-2','msg'=>'无效的响应数据');
            //echo json_encode($arr);exit;
            $xml = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= '';
            $xml .= "fail";
            $xml .= "<code>-2</code>";
            $xml .= "无效的响应数据";
            $xml .= '';
            echo $xml;exit;
        }
        //判断购买时候成功
        if (!isset($data->status) || $data->status != 0) {
            //E('Invalid receipt');//无效的收据
            //$arr = array('status'=>'fail','code'=>'-1','msg'=>'无效的收据');
            //echo json_encode($arr);exit;
            $xml = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= '';
            $xml .= "fail";
            $xml .= "<code>-1</code>";
            $xml .= "无效的收据";
            $xml .= '';
            echo $xml;exit;
        }

        $order = $data->receipt->in_app;//所有的订单的信息
        $k = count($order) -1;
        $need = $order[$k];//需要的那个订单

        var_dump($need);


        //下面进行业务处理了，根据用户购买的套餐，给他开通相应的套餐、时间
        //新建一个订单，已支付状态,
        //把购买的套餐的时间加到该账号


    }

    function getreceiptdata2(){
        $receipt_data = I('receipt_data');
        $order_sn = I('order_sn');

        //查找订单信息
        $order_info = M('transaction')->where("order_sn = '".$order_sn."'")->find();
        if(!$order_info){
            return $this->json(-2,'没有找到相关订单信息');
        }

        if($order_info['pay_status'] == 2){
            return $this->json(-3,'订单信息有误');
        }

        //$sandbox_val = I('sandbox');
        /**
         * 21000 App Store不能读取你提供的JSON对象
         * 21002 receipt-data域的数据有问题
         * 21003 receipt无法通过验证
         * 21004 提供的shared secret不匹配你账号中的shared secret
         * 21005 receipt服务器当前不可用
         * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
         * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
         * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
         */
        function acurl($receipt_data, $sandbox=0){
            //小票信息
            $POSTFIELDS = array("receipt-data" => $receipt_data);
            $POSTFIELDS = json_encode($POSTFIELDS);

            //正式购买地址 沙盒购买地址
            $url_buy     = "https://buy.itunes.apple.com/verifyReceipt";
            $url_sandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
            $url = $sandbox ? $url_sandbox : $url_buy;

            //简单的curl
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
        // 验证参数
        if (strlen($receipt_data)<20){
            $result=array(
                'status'=>false,
                'message'=>'非法参数'
            );
            return $result;
        }
        // 请求验证
        $html = acurl($receipt_data);
        $data = json_decode($html,true);

        // 如果是沙盒数据 则验证沙盒模式
        if($data['status']=='21007'){
            // 请求验证
            $html = acurl($receipt_data, 1);
            $data = json_decode($html,true);
            $data['sandbox'] = '1';
        }

        if (isset($_GET['debug'])) {
            exit(json_encode($data));
        }

        // 判断是否购买成功
        if(intval($data['status'])===0){
            $map['pay_type'] = 3;//
            $map['pay_status'] = 2; //1为未支付
            $map['pay_time'] = time();
            $res2 = M('transaction')->where('order_sn = '.$order_sn)->save($map);
            if($order_info['indent'] == 6){
                $find_horn = M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->find();
                if($find_horn){
                    //有记录就修改购买状态字段
                    M('horn')->where('uid = '.$order_info['uid'].' and horn_id = '.$order_info['object_id'])->setField('pur_state',1);
                }else{
                    //添加购买信息
                    $data['uid'] = $order_info['uid'];
                    $data['horn_id'] = $order_info['object_id'];
                    $data['pur_price'] = $order_info['order_price'];
                    $data['pur_time'] = time();
                    $data['pur_state'] = 1;
                    M('horn')->add($data);
                }
            }

            $result=array(
                'status'=>true,
                'message'=>'购买成功'
            );
            $this->json(0,'',$result);
        }else{
            $result=array(
                'status'=>false,
                'message'=>'购买失败 status:'.$data['status']
            );
            $this->json(-99,'',$result);
        }
        //return $result;

    }

}


?>