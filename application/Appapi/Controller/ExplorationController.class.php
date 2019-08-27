<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/31 0031
 * Time: 下午 1:07
 */
namespace Appapi\Controller;

use Common\Controller\ApibaseController;
use Common\Common\Easemob;

class ExplorationController extends ApibaseController{
    public function __construct()
    {
        parent::__construct();
        \Seaslog::setBasePath('data/log/exploration');
    }

    /**
     * 附近的人功能接口
     * */
    public function nearby_user(){
        $uid = I('uid');
        $lng = I('lng');
        $lat = I('lat');
        $range = I('range');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        if(!$range){
            $range = 10000000;
        }

        $data['uid'] = $uid;
        $data['lng'] = $lng;
        $data['lat'] = $lat;
        $data['create_time'] = time();

        //判断数据表中是否有此用户数据
        $find_nearby = M('nearby_user')->where('uid = '.$uid)->find();
        if(!$find_nearby){
            $add_res = M('nearby_user')->add($data);
            if(!$add_res){
                return $this->json(-2,'请重新请求数据');
            }
        }else{
            $add_res = M('nearby_user')->where('uid = '.$uid)->save($data);
            if(!$add_res){
                return $this->json(-2,'请重新请求数据');
            }
        }

        //计算附近满足条件的用户
        $keep = time()-60*60*24;//位置信息保留20分钟
        $field = "*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$lat."*PI()/180-lat*PI()/180)/2),2)";
        $field.= "+COS(".$lat."*PI()/180)*COS(lat*PI()/180)*POW(SIN((".$lng."*PI()/180-lng*PI()/180)/2),2)))*1000) AS juli";


        $str = "ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$lat."*PI()/180-lat*PI()/180)/2),2)";
        $str.= "+COS(".$lat."*PI()/180)*COS(lat*PI()/180)*POW(SIN((".$lng."*PI()/180-lng*PI()/180)/2),2)))*1000) <= ".$range." and uid != ".$uid." and create_time >= ".$keep;
        $find_nearby_user = M('nearby_user')->field($field)->where($str)->select();////where语句中原来的数据  "'juli' <= ".$range." and uid != ".$uid.' and create_time >= '.$keep
        if(!$find_nearby_user){
            return $this->json(-3,'没有更多附近的人');
        }

        //存放用户uid
        $uids_arr = array();
        foreach($find_nearby_user as $key => $val){
            $uids_arr[] = $val['uid'];
        }
        $uids_arr = array_unique($uids_arr);
        //$uids_arr = array_merge(array_diff($uids_arr, array($uid)));//去除自己的uid
        if(empty($uids_arr)){
            return $this->json(-3,'没有更多附近的人');
        }
        $where['id'] = array('in',$uids_arr);
        $find_user_info = M('user')->where($where)->select();

        //查找自己的信息
        $find_my_info = M('user')->where('id = '.$uid)->find();

        foreach($find_nearby_user as $key2 => $val2){
            foreach($find_user_info as $key3 => $val3){
                if($val2['uid'] == $val3['id']){
                    $find_nearby_user[$key2]['user_nickname'] = $val3['user_nickname'];
                    $find_nearby_user[$key2]['avatar'] = $val3['avatar'] ? $val3['avatar'] : C('DEFAULT_AVATAR');
                    $find_nearby_user[$key2]['mobile'] = $val3['mobile'];
                    $find_nearby_user[$key2]['sex'] = $val3['sex'];
                    $find_nearby_user[$key2]['signature'] = $val3['signature'];
                    if($find_my_info){
                        //判断自己和其他人是否是人脉关系
                        if($val3['top_father'] == $find_my_info['top_father'] || $val3['pur_top_father'] == $find_my_info['top_father'] || $val3['mobile'] == $find_my_info['pur_mobile']){
                            $find_nearby_user[$key2]['is_connection'] = "1";
                        }else{
                            $find_nearby_user[$key2]['is_connection'] = "0";
                        }
                    }
                }
            }
        }


        return $this->json(0,'成功',$find_nearby_user);
    }


    /**
     * 清除位置信息
     * */
    public function remove_location(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        $data['lng'] = "";
        $data['lat'] = "";
        $data['create_time'] = "";
        $remove_res = M('nearby_user')->where('uid = '.$uid)->save($data);

        if(!$remove_res){
            return $this->json(-2,'操作有误');
        }

        return $this->json(0,'位置信息已清除');

    }

    /**
     *喇叭附近的人喊话功能（只有保留着附近的人的定位信息的人才可以收到推送，定位信息只保留20分钟）
     * */
    public function nearby_notice(){
        $uid = I('uid');
        $lng = I('lng');
        $lat = I('lat');
        $range = I('range');
        $content = I('content');//喊话的内容
        if(!$uid || !$range || !$lng || !$lat){
            return $this->json(-1,'缺少参数');
        }

        $horn_id = M('horn_data')->where('distance = '.$range)->find();
        if($horn_id){
            $horn_is_exit = M('horn')->where("uid = ".$uid." and horn_id = ".$horn_id['horn_id'])->find();
            if(!$horn_is_exit){
                return $this->json(-2,'只能使用一次');
            }
        }

        //重新添加定位信息
        $data['uid'] = $uid;
        $data['lng'] = $lng;
        $data['lat'] = $lat;
        $data['create_time'] = time();
        M('nearby_user')->where('uid = '.$uid)->save($data);

        //发布消息（当别人开启附近的人的功能的时候并且满足距离条件的用户才可以收到消息）
        //计算附近满足条件的用户
        $keep = time()-3600*20;//位置信息保留20分钟
        $field = "*,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$lat."*PI()/180-lat*PI()/180)/2),2)";
        $field.= "+COS(".$lat."*PI()/180)*COS(lat*PI()/180)*POW(SIN((".$lng."*PI()/180-lng*PI()/180)/2),2)))*1000) AS juli";

        $str = "ROUND(6378.138*2*ASIN(SQRT(POW(SIN((".$lat."*PI()/180-lat*PI()/180)/2),2)";
        $str.= "+COS(".$lat."*PI()/180)*COS(lat*PI()/180)*POW(SIN((".$lng."*PI()/180-lng*PI()/180)/2),2)))*1000) <= ".$range." and uid != ".$uid." and create_time >= ".$keep;
        $find_nearby_user = M('nearby_user')->field($field)->where($str)->select();//where语句中原来的数据  "'juli' <= ".$range." and uid != ".$uid.' and create_time >= '.$keep

        //当有用户的时候，才可以推送消息。
        if($find_nearby_user){
            //收集手机号
            $mobile_arr = array();
            $uids_arr = array();
            foreach($find_nearby_user as $key => $val){
                $uids_arr[] = $val['uid'];
            }

            $where['id'] = array('in',$uids_arr);
            $find_users = M('user')->where($where)->select();
            foreach($find_users as $k => $v){
                $mobile_arr[] = $v['mobile'];
            }

            //通过环信发送消息
            $send_arr = array();
            $i=0;
            foreach($mobile_arr as $key => $val){
                if(count($send_arr[$i]) >= 20){
                    $i++;
                }
                $send_arr[$i][]=$val;
                if($key == 99){
                    break;
                }
            }

            //查找个人信息
            $find_myself_info = M('user')->where('id = '.$uid)->find();
            $ease = new Easemob();
            $content = "附近的人向您打招呼：".$content;
            foreach($send_arr as $kk => $vv){
                foreach($send_arr[$kk] as $kk2 => $vv2){
                    $mobile = explode(",",$send_arr[$kk][$kk2]);
                    $ext = array('type'=>'1','mobile'=>$send_arr[$kk][$kk2],'user_nickname'=>$find_myself_info['user_nickname'],'avatar'=>$find_myself_info['avatar'],'content'=>$content);
                    $res = $ease->sendText($find_myself_info['mobile'],'users',$mobile,$content,$ext);
                }
            }

            $horn_id = M('horn_data')->where('distance = '.$range)->find();
            if($horn_id){
                M('horn')->where("uid = ".$uid." and horn_id = ".$horn_id['horn_id'])->delete();
            }

            return $this->json(0,'成功');
        }else{
            return $this->json(-3,'该范围内没有用户');
        }

    }

    /**
     * 三方客请求消息操作接口(支付完成后的确认)
     * */
    public function start_tripartite(){
        $uid = I('uid');//验证是否是自己发起的三方客
        $tri_id = I('tri_id');
        $request_type = I('request_type');// 1、确认；2、取消；3、完成；
        if(!$uid || !$tri_id || !$request_type){
            return $this->json(-1,'缺少参数');
        }

        //查询三方客信息
        $find_tripartite_info = M('tripartite')->where('tri_id = '.$tri_id)->find();
        if(!$find_tripartite_info){
            return $this->json(-2,'未找到该三方客信息');
        }

        //判断邀请者身份
//        if($find_tripartite_info['uid'] != $uid){
//            return $this->json(-3,'三方客邀请者信息不符');
//        }
        if($find_tripartite_info['tri_state'] == 3){
            return $this->json(-6,'此三方客已经完成');
        }

        if($request_type == 1){
            if($find_tripartite_info['tri_state'] == 4){
                return $this->json(-5,'此三方客已经被取消');
            }
            $data['tri_state'] = 2;
            $data['progress_time'] = time();
            $start_res = M('tripartite')->where('tri_id = '.$tri_id)->save($data);
        }
        if($request_type == 2){
            if($find_tripartite_info['tri_state'] != 4){
                $data['tri_state'] = 4;
                $data['cancel_time'] = time();
                $start_res = M('tripartite')->where('tri_id = '.$tri_id)->save($data);

                //生成退款订单
                $order_sn = date('YmdHis').rand(1000,9999);
                $data = array(
                    'uid'=>$find_tripartite_info['uid'],
                    'order_price'=>$find_tripartite_info['price'],
                    'indent'=>5,
                    'order_sn'=>$order_sn,
                    'pay_status'=>2,
                    'create_time'=>time(),
                );
                $trans_res = M('transaction')->add($data);

                //将钱退回发起人账户
                M('user')->where('id = '.$find_tripartite_info['uid'])->setInc('coins',$find_tripartite_info['price']);
            }else if($find_tripartite_info['tri_state'] == 4){
                $start_res = true;
            }
        }

        if($request_type == 3){
            if($find_tripartite_info['tri_state'] == 4){
                return $this->json(-5,'此三方客已经被取消');
            }
            $data['tri_state'] = 3;
            $data['finish_time'] = time();
            $start_res = M('tripartite')->where('tri_id = '.$tri_id)->save($data);

            //生成订单
            $order_sn = date('YmdHis').rand(1000,9999);
            $data = array(
                'uid'=>$find_tripartite_info['con_uid'],
                'order_price'=>$find_tripartite_info['price'],
                'indent'=>8,
                'order_sn'=>$order_sn,
                'pay_status'=>2,
                'create_time'=>time(),
            );
            $trans_res = M('transaction')->add($data);

            //将钱打到对方账户上
            M('user')->where('id = '.$find_tripartite_info['con_uid'])->setInc('coins',$find_tripartite_info['price']);

        }

        if(!$start_res){
            return $this->json(-4,'失败');
        }

        return $this->json(0,'成功');

    }

    /**
     * 担保明细接口
     * */
    public function guarantee_detail(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查询本人相关的担保信息
        $find_guarantee_info = M('tripartite')->where("uid = ".$uid." or con_uid = ".$uid)->order('initiate_time desc')->limit($limit)->select();

        if(!$find_guarantee_info){
            return $this->json(-2,'没有更多信息了');
        }

        //收集用户uid
        $uids = array();
        foreach($find_guarantee_info as $key => $val){
            $uids[]=$val['uid'];
            $uids[]=$val['con_uid'];
            if($val['tri_state'] == 1 && $val['uid'] != $uid){
                unset($find_guarantee_info[$key]);
            }
        }
        $uids = array_unique($uids);
        $find_guarantee_info = array_merge($find_guarantee_info);

        $where['id'] = array('in',$uids);
        //查询用户信息
        $find_users = M('user')->where($where)->select();
        foreach($find_guarantee_info as $k1 => $v1){
            foreach($find_users as $k2 => $v2){
                if($v1['uid'] == $v2['id']){
                    if($v2['user_nickname']){
                        $find_guarantee_info[$k1]['name'] = $v2['user_nickname'];
                    }else{
                        $find_guarantee_info[$k1]['name'] = $v2['mobile'];
                    }
                    $find_guarantee_info[$k1]['avatar'] = $v2['avatar'] ? $v2['avatar'] : C('DEFAULT_AVATAR');
                }
                if($v1['con_uid'] == $v2['id']){
                    if($v2['user_nickname']){
                        $find_guarantee_info[$k1]['con_name'] = $v2['user_nickname'];
                    }else{
                        $find_guarantee_info[$k1]['con_name'] = $v2['mobile'];
                    }
                    $find_guarantee_info[$k1]['con_avatar'] = $v2['avatar'] ? $v2['avatar'] : C('DEFAULT_AVATAR');
                }
            }

            if($v1['tri_state'] == 1){
                $find_guarantee_info[$k1]['state_content'] = '发起中';
            }else if($v1['tri_state'] == 2){
                $find_guarantee_info[$k1]['state_content'] = '进行中';
            }else if($v1['tri_state'] == 3){
                $find_guarantee_info[$k1]['state_content'] = '已完成';
            }else if($v1['tri_state'] == 4){
                $find_guarantee_info[$k1]['state_content'] = '已取消';
            }

            if($v1['initiate_time']){
                $find_guarantee_info[$k1]['initiate_time'] = date("Y年m月d日",$v1['initiate_time']);
                $find_guarantee_info[$k1]['time'] = date("Y年m月d日",$v1['initiate_time']);
            }
            if($v1['progress_time']){
                $find_guarantee_info[$k1]['progress_time'] = date("Y年m月d日",$v1['progress_time']);
                $find_guarantee_info[$k1]['time'] = date("Y年m月d日",$v1['progress_time']);
            }
            if($v1['finish_time']){
                $find_guarantee_info[$k1]['finish_time'] = date("Y年m月d日",$v1['finish_time']);
                $find_guarantee_info[$k1]['time'] = date("Y年m月d日",$v1['finish_time']);
            }
            if($v1['cancel_time']){
                $find_guarantee_info[$k1]['cancel_time'] = date("Y年m月d日",$v1['cancel_time']);
                $find_guarantee_info[$k1]['time'] = date("Y年m月d日",$v1['cancel_time']);
            }


        }

        return $this->json(0,'成功',$find_guarantee_info);

    }

    /**
     * 担保明细完成或取消操作接口
     * */
    public function guarantee_operation(){
        $tri_id = I('tri_id');//担保信息id
        $uid = I('uid');//用户uid
        $request_type = I('request_type');// 1、完成；2、取消

        if(!$tri_id || !$uid || !$request_type){
            return $this->json(-1,'缺少参数');
        }

        //查询自己的用户信息
        //$find_myself_info = M('user')->where('id = '.$uid)->find();

        //查询三方客信息
        $find_tripartite_info = M('tripartite')->where('tri_id = '.$tri_id)->find();
        if(!$find_tripartite_info){
            return $this->json(-2,'没有查到该三方客信息');
        }

        if($find_tripartite_info['tri_state'] == 4){
            return $this->json(-3,'此三方客已经被取消');
        }

        if($find_tripartite_info['uid'] != $uid && $find_tripartite_info['con_uid'] != $uid){
            return $this->json(-4,'用户和此三方客信息不符');
        }

        if($find_tripartite_info['tri_state'] == 1){
            //此时还在邀请中，对方并没有同意，可以直接撤回消息
            if($request_type == 2){
                $data['tri_state'] = 4;
                $res = M('tripartite')->where('tri_id = '.$tri_id)->save($data);

                //生成订单
                $order_sn = date('YmdHis').rand(1000,9999);
                $data = array(
                    'uid'=>$uid,
                    'order_price'=>$find_tripartite_info['price'],
                    'indent'=>5,
                    'order_sn'=>$order_sn,
                    'pay_status'=>2,
                    'create_time'=>time(),
                );
                $trans_res = M('transaction')->add($data);

                //退钱到用户账户

                $refund_res = M('user')->where('id = '.$find_tripartite_info['uid'])->setInc('coins',$find_tripartite_info['price']);
                $refund_res = M('tripartite')->where('tri_id = '.$tri_id)->setField('cancel_time',time());
                return $this->json(2,'取消成功');
            }
        }else{
            //查询发起人手机号
            if($find_tripartite_info['con_uid'] == $uid){
                $find_user_mobile = M('user')->where('id = '.$find_tripartite_info['uid'])->find();
            }
            if($find_tripartite_info['uid'] == $uid){
                $find_user_mobile = M('user')->where('id = '.$find_tripartite_info['con_uid'])->find();
            }

            if($request_type == 1){
                //查询24小时内是否发过该请求
                $find_request_info = M('tripartite_remind_record')->where("mobile = '".$find_user_mobile['mobile']."' and type = 1")->find();
                if($find_request_info){
                    $range = time()-$find_request_info['time'];
                    if($range < 24*3600){
                        return $this->json(-5,'24小时之内只能发送一次');
                    }
                }

                $mobile = explode(",",$find_user_mobile['mobile']);
                $ease = new Easemob();
                //$content = '您收到了三方客的完成申请';
                if($find_user_mobile['user_nickname']){
                    $content = $find_user_mobile['user_nickname']."的三方客完成申请";
                }else{
                    $content = $find_user_mobile['mobile']."的三方客完成申请";
                }
                $ext = array('type'=>'1','mobile'=>$find_user_mobile['mobile'],'name'=>$find_user_mobile['user_nickname'],'content'=>$content,"tri_id"=>$tri_id);
                $res = $ease->sendText('三方客完成申请'.$find_user_mobile['mobile'],'users',$mobile,$content,$ext);

                if($find_request_info){
                    $map['time'] = time();
                    M('tripartite_remind_record')->where("mobile = '".$find_user_mobile['mobile']."' and type = 1")->save($map);
                }else{
                    $map['time'] = time();
                    $map['type'] = 1;
                    $map['mobile'] = $find_user_mobile['mobile'];
                    M('tripartite_remind_record')->add($map);
                }

                return $this->json(1,'完成申请已发送');
            }

            if($request_type == 2){
                //查询24小时内是否发过该请求
                $find_request_info = M('tripartite_remind_record')->where("mobile = '".$find_user_mobile['mobile']."' and type = 2")->find();
                if($find_request_info){
                    $range = $find_request_info['time']-time();
                    if($range < 24*3600){
                        return $this->json(-5,'24小时之内只能发送一次');
                    }
                }

                $mobile = explode(",",$find_user_mobile['mobile']);
                $ease = new Easemob();
                //$content = '您收到了三方客的取消申请';
                if($find_user_mobile['user_nickname']){
                    $content = $find_user_mobile['user_nickname']."的三方客撤销申请";
                }else{
                    $content = $find_user_mobile['mobile']."的三方客撤销申请";
                }
                $ext = array('type'=>'1','mobile'=>$find_user_mobile['mobile'],'name'=>$find_user_mobile['user_nickname'],'content'=>$content,"tri_id"=>$tri_id);
                $res = $ease->sendText('三方客撤销申请'.$find_user_mobile['mobile'],'users',$mobile,$content,$ext);

                if($find_request_info){
                    $map['time'] = time();
                    M('tripartite_remind_record')->where("mobile = '".$find_user_mobile['mobile']."' and type = 2")->save($map);
                }else{
                    $map['time'] = time();
                    $map['type'] = 2;
                    $map['mobile'] = $find_user_mobile['mobile'];
                    M('tripartite_remind_record')->add($map);
                }

                return $this->json(1,'取消申请已发送');
            }
        }


    }

    /**
     * 发送查看好友位置信息接口
     * */
    public function send_location_request(){
        $my_mobile = I('my_mobile');
        $the_other_mobile = I('the_other_mobile');
        if(!$my_mobile || !$the_other_mobile){
            return $this->json(-1,'参数错误');
        }

        if($my_mobile == $the_other_mobile){
            return $this->json(-2,'不能查看自己的信息');
        }

        //查询用户信息是否存在
        $find_my_info = M('user')->where("mobile = '".$my_mobile."'")->find();
        $find_the_other_info = M('user')->where("mobile = '".$the_other_mobile."'")->find();
        if(!$find_my_info || !$find_the_other_info){
            return $this->json(-3,'用户信息不存在');
        }

        //查看对方是否开启位置信息开关
        $find_switch_location_info = M('switch_location_info')->where("mobile = '".$the_other_mobile."' and is_open = 1")->find();
        if(!$find_switch_location_info){
            return $this->json(-4,'对方没有开启此功能');
        }

        $type = "1";
        //判断是好友或人脉
        $find_friend_info = M('user_friends')->where('f_uid = '.$find_my_info['id'].' and uid = '.$find_the_other_info['id'])->find();
        if(!$find_friend_info){
            if(($find_my_info['top_father'] == $find_the_other_info['top_father']) || ($find_my_info['mobile'] == $find_the_other_info['pur_mobile']) || ($find_my_info['pur_mobile'] == $find_the_other_info['mobile']) || ($find_my_info['top_father'] == $find_the_other_info['pur_top_father'])){
                $type = '2';
            }else{
                return $this->json(-5,'不是好友或人脉不能查看位置信息');
            }
        }

        //在这里增加扣积分的逻辑
        //这里的逻辑是判断前3次
        $resx = M('location_times')->where('mobile = '.$my_mobile)->find();
        if (!$resx){
            $datax['mobile'] = $my_mobile;
            $datax['count'] = 1;
            $datax['time'] = time();
            M('location_times')->add($datax);
        }else{
            if ($resx['count'] >3){

                //需要花钱或是积分
                if ($find_my_info['points'] < 20 && $find_my_info['coins'] < 2){
                    return $this->json(-6,'积分及余额不足,请前往充值');
                }else{
                    if ($find_my_info['points'] < 20 && $find_my_info['coins'] >=2){
                        //扣零钱
                        $re1 = M('user')->where('mobile ='.$my_mobile)->setDec('coins',2);
                        //给对方收益
                        $re2 = M('user')->where('mobile ='.$the_other_mobile)->setInc('coins',1.6);

                        if ($re1 && $re2){
                            \SeasLog::info('某人'.$my_mobile.'请求位置花费零钱记录,时间'.date('Y-m-d H:i:s',time()).'请求了别人信息:'.$the_other_mobile);
                        }else{
                            \SeasLog::info(' 错误消息提示:'.'某人'.$my_mobile.'请求位置花费零钱记录,时间'.date('Y-m-d H:i:s',time()).'请求了别人信息:'.$the_other_mobile);
                        }

                    }elseif ($find_my_info['points'] >=20){
                        //扣积分
                        $re3 = M('user')->where('mobile ='.$my_mobile)->setDec('points',20);
                        //给对方收益
                        $re4 = M('user')->where('mobile ='.$the_other_mobile)->setInc('coins',16);
                        if ($re3 && $re4){
                            \SeasLog::info('某人'.$my_mobile.'请求位置花费积分记录,时间'.date('Y-m-d H:i:s',time()).'请求了别人信息:'.$the_other_mobile);
                        }else{
                            \SeasLog::info(' 错误消息提示:'.'某人'.$my_mobile.'请求位置花费积分记录,时间'.date('Y-m-d H:i:s',time()).'请求了别人信息:'.$the_other_mobile);
                        }
                    }
                }

            }else{
                M('location_times')->where('mobile = '.$my_mobile)->setInc('count',1);
            }
        }
        

        if($type == '1'){
            if($find_friend_info['remark']){
                $content = $find_friend_info['remark']."查看了您的位置信息";
            }else{
                if($find_my_info['user_nickname']){
                    $content = $find_my_info['user_nickname']."查看了您的位置信息";
                }else{
                    $content = $find_my_info['mobile']."查看了您的位置信息";
                }
            }
        }else if($type == '2'){
            //查询人脉备注信息
            $find_conn_reamrk = M('connection_remark')->where('f_uid = '.$find_my_info['id'].' and uid = '.$find_the_other_info['id'])->find();
            if($find_conn_reamrk['remark']){
                $content = $find_conn_reamrk['remark']."查看了您的位置信息";
            }else{
                if($find_my_info['user_nickname']){
                    $content = $find_my_info['user_nickname']."查看了您的位置信息";
                }else{
                    $content = $find_my_info['mobile']."查看了您的位置信息";
                }
            }
        }else{
            //如果数据有问题就走这里
            if($find_my_info['user_nickname']){
                $content = $find_my_info['user_nickname']."查看了您的位置信息";
            }else{
                $content = $find_my_info['mobile']."查看了您的位置信息";
            }
        }

        $ease = new Easemob();
        $mobile = explode(",",$the_other_mobile);

        //因为是后台发送的消息，暂时让type=1
        $type = '1';
        $ext = array('type'=>$type,'mobile'=>$my_mobile,'user_nickname'=>$find_my_info['user_nickname'],'avatar'=>$find_my_info['avatar'],'content'=>$content,'send_time'=>time(),'location'=>'send');
        //$res = $ease->sendText($my_mobile,'users',$mobile,$content,$ext);
        $res = $ease->sendText('系统消息'.$my_mobile,'users',$mobile,$content,$ext);

        if($res['data']){
            //开始分配积分
            //M('user')->where("mobile = '".$my_mobile."'")->setDec('points',20);
            //M('user')->where("mobile = '".$the_other_mobile."'")->setInc('points',5);
        }

        return $this->json(0,'申请已发送',$res);

    }

    /**
     *返回位置经纬度接口
     * */
    public function return_position_info(){
        $lat = I('lat');
        $lng = I('lng');
        $my_mobile = I('my_mobile');
        $the_other_mobile = I('the_other_mobile');
        $send_time = I('send_time');
        if(!$my_mobile || !$the_other_mobile || !$lat || !$lng || !$send_time){
            return $this->json(-1,'参数错误');
        }

        $find_my_info = M('user')->where("mobile = '".$my_mobile."'")->find();
        $find_the_other_info = M('user')->where("mobile = '".$the_other_mobile."'")->find();

        $ease = new Easemob();
        $mobile = explode(",",$the_other_mobile);
        if((time()-$send_time) > 10){
            $type = '1';
            $content = '位置信息请求超时，对方网络状况可能不佳。';
            $ext = array('type'=>$type,'mobile'=>$my_mobile,'user_nickname'=>$find_my_info['user_nickname'],'avatar'=>$find_my_info['avatar'],'state'=>'1','location'=>'timeout');//请求超时
            $res = $ease->sendText('系统消息'.$my_mobile,'users',$mobile,$content,$ext);
        }else{
            $type = '1';
            $content = '位置信息请求成功';
            $ext = array('type'=>$type,'mobile'=>$my_mobile,'user_nickname'=>$find_my_info['user_nickname'],'avatar'=>$find_my_info['avatar'],'lat'=>$lat,'lng'=>$lng,'state'=>'2','location'=>'receive');
            $res = $ease->sendText('系统消息'.$my_mobile,'users',$mobile,$content,$ext);

            return $this->json(0,'申请已发送',$res);
        }

    }

    /**
     *位置信息的开关接口
     * */
    public function position_switch(){
        $uid = I('uid');
        $mobile = I('mobile');//做验证
        if(!$uid || !$mobile){
            return $this->json(-1,'参数错误');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            return $this->json(-2,'该用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            return $this->json(-3,'用户信息不匹配');
        }

        $is_open = "";
        //查询开关信息
        $find_switch_info = M('switch_location_info')->where("uid = ".$uid)->find();
        if(!$find_switch_info){
            $data['uid'] = $uid;
            $data['mobile'] = $mobile;
            $res = M('switch_location_info')->add($data);
            if($res){
                return $this->json(0,'开关已开启',array('location_is_open' => '1'));
            }else{
                return $this->json(0,'开关开启失败');
            }
        }else{
            if($find_switch_info['is_open'] == 1){
                $res = M('switch_location_info')->where("uid = ".$uid)->setField('is_open',2);
                if($res){
                    return $this->json(0,'开关已关闭',array('location_is_open' => '0'));
                }else{
                    return $this->json(0,'开关关闭失败');
                }
            }else if($find_switch_info['is_open'] == 2){
                $res = M('switch_location_info')->where("uid = ".$uid)->setField('is_open',1);
                if($res){
                    return $this->json(0,'开关已开启',array('location_is_open' => '1'));
                }else{
                    return $this->json(0,'开关开启失败');
                }
            }
        }

    }

    /**
     * 判断位置信息是否关闭
     * */
    public function position_is_open(){
        $uid = I('uid');
        $mobile = I('mobile');//做验证
        if(!$uid || !$mobile){
            return $this->json(-1,'参数错误');
        }

        //查询用户信息
        $find_my_info = M('user')->where("id = ".$uid)->find();
        if(!$find_my_info){
            return $this->json(-2,'该用户不存在');
        }

        if($find_my_info['mobile'] != $mobile){
            return $this->json(-3,'用户信息不匹配');
        }

        $find_switch_info = M('switch_location_info')->where("uid = ".$uid)->find();
        if(!$find_switch_info){
            $location_is_open = "0";
        }else{
            if($find_switch_info['is_open'] == 1){
                $location_is_open = "1";
            }else if($find_switch_info['is_open'] == 2){
                $location_is_open = "0";
            }
        }


        return $this->json(0,'成功',array('location_is_open' => $location_is_open));

    }

}


?>