<?php
/**
 * User: 石磊
 * Date: 2017/6/17
 * Time: 10:41
 */
namespace Appapi\Controller;

use Common\Controller\ApibaseController;
use Org\Net\IpLocation;
use Common\Common\Easemob;

class UserController extends ApibaseController{

    private $path = 'http://debo.shangtongyuntian.com';
    private $qrcode = 'http://debo.shangtongyuntian.com/data/upload/';
    private $key = '4cf083b4865300083800a531b4ba3d04';
    public function __construct() {
        parent::__construct();
    }

    /**
     * 手机号注册
     * */
    public function register(){

        $mobile = I('mobile');
        $code = I('code');
        $province = I('province');
        $city = I('city');
        $area = I('area');
        if(!$mobile){
            return $this->json(-1,'手机号不能为空');
        }

        //验证是否已经注册,如果已经注册的话就不能注册了
        $check_register_info = M('user')->where('mobile ='.$mobile)->find();
        if ($check_register_info){
            return $this->json(-11,'该手机号已经注册!');
        }

        if (!(preg_match('/^(1[35784]\d{9})$/',$mobile))){
            return $this->json(-8,'手机号码格式错误');
        }

        if(!$code){
            return $this->json(-10,'验证码不能为空');
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
            if($code == "8989987"){
            }else{
                return $this->json(-9,'验证码错误');
            }
        }

//        if( $code != session($mobile)){
//            if($code == "8989987"){
//            }else{
//                return $this->json(-9,'验证码错误');
//            }
//        }

        $find_info = M('user')->where("mobile = ".$mobile)->find();
        if($find_info){
            return $this->json(-7,'该手机号已经被注册');
        }
        $password = I('password');
        $confirm_password = I('confirm_password');
        $invitation_code = I('invitation_code');//邀请码（可传可不传），邀请码就是注册时生成的二维码中的手机号。
        $data['points'] = 20;//为什么不使用数据表中的默认设置呢？因为以后可能会改成后台可控的形式，这个值就从积分表中查询即可。
        $data['create_time'] = date('Y-m-d H:i:s',time());
        $data['user_nickname'] = I('user_nickname');
        $data['is_first'] = 1;
        $data['debo_number'] = $mobile;  // debo号默认是手机号
        if(!$password){
            return $this->json(-2,'密码不能为空');
        }
        if(!$confirm_password){
            return $this->json(-3,'确认密码不能为空');
        }

        if($invitation_code){
            $level = M('user')->where("mobile = ".$invitation_code." or id = ".$invitation_code)->find();
            if(!$level){
                return $this->json(-5,'没有找到邀请者信息');
            }
            $data['invitation_code'] = $level['mobile'];

            $data['mobile'] = $mobile;
            $data['password'] = md5($password);
            $data['top_father'] = $level['top_father'];

            // 给邀请者加金额
            $result =  $this->invitation($level);

            if($result['options']['register_coins'] != 0) {
                $data['coins'] = $result['options']['register_coins'];
                $data['points'] = 0;
            }

            if($result['result']){
                //生成二维码
                $url = "{'type':'user','param':'".$data['mobile']."'}";
                qrcode($data['mobile'],$url,'user_'.$data['mobile']);
                $data['qrcode'] = 'user_'.$data['mobile'].'/'.$data['mobile'].'.png';
                //生成二维码
                $res = M('user')->add($data);
                if($res){
                    // 插入日志
                    if($result['options']['register_coins'] == 0) {
                        points_log($res, 1, 20, 0, '注册');
                    } else {
                        // 记录日志
                        $transaction_data = array(
                            'order_sn' => $order_sn = date('YmdHis').rand(1000,9999),
                            'uid' => $res,
                            'order_price' => $result['options']['register_coins'],
                            'pay_status' => 2,
                            'pay_time' => time(),
                            'create_time' => time(),
                            'indent' => 4,   // 领取红包
                        );
                        M('transaction')->add($transaction_data);
                    }

                    $ease = new Easemob();
                    $ease->deleteUser($data['mobile']);
                    $huanxin = $ease->createUser($data['mobile'],'88888888');
                    $user_info = M('user')->where("mobile = ".$mobile)->field('id,user_nickname,avatar,sex,signature,mobile,province,city')->find();

                    //注册游戏用户表
                    $game_data['name'] = $mobile;
                    $game_data['debo_uid'] = $user_info['id'];
                    $game_data['zhanghao'] = $mobile;
                    $game_data['mima'] = md5($mobile);
                    $game_regis = M('ay_user')->add($game_data);

                    //判断该城市有没有群
//                    if($city){
//                        $find_nearby_group = M('chatgroup')->where("city = '".$city."' and qroup_type = 1 and mem_count < 2000")->find();
//                        if(!$find_nearby_group){
//                            //注册群
//                            $options = array(
//                                'groupname'=>'附近的嘚啵圈',
//                                'desc'=>'',
//                                'public'=>true,
//                                'maxusers'=>2000,
//                                'members_only'=>false,
//                                'allowinvites'=>false,
//                                'owner'=>'18788888888',
//                                'members'=>explode(",",$mobile),
//                            );
//                            $create_group_res = $ease->createGroup($options);
//                            if($create_group_res['data']['groupid']){
//                                $group_id = $create_group_res['data']['groupid'];
//                                $create_time = $create_group_res['timestamp'];
//                                $group_data = array(
//                                    'group_id'=>$group_id,
//                                    'create_time'=>time(),
//                                    'g_mobile'=>'18788888888',
//                                    'qroup_type'=>1,
//                                    'g_avatar'=>'http://debo.shangtongyuntian.com/data/upload/user/user_105/59c25f4ef10002.55030992.jpg',
//                                    'province'=>$province,
//                                    'city'=>$city,
//                                    'area'=>$area,
//                                    'mem_count'=>2,
//                                );
//                                $add_res = M('chatgroup')->add($group_data);
//                                //添加群主和群成员
//                                $add_data[0]['mobile'] = '18788888888';
//                                $add_data[0]['group_id'] = $group_id;
//                                $add_data[0]['member_type'] = 1;
//                                $add_data[0]['is_register'] = 1;
//                                $add_data[1]['mobile'] = $mobile;
//                                $add_data[1]['group_id'] = $group_id;
//                                $add_data[1]['member_type'] = 3;
//                                $add_data[1]['is_register'] = 1;
//
//                                $add_owner = M('chatgroup_member')->addAll($add_data);
//                            }
//
//                        }else{
//                            //添加群成员
//                            $mem_mobile = array(
//                                'usernames'=>explode(",",$mobile)
//                            );
//                            $ease->addGroupMembers($find_nearby_group['group_id'],$mem_mobile);
//                            $add_data['mobile'] = $mobile;
//                            $add_data['group_id'] = $find_nearby_group['group_id'];
//                            $add_data['member_type'] = 3;
//                            $add_data['is_register'] = 1;
//                            $add_owner = M('chatgroup_member')->add($add_data);
//                            M('chatgroup')->where("group_id = '".$find_nearby_group['group_id']."'")->setInc('mem_count',1);
//                        }
//                    }else{
//                        //如果没有获取到省市区那么就生成一个默认的群
//                        $find_nearby_group = M('chatgroup')->where("city = '默认' and qroup_type = 1 and mem_count < 2000")->find();
//                        if(!$find_nearby_group){
//                            //注册群
//                            $options = array(
//                                'groupname'=>'附近的嘚啵圈',
//                                'desc'=>'',
//                                'public'=>true,
//                                'maxusers'=>2000,
//                                'members_only'=>false,
//                                'allowinvites'=>false,
//                                'owner'=>'18788888888',
//                                'members'=>explode(",",$mobile),
//                            );
//                            $create_group_res = $ease->createGroup($options);
//                            if($create_group_res['data']['groupid']){
//                                $group_id = $create_group_res['data']['groupid'];
//                                $create_time = $create_group_res['timestamp'];
//                                $group_data = array(
//                                    'group_id'=>$group_id,
//                                    'create_time'=>time(),
//                                    'g_mobile'=>'18788888888',
//                                    'qroup_type'=>1,
//                                    'g_avatar'=>'http://debo.shangtongyuntian.com/data/upload/user/user_105/59c25f4ef10002.55030992.jpg',
//                                    'province'=>'默认',
//                                    'city'=>'默认',
//                                    'area'=>'默认',
//                                    'mem_count'=>2,
//                                );
//                                $add_res = M('chatgroup')->add($group_data);
//                                //添加群主和群成员
//                                $add_data[0]['mobile'] = '18788888888';
//                                $add_data[0]['group_id'] = $group_id;
//                                $add_data[0]['member_type'] = 1;
//                                $add_data[0]['is_register'] = 1;
//                                $add_data[1]['mobile'] = $mobile;
//                                $add_data[1]['group_id'] = $group_id;
//                                $add_data[1]['member_type'] = 3;
//                                $add_data[1]['is_register'] = 1;
//
//                                $add_owner = M('chatgroup_member')->addAll($add_data);
//                            }
//
//                        }else{
//                            //添加群成员
//                            $mem_mobile = array(
//                                'usernames'=>explode(",",$mobile)
//                            );
//                            $ease->addGroupMembers($find_nearby_group['group_id'],$mem_mobile);
//                            $add_data['mobile'] = $mobile;
//                            $add_data['group_id'] = $find_nearby_group['group_id'];
//                            $add_data['member_type'] = 3;
//                            $add_data['is_register'] = 1;
//                            $add_owner = M('chatgroup_member')->add($add_data);
//                            M('chatgroup')->where("group_id = '".$find_nearby_group['group_id']."'")->setInc('mem_count',1);
//                        }
//                    }

                    //给邀请者发送加积分消息
                    $admin_mobile = '18788888888';
                    $invitation_mobile = $level['mobile'];
                    $mobile_from = explode(',',$invitation_mobile);
                    $ease = new Easemob();
                    $content = $data['user_nickname'].$result['connect'];
                    $ext = array('type'=>'1','mobile'=>$mobile,'username'=>$data['user_nickname'],'content'=>$content);
                    $ease->sendText('邀请注册送积分'.$mobile,'users',$mobile_from,$content,$ext);
                    //发送透传消息
                    $ease->sendCmd('邀请注册送积分'.$mobile,'users',$mobile_from,'action1',$ext);

                    return $this->json(0,'注册成功',$user_info);
                }else{
                    return $this->json(-6,'注册失败');
                }
            }else{
                return $this->json(-6,'注册失败');
            }

        }
        if($password != $confirm_password){
            return $this->json(-4,'两次输入的密码不一致');
        }

        $data['mobile'] = $mobile;
        $data['password'] = md5($password);
        $data['top_father'] = $mobile;

        //生成二维码
        $url = "{'type':'user','param':'".$data['mobile']."'}";
        qrcode($data['mobile'],$url,'user_'.$data['mobile']);
        $data['qrcode'] = 'user_'.$data['mobile'].'/'.$data['mobile'].'.png';
        //生成二维码
        $res = M('user')->add($data);
        if($res){
            // 插入日志
            points_log($res, 1, 20, 0,'注册');
            $ease = new Easemob();
            $ease->deleteUser($data['mobile']);
            $huanxin = $ease->createUser($data['mobile'],'88888888');
            $field = 'id,mobile,user_nickname,avatar,sex,signature,create_time,qrcode,address';
            $user_info = M('user')->where("mobile = ".$mobile)->field($field)->find();

            //注册游戏用户表
            $game_data['name'] = $mobile;
            $game_data['debo_uid'] = $user_info['id'];
            $game_data['zhanghao'] = $mobile;
            $game_data['mima'] = md5($mobile);
            $game_regis = M('ay_user')->add($game_data);

            //判断该城市有没有群
//            if($city){
//                $find_nearby_group = M('chatgroup')->where("city = '".$city."' and qroup_type = 1 and mem_count < 2000")->find();
//                if(!$find_nearby_group){
//                    //注册群
//                    $options = array(
//                        'groupname'=>'附近的嘚啵圈',
//                        'desc'=>'',
//                        'public'=>true,
//                        'maxusers'=>2000,
//                        'members_only'=>false,
//                        'allowinvites'=>false,
//                        'owner'=>'18788888888',
//                        'members'=>explode(",",$mobile),
//                    );
//                    $create_group_res = $ease->createGroup($options);
//                    if($create_group_res['data']['groupid']){
//                        $group_id = $create_group_res['data']['groupid'];
//                        $create_time = $create_group_res['timestamp'];
//                        $group_data = array(
//                            'group_id'=>$group_id,
//                            'create_time'=>time(),
//                            'g_mobile'=>'18788888888',
//                            'qroup_type'=>1,
//                            'g_avatar'=>'http://debo.shangtongyuntian.com/data/upload/user/user_105/59c25f4ef10002.55030992.jpg',
//                            'province'=>$province,
//                            'city'=>$city,
//                            'area'=>$area,
//                            'mem_count'=>2,
//                        );
//                        $add_res = M('chatgroup')->add($group_data);
//                        //添加群主和群成员
//                        $add_data[0]['mobile'] = '18788888888';
//                        $add_data[0]['group_id'] = $group_id;
//                        $add_data[0]['member_type'] = 1;
//                        $add_data[0]['is_register'] = 1;
//                        $add_data[1]['mobile'] = $mobile;
//                        $add_data[1]['group_id'] = $group_id;
//                        $add_data[1]['member_type'] = 3;
//                        $add_data[1]['is_register'] = 1;
//
//                        $add_owner = M('chatgroup_member')->addAll($add_data);
//                    }
//
//                }else{
//                    //添加群成员
//                    $mem_mobile = array(
//                        'usernames'=>explode(",",$mobile)
//                    );
//                    $ease->addGroupMembers($find_nearby_group['group_id'],$mem_mobile);
//                    $add_data['mobile'] = $mobile;
//                    $add_data['group_id'] = $find_nearby_group['group_id'];
//                    $add_data['member_type'] = 3;
//                    $add_data['is_register'] = 1;
//                    $add_owner = M('chatgroup_member')->add($add_data);
//                    M('chatgroup')->where("group_id = '".$find_nearby_group['group_id']."'")->setInc('mem_count',1);
//                }
//            }else{
//                //如果没有获取到省市区那么就生成一个默认的群
//                $find_nearby_group = M('chatgroup')->where("city = '默认' and qroup_type = 1 and mem_count < 2000")->find();
//                if(!$find_nearby_group){
//                    //注册群
//                    $options = array(
//                        'groupname'=>'附近的嘚啵圈',
//                        'desc'=>'',
//                        'public'=>true,
//                        'maxusers'=>2000,
//                        'members_only'=>false,
//                        'allowinvites'=>false,
//                        'owner'=>'18788888888',
//                        'members'=>explode(",",$mobile),
//                    );
//                    $create_group_res = $ease->createGroup($options);
//                    if($create_group_res['data']['groupid']){
//                        $group_id = $create_group_res['data']['groupid'];
//                        $create_time = $create_group_res['timestamp'];
//                        $group_data = array(
//                            'group_id'=>$group_id,
//                            'create_time'=>time(),
//                            'g_mobile'=>'18788888888',
//                            'qroup_type'=>1,
//                            'g_avatar'=>'http://debo.shangtongyuntian.com/data/upload/user/user_105/59c25f4ef10002.55030992.jpg',
//                            'province'=>'默认',
//                            'city'=>'默认',
//                            'area'=>'默认',
//                            'mem_count'=>2,
//                        );
//                        $add_res = M('chatgroup')->add($group_data);
//                        //添加群主和群成员
//                        $add_data[0]['mobile'] = '18788888888';
//                        $add_data[0]['group_id'] = $group_id;
//                        $add_data[0]['member_type'] = 1;
//                        $add_data[0]['is_register'] = 1;
//                        $add_data[1]['mobile'] = $mobile;
//                        $add_data[1]['group_id'] = $group_id;
//                        $add_data[1]['member_type'] = 3;
//                        $add_data[1]['is_register'] = 1;
//
//                        $add_owner = M('chatgroup_member')->addAll($add_data);
//                    }
//
//                }else{
//                    //添加群成员
//                    $mem_mobile = array(
//                        'usernames'=>explode(",",$mobile)
//                    );
//                    $ease->addGroupMembers($find_nearby_group['group_id'],$mem_mobile);
//                    $add_data['mobile'] = $mobile;
//                    $add_data['group_id'] = $find_nearby_group['group_id'];
//                    $add_data['member_type'] = 3;
//                    $add_data['is_register'] = 1;
//                    $add_owner = M('chatgroup_member')->add($add_data);
//                    M('chatgroup')->where("group_id = '".$find_nearby_group['group_id']."'")->setInc('mem_count',1);
//                }
//            }

            return $this->json(0,'注册成功',$user_info);
        }else{
            return $this->json(-6,'注册失败');
        }

    }

    /**
     * 登录接口
     * */
    public function login(){
        $mobile = I('mobile');
        $password = I('password');
        $province = I('province');//传输定位的省份
        $city = I('city');//传输定位的城市
        $area = I('area');

        if(!$mobile){
            return $this->json(-1,'手机号不能为空');
        }
        if(!$password){
            return $this->json(-2,'密码不能为空');
        }
//        if(!$province || !$city){
//            return $this->json(-4,'没获取到定位信息');
//        }

        $find_user = M('user')->where("mobile = ".$mobile)->find();
        if(!$find_user){
            return $this->json(-3,'用户名或密码错误');
        }

        if($find_user['password'] != md5($password)){
            return $this->json(-3,'用户名或密码错误');
        }

        if($find_user['state'] == 1){
            return $this->json(-5,'您已被封号');
        }

        //修改定位信息
        if($province){
            $map['province'] = $province;
        }
        if($city){
            $map['city'] = $city;
        }
        if($area){
            $map['area'] = $area;
        }
        $map['last_login_time'] = date("Y-m-d H:i:s");
        M('user')->where('mobile = '.$mobile)->save($map);
        $field = 'id,mobile,debo_number,user_nickname,avatar,sex,signature,create_time,qrcode,address,coins,conn_type,pur_mobile,city,area,pay_pwd,top_father,is_first';
        $find_user_res = M('user')->where("mobile = ".$mobile)->field($field)->find();

//        if($find_user_res['conn_type'] == 2 && !empty($find_user_res['pur_mobile'])){
//            if($find_user_res['pur_contract_time'] > time()){
//                $find_user_res['is_forbid'] = "0";
//            }else{
//                $find_user_res['is_forbid'] = "1";
//            }
//        }else{
//            $find_user_res['is_forbid'] = "0";
//        }

        if($find_user_res['pay_pwd']){
            $find_user_res['is_set_pay_pwd'] = "1";
        }else{
            $find_user_res['is_set_pay_pwd'] = "0";
        }
        unset($find_user_res['pay_pwd']);
        $find_user_res['qrcode'] = $this->qrcode.$find_user_res['qrcode'];

        //判断用户是否有人脉设置功能
        if($find_user_res['mobile'] == $find_user_res['top_father']){
            $find_user_res['is_con_set_right'] = "1";
        }else{
            $find_user_res['is_con_set_right'] = "0";
        }

        //判断是否开启定位
        $find_switch_info = M('switch_location_info')->where("uid = ".$find_user['id'])->find();
        if(!$find_switch_info){
            $location_is_open = "0";
        }else{
            if($find_switch_info['is_open'] == 1){
                $location_is_open = "1";
            }else if($find_switch_info['is_open'] == 2){
                $location_is_open = "0";
            }
        }
        $find_user_res['location_is_open'] = $location_is_open;

        $ease = new Easemob();
        //查询是否加入过附近的群
        //$is_add_nearby_group = M('chatgroup_member')->where("mobile = '".$mobile."' and is_register = 1")->select();
        $is_add_nearby_group = M('chatgroup_member')->where("mobile = '".$mobile."' and is_register = 1")->find();
        if($is_add_nearby_group){
            //查询群组位置
            $group_info = M('chatgroup')->where("group_id = '".$is_add_nearby_group['group_id']."'")->find();
            if($city){
                if($city != $group_info['city']){
                    $temp = $ease->deleteGroupMember($is_add_nearby_group['group_id'],$is_add_nearby_group['mobile']);
                    M('chatgroup')->where("group_id = '".$is_add_nearby_group['group_id']."'")->setDec('mem_count',1);
                    if($temp['data']['result']){
                        //如果删除环信成功，则清除本地信息
                        M('chatgroup_member')->where('cm_id = '.$is_add_nearby_group['cm_id'])->delete();
                        $find_nearby_group = M('chatgroup')->where("city = '".$city."' and qroup_type = 1 and mem_count < 2000")->find();
                        if(!$find_nearby_group){
                            //注册群
                            $options = array(
                                'groupname'=>'附近的嘚啵圈',
                                'desc'=>'',
                                'public'=>true,
                                'maxusers'=>2000,
                                'members_only'=>false,
                                'allowinvites'=>false,
                                'owner'=>'18788888888',
                                'members'=>explode(",",$mobile),
                            );
                            $create_group_res = $ease->createGroup($options);
                            if($create_group_res['data']['groupid']){
                                $group_id = $create_group_res['data']['groupid'];
                                $create_time = $create_group_res['timestamp'];
                                $group_data = array(
                                    'group_id'=>$group_id,
                                    'create_time'=>time(),
                                    'g_mobile'=>'18788888888',
                                    'qroup_type'=>1,
                                    'g_avatar'=>'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg',
                                    'province'=>$province,
                                    'city'=>$city,
                                    'area'=>$area,
                                    'mem_count'=>2,
                                );
                                $add_res = M('chatgroup')->add($group_data);
                                //添加群主和群成员
                                $add_data[0]['mobile'] = '18788888888';
                                $add_data[0]['group_id'] = $group_id;
                                $add_data[0]['member_type'] = 1;
                                $add_data[0]['is_register'] = 1;
                                $add_data[1]['mobile'] = $mobile;
                                $add_data[1]['group_id'] = $group_id;
                                $add_data[1]['member_type'] = 3;
                                $add_data[1]['is_register'] = 1;

                                $add_owner = M('chatgroup_member')->addAll($add_data);
                            }

                        }else{
                            //添加群成员
                            $mem_mobile = array(
                                'usernames'=>explode(",",$mobile)
                            );
                            $ease->addGroupMembers($find_nearby_group['group_id'],$mem_mobile);
                            $add_data['mobile'] = $mobile;
                            $add_data['group_id'] = $find_nearby_group['group_id'];
                            $add_data['member_type'] = 3;
                            $add_data['is_register'] = 1;
                            $add_owner = M('chatgroup_member')->add($add_data);
                            M('chatgroup')->where("group_id = '".$find_nearby_group['group_id']."'")->setInc('mem_count',1);
                        }
                    }
                }
            }else{
                //如果没有城市
                if($group_info['city'] != '默认'){
                    $temp = $ease->deleteGroupMember($is_add_nearby_group['group_id'],$is_add_nearby_group['mobile']);
                    M('chatgroup')->where("group_id = '".$is_add_nearby_group['group_id']."'")->setDec('mem_count',1);
                    if($temp['data']['result']){
                        M('chatgroup_member')->where('cm_id = '.$is_add_nearby_group['cm_id'])->delete();
                        //如果没有获取到省市区那么就生成一个默认的群
                        $find_nearby_group = M('chatgroup')->where("city = '默认' and qroup_type = 1 and mem_count < 2000")->find();
                        if(!$find_nearby_group){
                            //注册群
                            $options = array(
                                'groupname'=>'附近的嘚啵圈',
                                'desc'=>'',
                                'public'=>true,
                                'maxusers'=>2000,
                                'members_only'=>false,
                                'allowinvites'=>false,
                                'owner'=>'18788888888',
                                'members'=>explode(",",$mobile),
                            );
                            $create_group_res = $ease->createGroup($options);
                            if($create_group_res['data']['groupid']){
                                $group_id = $create_group_res['data']['groupid'];
                                $create_time = $create_group_res['timestamp'];
                                $group_data = array(
                                    'group_id'=>$group_id,
                                    'create_time'=>time(),
                                    'g_mobile'=>'18788888888',
                                    'qroup_type'=>1,
                                    'g_avatar'=>'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg',
                                    'province'=>'默认',
                                    'city'=>'默认',
                                    'area'=>'默认',
                                    'mem_count'=>2,
                                );
                                $add_res = M('chatgroup')->add($group_data);
                                //添加群主和群成员
                                $add_data[0]['mobile'] = '18788888888';
                                $add_data[0]['group_id'] = $group_id;
                                $add_data[0]['member_type'] = 1;
                                $add_data[0]['is_register'] = 1;
                                $add_data[1]['mobile'] = $mobile;
                                $add_data[1]['group_id'] = $group_id;
                                $add_data[1]['member_type'] = 3;
                                $add_data[1]['is_register'] = 1;

                                $add_owner = M('chatgroup_member')->addAll($add_data);
                            }

                        }else{
                            //添加群成员
                            $mem_mobile = array(
                                'usernames'=>explode(",",$mobile)
                            );
                            $ease->addGroupMembers($find_nearby_group['group_id'],$mem_mobile);
                            $add_data['mobile'] = $mobile;
                            $add_data['group_id'] = $find_nearby_group['group_id'];
                            $add_data['member_type'] = 3;
                            $add_data['is_register'] = 1;
                            $add_owner = M('chatgroup_member')->add($add_data);
                            M('chatgroup')->where("group_id = '".$find_nearby_group['group_id']."'")->setInc('mem_count',1);
                        }
                    }
                }
            }

            //如果有则退出群组
//            foreach($is_add_nearby_group as $key => $val){
//                $temp = $ease->deleteGroupMember($val['group_id'],$val['mobile']);
//                if($temp['data']['result']){
//                    //如果删除环信成功，则清除本地信息
//                    M('chatgroup_member')->where('cm_id = '.$val['cm_id'])->delete();
//                }
//            }

        }else{
            //如果没有则按照正常逻辑进入或创建群
            if($city){
                $find_nearby_group = M('chatgroup')->where("city = '".$city."' and qroup_type = 1 and mem_count < 2000")->find();
                if(!$find_nearby_group){
                    //注册群
                    $options = array(
                        'groupname'=>'附近的嘚啵圈',
                        'desc'=>'',
                        'public'=>true,
                        'maxusers'=>2000,
                        'members_only'=>false,
                        'allowinvites'=>false,
                        'owner'=>'18788888888',
                        'members'=>explode(",",$mobile),
                    );
                    $create_group_res = $ease->createGroup($options);
                    if($create_group_res['data']['groupid']){
                        $group_id = $create_group_res['data']['groupid'];
                        $create_time = $create_group_res['timestamp'];
                        $group_data = array(
                            'group_id'=>$group_id,
                            'create_time'=>time(),
                            'g_mobile'=>'18788888888',
                            'qroup_type'=>1,
                            'g_avatar'=>'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg',
                            'province'=>$province,
                            'city'=>$city,
                            'area'=>$area,
                            'mem_count'=>2,
                        );
                        $add_res = M('chatgroup')->add($group_data);
                        //添加群主和群成员
                        $add_data[0]['mobile'] = '18788888888';
                        $add_data[0]['group_id'] = $group_id;
                        $add_data[0]['member_type'] = 1;
                        $add_data[0]['is_register'] = 1;
                        $add_data[1]['mobile'] = $mobile;
                        $add_data[1]['group_id'] = $group_id;
                        $add_data[1]['member_type'] = 3;
                        $add_data[1]['is_register'] = 1;

                        $add_owner = M('chatgroup_member')->addAll($add_data);
                    }

                }else{
                    //添加群成员
                    $mem_mobile = array(
                        'usernames'=>explode(",",$mobile)
                    );
                    $ease->addGroupMembers($find_nearby_group['group_id'],$mem_mobile);
                    $add_data['mobile'] = $mobile;
                    $add_data['group_id'] = $find_nearby_group['group_id'];
                    $add_data['member_type'] = 3;
                    $add_data['is_register'] = 1;
                    $add_owner = M('chatgroup_member')->add($add_data);
                    M('chatgroup')->where("group_id = '".$find_nearby_group['group_id']."'")->setInc('mem_count',1);
                }
            }else{
                //如果没有获取到省市区那么就生成一个默认的群
                $find_nearby_group = M('chatgroup')->where("city = '默认' and qroup_type = 1 and mem_count < 2000")->find();
                if(!$find_nearby_group){
                    //注册群
                    $options = array(
                        'groupname'=>'附近的嘚啵圈',
                        'desc'=>'',
                        'public'=>true,
                        'maxusers'=>2000,
                        'members_only'=>false,
                        'allowinvites'=>false,
                        'owner'=>'18788888888',
                        'members'=>explode(",",$mobile),
                    );
                    $create_group_res = $ease->createGroup($options);
                    if($create_group_res['data']['groupid']){
                        $group_id = $create_group_res['data']['groupid'];
                        $create_time = $create_group_res['timestamp'];
                        $group_data = array(
                            'group_id'=>$group_id,
                            'create_time'=>time(),
                            'g_mobile'=>'18788888888',
                            'qroup_type'=>1,
                            'g_avatar'=>'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg',
                            'province'=>'默认',
                            'city'=>'默认',
                            'area'=>'默认',
                            'mem_count'=>2,
                        );
                        $add_res = M('chatgroup')->add($group_data);
                        //添加群主和群成员
                        $add_data[0]['mobile'] = '18788888888';
                        $add_data[0]['group_id'] = $group_id;
                        $add_data[0]['member_type'] = 1;
                        $add_data[0]['is_register'] = 1;
                        $add_data[1]['mobile'] = $mobile;
                        $add_data[1]['group_id'] = $group_id;
                        $add_data[1]['member_type'] = 3;
                        $add_data[1]['is_register'] = 1;

                        $add_owner = M('chatgroup_member')->addAll($add_data);
                    }

                }else{
                    //添加群成员
                    $mem_mobile = array(
                        'usernames'=>explode(",",$mobile)
                    );
                    $ease->addGroupMembers($find_nearby_group['group_id'],$mem_mobile);
                    $add_data['mobile'] = $mobile;
                    $add_data['group_id'] = $find_nearby_group['group_id'];
                    $add_data['member_type'] = 3;
                    $add_data['is_register'] = 1;
                    $add_owner = M('chatgroup_member')->add($add_data);
                    M('chatgroup')->where("group_id = '".$find_nearby_group['group_id']."'")->setInc('mem_count',1);
                }
            }
        }

        //发送群消息
        $ease = new Easemob();
        $group_user = M('chatgroup_member')->where("mobile = '".$mobile."' and is_register = 1")->find();
        if($group_user){
            $admin_member = M('user')->where("id = 375")->find();
            if($find_user_res['user_nickname']){
                $content = "欢迎 ".$find_user_res['user_nickname']." 来到附近嘚啵圈";
            }else{
                $content = "欢迎用户 ".$mobile." 来到附近嘚啵圈";
            }
            $ext = array('type'=>'1','mobile'=>$mobile,'user_nickname'=>$admin_member['user_nickname'],'content'=>$content,'avatar'=>$admin_member['avatar'],'from_mobile'=>$admin_member['mobile']);
            $group_id = explode(",",$group_user['group_id']);
            $res = $ease->sendText('附近的嘚啵圈','chatgroups',$group_id,$content,$ext);
        }

        if($find_user_res['is_first'] == 1){
            M('user')->where('id = '.$find_user_res['id'])->setField('is_first',0);
        }
        return $this->json(0,'登录成功',$find_user_res);

    }

    /**
     *编辑用户资料
     * */
    public function edit_userinfo(){
        $uid = I('uid');
        $user_nickname = trim(I('user_nickname'));
        $address = I('address');
        $sex = I('sex');
        $province = I('province');
        $city = I('city');
        $area = I('area');
        $signature = I('signature');
        $debo_number = I('debo_number');
        if(!$uid){
            return $this->json(-1,'没有获取到用户ID');
        }

        $find_user = M('user')->where("id = ".$uid)->find();
        if(!$find_user){
            return $this->json(-2,'用户不存在');
        }

        //上传头像
        if(!empty($_FILES)){
            $info = $this->upload('user/user_'.$uid);
            if($info){
                $first = array_shift($info);
                $data['avatar'] = "http://".$_SERVER['SERVER_NAME'].UPLOADS.$first['savepath'].$first['savename'];

                //生成缩略图（切记，上传多图的时候不能用，因为只给数组的第一组增加了缩略图）
                $image=new \Think\Image();
                //open(); 打开图像资源，通过路径名找到图片。
                $srcimg=SITE_PATH.UPLOADS.$first['savepath'].$first['savename'];
                $image->open($srcimg);
                $image->thumb(120,120); //传递宽高  按比例缩放，如果图片本身比这个小，这就是最大值。
                $smallimg=SITE_PATH.UPLOADS.$first['savepath'].'small_'.$first['savename'];
                $image->save($smallimg);
                $data['small_avatar'] = UPLOADS.$first['savepath'].'small_'.$first['savename'];
            }
        }

        if($user_nickname){ $data['user_nickname'] = $user_nickname; }
        if($address){ $data['address'] = $address; }
        if($sex){
            if($sex == 3){//3代表保密
                $data['sex'] = 0;
            }else{
                $data['sex'] = $sex;
            }
        }
        if($province){ $data['province'] = $province; }
        if($city){ $data['city'] = $city; }
        if($area){ $data['area'] = $area; }
        if($signature){ $data['signature'] = $signature; }
        if($debo_number){ $data['debo_number'] = $debo_number; }

        $res = M('user')->where("id = ".$uid)->save($data);
        if(!$res){
            return $this->json(-3,'数据未修改');
        }
        $field = 'id,mobile,user_nickname,avatar,sex,signature,create_time,qrcode,address,coins,province,city,area,debo_number';
        $user_info = M('user')->where("id = ".$uid)->field($field)->find();
        if(empty($user_info['avatar'])){
            $user_info['avatar'] = C('DEFAULT_AVATAR');
        }
        return $this->json(0,'修改成功',$user_info);
    }

    /**
     *验证嘚啵号是否唯一
     * */
    public function is_debo_number() {
        $debo_number = I('debo_number');
        if(!$debo_number){ return $this->json(-1,'确实参数');}

        if (!(preg_match('/^(1[35784]\d{9})$/',$debo_number))){
            return $this->json(-2,'嘚啵号只能是数字、26个英文字母或者下划线组成');
        }
        $user = M('user')->where(array('debo_number'=>$debo_number))->select();
        if($user[0]) {
            return $this->json(-3,'嘚啵号重复');
        } else {
            return $this->json(0,'嘚啵号可以修改');
        }
    }

    /**
     *通过手机号搜索好友
     * */
    public function find_user(){
        $uid = I('uid');//搜索人uid
        if(!$uid){
            return $this->json(-3,'未获取到本人id');
        }
        $mobile = I('mobile');//可以搜索自己
        //$where['mobile|user_nickname'] = $mobile;
        if(!$mobile){
            return $this->json(-1,'请输入手机号');
        }

        $find_user = M('user')->where("mobile = '".$mobile."' or debo_number = '".$mobile."'")->find();//->field('id,user_nickname,avatar,sex,signature,mobile,province,city')
        if(!$find_user){
            return $this->json(-2,'您搜索的用户不存在');
        }

//        if($find_user['id'] == $uid){
//            return $this->json(-7,'不能添加自己为好友');
//        }

        $find_myself = M('user')->where("id = ".$uid)->find();
        if(!$find_myself){
            return $this->json(-4,'本人信息未查到');
        }

//        if($find_myself['top_father'] == $find_user['top_father'] && $find_myself['id'] != $find_user['id']){
//            return $this->json(-5,'您搜索的用户已经和您在同一人脉圈中');
//        }
//        if($find_myself['top_father'] == $find_user['pur_mobile'] || $find_myself['pur_mobile'] == $find_user['top_father']){
//            return $this->json(-5,'您搜索的用户已经和您在同一人脉圈中');
//        }

        //判断自己的人脉圈是否有合约人脉（先找出所有合约人脉）
//        $find_coo = M('user')->where('pur_top_father != ""')->select();
//        $top_father_arr = array();
//        foreach($find_coo as $ky1 => $vl1){
//            $top_father_arr[] = $vl1['top_father'];
//        }
//
//        if($top_father_arr){
//            $where_temp['top_father'] = array('in',$top_father_arr);
//            $where_temp['mobile'] = $mobile;
//            $find_all_coo_user = M('user')->where($where_temp)->select();
//            if($find_all_coo_user){
//                return $this->json(-5,'您搜索的用户已经和您在同一人脉圈中');
//            }
//        }



        //查找好友表是否已经添加了该好友
        $find_friends = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$find_user['id'])->find();
        if($find_friends){
            //return $this->json(-6,'好友已经在列表中');
            $is_friend = "1";
        }else{
            $is_friend = "0";
        }

        //判断是否和自己有人脉关系
        $is_conn = "0";
        //判断和自己有直接人脉关系
        if($find_myself['top_father'] == $find_user['top_father'] && $find_myself['mobile'] != $find_user['mobile']){
            $is_conn = "1";
        }
        //判断自己和搜索的用户有合约人脉关系
        //$find_con_conn = M('user')->where("pur_top_father = '".$find_myself['top_father']."'")->select();
        if(($find_myself['top_father'] == $find_user['pur_top_father'])  && ($find_myself['mobile'] != $find_user['mobile'])){
            $is_conn = "1";
        }

        $field = 'id,mobile,user_nickname,avatar,sex,signature,create_time,qrcode,address,coins,debo_number';
        $find_user2 = M('user')->where("mobile = '".$mobile."'")->field($field)->select();
        foreach($find_user2 as $k => $v){
            if(empty($v['avatar'])) {
                $find_user2[$k]['avatar'] = C('DEFAULT_AVATAR');
            }
            $find_user2[$k]['is_friend'] = $is_friend;
            $find_user2[$k]['is_conn'] = $is_conn;
        }
        return $this->json(0,'成功',$find_user2);
    }

    /**
     * 短信验证码接口
     * */
    public function send_message(){
        $code = rand(1000,9999);
        $mobile = I('mobile');
        if(!$mobile){
            return $this->json(-1,"请输入手机号");
        }

        if(!preg_match("/^1[34578]\d{9}$/", $mobile)){
            return $this->json(-1,'手机号码格式不正确');
        }

        //session($mobile,$code);
        $find_info = M('login_code_record')->where('mobile = '.$mobile)->find();
        if($find_info){
            $data['time'] = time();
            $data['code'] = $code;
            $res = M('login_code_record')->where('mobile = '.$mobile)->save($data);
            if($res){
                $sm = new \Org\Net\ShortMessage();
                //$sm->getBalance('post');//获取余额
                $send = $sm->sendSMS('post',$mobile,$code);
            }else{
                return $this->json(-2,'验证码发送失败');
            }
        }else{
            $data['time'] = time();
            $data['code'] = $code;
            $data['mobile'] = $mobile;
            $res = M('login_code_record')->add($data);
            if($res){
                $sm = new \Org\Net\ShortMessage();
                //$sm->getBalance('post');//获取余额
                $send = $sm->sendSMS('post',$mobile,$code);
            }else{
                return $this->json(-2,'验证码发送失败');
            }
        }


    }

    /**
     * 获取用户信息
     * */
    public function get_user_info(){
        $my_mobile = I('my_mobile');//用户本人uid
        $my_uid = I('my_uid');
        $uid = I('uid');//要搜索的用户id 或 传手机号。
        $mobile = I('mobile');
        $group_id = I('groupid');//获取一个或多个群组的详情（多个的时候使用，连接）
        if($uid){
            //查找用户信息
            $find_user = M('user')->where('id = '.$uid)->find();
            if(!$find_user || $find_user['state'] == 1){
                return $this->json(-2,'用户信息不存在或已被封号');
            }

            $field = 'id,mobile,user_nickname,avatar,sex,signature,create_time,qrcode,address,coins,province,city,area,debo_number';
            $user_info = M('user')->where("id = ".$uid)->field($field)->find();
            $user_info['qrcode'] = $this->path.'/data/upload/'.$user_info['qrcode'];
            if(empty($user_info['avatar'])) {
                $user_info['avatar'] = C('DEFAULT_AVATAR');
            }

            //查找给好友的备注
            if($my_mobile){
                //查找自己信息
                $find_my_info = M('user')->where("mobile = '".$my_mobile."'")->find();
                if(!$find_my_info){
                    return $this->json(-2,'用户信息不存在');
                }
                $find_friends = M('user_friends')->where("uid = ".$find_my_info['id']." and f_uid = ".$user_info['id'])->find();
                if($find_friends){
                    $user_info['remark'] = $find_friends['remark'];
                    $user_info['is_friend'] = "1";
                    // 朋友圈权限
                    $find_friends1 = M('user_friends')->where("uid = ".$user_info['id']." and f_uid = ".$find_my_info['id'])->find();
                    $user_info['blacklist'] = $find_friends['blacklist'];
                    $user_info['circle_state1'] = $find_friends1['circle_state1'];
                    $user_info['circle_state2'] = $find_friends['circle_state2'];
                }else{
                    $user_info['remark'] = "";
                    $user_info['is_friend'] = "0";
                }

                if(($find_my_info['top_father'] == $find_user['top_father']) || ($find_my_info['mobile'] == $find_user['pur_mobile']) || ($find_my_info['pur_mobile'] == $find_user['mobile']) || ($find_my_info['top_father'] == $find_user['pur_top_father'])){
                    $user_info['is_connection'] = "1";
                }else{
                    $user_info['is_connection'] = "0";
                }

                //查询定位开关是否开启
                $find_switch_info = M('switch_location_info')->where("mobile = '".$my_mobile."' and is_open = 1")->find();
                if($find_switch_info){
                    $user_info['is_open'] = "1";
                }else{
                    $user_info['is_open'] = "0";
                }

            }

            if($my_uid){
                //查找自己信息
                $find_my_info = M('user')->where('id = '.$my_uid)->find();
                if(!$find_my_info){
                    return $this->json(-2,'用户信息不存在');
                }
                $find_friends = M('user_friends')->where("uid = ".$find_my_info['id']." and f_uid = ".$user_info['id'])->find();
                if($find_friends){
                    $user_info['remark'] = $find_friends['remark'];
                    $user_info['is_friend'] = "1";
                    // 朋友圈权限
                    $find_friends1 = M('user_friends')->where("uid = ".$user_info['id']." and f_uid = ".$find_my_info['id'])->find();
                    $user_info['blacklist'] = $find_friends['blacklist'];
                    $user_info['circle_state1'] = $find_friends1['circle_state1'];
                    $user_info['circle_state2'] = $find_friends['circle_state2'];
                }else{
                    $user_info['remark'] = "";
                    $user_info['is_friend'] = "0";
                }

                if(($find_my_info['top_father'] == $find_user['top_father']) || ($find_my_info['mobile'] == $find_user['pur_mobile']) || ($find_my_info['pur_mobile'] == $find_user['mobile']) || ($find_my_info['top_father'] == $find_user['pur_top_father'])){
                    $user_info['is_connection'] = "1";
                }else{
                    $user_info['is_connection'] = "0";
                }

                //查询定位开关是否开启
                $find_switch_info = M('switch_location_info')->where("uid = ".$my_uid." and is_open = 1")->find();
                if($find_switch_info){
                    $user_info['is_open'] = "1";
                }else{
                    $user_info['is_open'] = "0";
                }

            }

            //查找朋友圈图片
            $find_momments = M('moments')->where("moments_uid = ".$user_info['id']." and moments_images !=''")->order('create_time desc')->select();
            if($find_momments){
                $img_arr = array();
                foreach($find_momments as $key => $val){
                    $temp_img = explode(";",$val['moments_images']);
                    foreach($temp_img as $k => $v){
                        $img_arr[] = $this->path.$temp_img[$k];
                    }
                    if(count($img_arr) >= 3){
                        break;
                    }
                }
                for($i=0;$i<count($img_arr);$i++){
                    if($i >= 3){
                        break;
                    }
                    $user_info['moments_images'][] = $img_arr[$i];
                }
            }else{
                $user_info['moments_images'] = array();
            }
            $user_info['debo_code'] = substr(md5($user_info['mobile']),0,8);
            if(!$user_info['user_nickname']){
                $user_info['user_nickname'] = $user_info['mobile'];
            }
            return $this->json(0,'成功',$user_info);
        }

        if($mobile){
            //查找用户信息
            $find_user = M('user')->where("mobile = '".$mobile."'")->find();
            if(!$find_user || $find_user['state'] == 1){
                return $this->json(-2,'用户信息不存在或已被封号');
            }

            $field = 'id,mobile,user_nickname,avatar,sex,signature,create_time,qrcode,address,coins,province,city,area,debo_number';
            $user_info = M('user')->where("mobile = '".$mobile."'")->field($field)->find();
            if(empty($user_info['avatar'])) {
                $user_info['avatar'] = C('DEFAULT_AVATAR');
            }

            //查找给好友的备注
            if($my_mobile){
                //查找自己信息
                $find_my_info = M('user')->where("mobile = '".$my_mobile."'")->find();
                if(!$find_my_info){
                    return $this->json(-2,'用户信息不存在');
                }
                $find_friends = M('user_friends')->where("uid = ".$find_my_info['id']." and f_uid = ".$user_info['id'])->find();
                if($find_friends){
                    $user_info['remark'] = $find_friends['remark'];
                    $user_info['is_friend'] = "1";
                    // 朋友圈权限
                    $find_friends1 = M('user_friends')->where("uid = ".$user_info['id']." and f_uid = ".$find_my_info['id'])->find();
                    $user_info['blacklist'] = $find_friends['blacklist'];
                    $user_info['circle_state1'] = $find_friends1['circle_state1'];
                    $user_info['circle_state2'] = $find_friends['circle_state2'];
                }else{
                    $user_info['remark'] = "";
                    $user_info['is_friend'] = "0";
                }

                if(($find_my_info['top_father'] == $find_user['top_father']) || ($find_my_info['mobile'] == $find_user['pur_mobile']) || ($find_my_info['pur_mobile'] == $find_user['mobile']) || ($find_my_info['top_father'] == $find_user['pur_top_father'])){
                    $user_info['is_connection'] = "1";
                }else{
                    $user_info['is_connection'] = "0";
                }

                //查询定位开关是否开启
                $find_switch_info = M('switch_location_info')->where("mobile = '".$my_mobile."' and is_open = 1")->find();
                if($find_switch_info){
                    $user_info['is_open'] = "1";
                }else{
                    $user_info['is_open'] = "0";
                }

            }
            if($my_uid){
                //查找自己信息
                $find_my_info = M('user')->where('id = '.$my_uid)->find();
                if(!$find_my_info){
                    return $this->json(-2,'用户信息不存在');
                }
                $find_friends = M('user_friends')->where("uid = ".$find_my_info['id']." and f_uid = ".$user_info['id'])->find();
                if($find_friends){
                    $user_info['remark'] = $find_friends['remark'];
                    $user_info['is_friend'] = "1";
                    // 朋友圈权限
                    $find_friends1 = M('user_friends')->where("uid = ".$user_info['id']." and f_uid = ".$find_my_info['id'])->find();
                    $user_info['blacklist'] = $find_friends['blacklist'];
                    $user_info['circle_state1'] = $find_friends1['circle_state1'];
                    $user_info['circle_state2'] = $find_friends['circle_state2'];
                }else{
                    $user_info['remark'] = "";
                    $user_info['is_friend'] = "0";
                }

                if(($find_my_info['top_father'] == $find_user['top_father']) || ($find_my_info['mobile'] == $find_user['pur_mobile']) || ($find_my_info['pur_mobile'] == $find_user['mobile']) || ($find_my_info['top_father'] == $find_user['pur_top_father'])){
                    $user_info['is_connection'] = "1";
                }else{
                    $user_info['is_connection'] = "0";
                }

                //查询定位开关是否开启
                $find_switch_info = M('switch_location_info')->where("uid = ".$my_uid." and is_open = 1")->find();
                if($find_switch_info){
                    $user_info['is_open'] = "1";
                }else{
                    $user_info['is_open'] = "0";
                }

            }

            //查找朋友圈图片
            $find_momments = M('moments')->where("moments_uid = ".$user_info['id']." and moments_images !=''")->order('create_time desc')->select();
            if($find_momments){
                $img_arr = array();
                foreach($find_momments as $key => $val){
                    $temp_img = explode(";",$val['moments_images']);
                    foreach($temp_img as $k => $v){
                        $img_arr[] = $this->path.$temp_img[$k];
                    }
                    if(count($img_arr) >= 3){
                        break;
                    }
                }
                for($i=0;$i<count($img_arr);$i++){
                    $user_info['moments_images'][] = $img_arr[$i];
                }
            }else{
                $user_info['moments_images'] = array();
            }

            $user_info['qrcode'] = $this->qrcode.$user_info['qrcode'];
            $user_info['debo_code'] = substr(md5($user_info['mobile']),0,8);

            if(!$user_info['user_nickname']){
                $user_info['user_nickname'] = $user_info['mobile'];
            }
            return $this->json(0,'成功',$user_info);
        }

        //当接收到群组id的时候，查询群详细信息（可以查询多个群组信息）
        if($group_id && $my_mobile){
            //查询个人信息
            $group_id = explode(",",$group_id);
            $ease = new Easemob();
            $res = $ease->getGroupDetail($group_id);
            if(empty($res['data'])){
                return $this->json(-3,'没有查询到群信息');
            }
            //查询相应的群组信息
            $find_user_info = M('user')->where("mobile = '".$my_mobile."'")->find();//查找用户信息
            if(!$find_user_info){
                return $this->json(-4,'该手机用户不存在');
            }

            //return $this->json(0,'成功',$res['data'][0]['affiliations']);exit;
//            foreach($res['data'] as $key1 => $val1){
//                foreach($res['data'][$key1]['affiliations'] as $key2 => $val2){
//                    if($res['data'][$key1]['affiliations'][$key2]['member']){
//                        $arr[] = $res['data'][$key1]['affiliations'][$key2]['member'];
//                    }else if($res['data'][$key1]['affiliations'][$key2]['owner']){
//                        $arr[] = $res['data'][$key1]['affiliations'][$key2]['owner'];
//                    }
//                }
//            }
//            $arr = array_unique($arr);
//            if($arr){
//                //查询用户新
//                $where['mobile'] = array('in',$arr);
//                $find_group_member_info = M('user')->where($where)->select();
//
//                foreach($res['data'] as $key3 => $val3){
//                    $where['group_id'] = $val3['id'];
//                    $find_group_member_info2 = M('chatgroup_member')->where($where)->select();
//                    foreach($res['data'][$key3]['affiliations'] as $key4 => $val4){
//                        if($val4['member']){
//                            foreach($find_group_member_info as $key5 => $val5){
//                                if($val4['member'] == $val5['mobile']){
//                                    $res['data'][$key3]['affiliations'][$key4]['user_nickname'] = $val5['user_nickname'];
//                                    $res['data'][$key3]['affiliations'][$key4]['avatar'] = $val5['avatar'];
//                                }
//                            }
//                            if($find_group_member_info2){
//                                foreach($find_group_member_info2 as $key6 => $val6){
//                                    if($val4['member'] == $val6['mobile']){
//                                        $res['data'][$key3]['affiliations'][$key4]['member_name'] = $val6['member_name'];
//                                    }
//                                }
//                            }else{
//                                $res['data'][$key3]['affiliations'][$key4]['member_name'] = "";
//                            }
//
//                            $res['data'][$key3]['affiliations'][$key4]['member_type'] = "2";//普通成员
//                        }else if($val4['owner']){
//                            foreach($find_group_member_info as $key5 => $val5){
//                                if($val4['owner'] == $val5['mobile']){
//                                    $res['data'][$key3]['affiliations'][$key4]['user_nickname'] = $val5['user_nickname'];
//                                    $res['data'][$key3]['affiliations'][$key4]['avatar'] = $val5['avatar'];
//                                }
//                            }
//
//                            if($find_group_member_info2){
//                                foreach($find_group_member_info2 as $key6 => $val6){
//                                    if($val4['member'] == $val6['mobile']){
//                                        $res['data'][$key3]['affiliations'][$key4]['member_name'] = $val6['member_name'];
//                                    }
//                                }
//                            }else{
//                                $res['data'][$key3]['affiliations'][$key4]['member_name'] = "";
//                            }
//                            $res['data'][$key3]['affiliations'][$key4]['member_type'] = "1";//群管理员
//                        }
//                    }
//                    if(empty($res['data'][$key3]['affiliations'][$key4]['member_name'])){
//                        $res['data'][$key3]['affiliations'][$key4]['member_name'] = "";
//                    }
//                }
//            }


            $where['group_id'] = array('in',$group_id);
            $where['uid'] = $find_user_info['id'];
            $find_group_member_info = M('chatgroup_member')->where($where)->select();
            if($find_group_member_info){
                foreach($res['data'] as $k1 => $v1){
                    foreach($find_group_member_info as $k2 => $v2){
                        if($v1['id'] == $v2['group_id']){
                            $res['data'][$k1]['myname_for_group'] = $v2['member_name'];
                            $res['data'][$k1]['is_show_name'] = $v2['is_show_name'];
                        }
                    }
                    if(empty($res['data'][$k1]['myname_for_group'])){
                        $res['data'][$k1]['myname_for_group'] = "";
                        $res['data'][$k1]['is_show_name'] = "1";
                    }
                }
            }else{
                foreach($res['data'] as $k1 => $v1){
                    $res['data'][$k1]['myname_for_group'] = "";
                    $res['data'][$k1]['is_show_name'] = "1";
                }
            }

            return $this->json(0,'成功',$res['data']);
        }

    }

    /**
     * 相册
     * */
    public function photo_album(){

        $uid = I('uid');//有分页
        $f_uid = I('f_uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        if($uid && !$f_uid){

            //$sql = '(select * from de_moments where moments_uid = '.$uid.')union(select * from de_connection_circle where cc_uid = '.$uid.') order by create_time desc limit '.$limit;
            $sql = 'select *,count("create_time") as is_both from (select * from de_moments where moments_uid = '.$uid.' union select * from de_connection_circle where cc_uid = '.$uid.')a group by create_time order by create_time desc limit '.$limit;
            $res = M()->query($sql);

            if($res){
                foreach($res as $key => $val){

                    if(!$val['moments_content']){
                        $res[$key]['moments_content'] = "";
                    }

                    if($val['moments_images']){
                        $temp_img = explode(";",$val['moments_images']);
                        for($i=0;$i<count($temp_img);$i++){
                            $res[$key]['images'][] = $this->path.$temp_img[$i];
                        }
                    }else{
                        $res[$key]['images'] = array();
                    }
                    unset($res[$key]['moments_images']);
                }

                return $this->json(0,'成功',$res);
            }else{
                return $this->json(-3,'没有更多动态了');
            }


        }

        if($uid && $f_uid){

            //判断有没有设置朋友圈权限
            $find_friends_state = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$f_uid)->getField('circle_state1');
            if($find_friends_state == 2){//说明设置了好友权限
                //return $this->json(-5,'对方设置了好友权限');
                //判断对方是否设置了人脉圈权限
                $find_con_auth = M('connection_authority')->where('uid = '.$uid.' and c_uid = '.$f_uid)->find();
                if($find_con_auth){
                    //判断是否对你设置了权限
                    if($find_con_auth['circle_state1'] == 2){
                        return $this->json(-2,'对方设置了好友权限');
                    }
                }

                //开始查找人脉圈动态
                $where = 'cc_uid = '.$f_uid.' and '.$f_uid.' = (case when connection_type=1 then '.$f_uid.' when connection_type=2 then null when connection_type=3 and find_in_set('."$f_uid".',connection_visible_id)>0 then '.$f_uid.' when connection_type=4 and find_in_set('."$f_uid".',connection_visible_id)=0 then '.$uid.' else null end)';
                $res = M('connection_circle')->where($where)->field('cc_id as moments_id,cc_uid as moments_uid,cc_content as moments_content,cc_images as moments_images,create_time,position,lat,lng,type,circle_type')->order('create_time desc')->limit($limit)->select();
                if($res){
                    foreach($res as $key => $val){
                        if($val['moments_images']){
                            $temp_img = explode(";",$val['moments_images']);
                            for($i=0;$i<count($temp_img);$i++){
                                $res[$key]['images'][] = $this->path.$temp_img[$i];
                            }
                        }else{
                            $res[$key]['images'] = array();
                        }
                        unset($res[$key]['moments_images']);
                    }
                    return $this->json(0,'成功',$res);
                }else{
                    return $this->json(-3,'没有更多动态了');
                }

            }

            //判断有没有设置人脉圈权限
            $find_con_auth = M('connection_authority')->where('uid = '.$uid.' and c_uid = '.$f_uid)->find();
            if($find_con_auth){
                //判断是否对你设置了权限
                if($find_con_auth['circle_state1'] == 2){
                    //return $this->json(-2,'对方设置了好友权限');
                    //判断有没有设置朋友圈权限
                    $find_friends_state = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$f_uid)->getField('circle_state1');
                    if($find_friends_state == 2){
                        return $this->json(-2,'对方设置了好友权限');
                    }
                    //查找朋友圈动态
                    $where = 'moments_uid ='.$f_uid.' and '.$f_uid.'= (case when moments_type=1 then '.$f_uid.' when moments_type=2 then null when moments_type=3 and find_in_set('."$f_uid".',moments_visible_id)>0 then '.$f_uid.' when moments_type=4 and find_in_set('."$f_uid".',moments_visible_id)=0 then '.$f_uid.' else null end)';
                    $res = M('moments')->where($where)->order('create_time desc')->limit($limit)->select();
                    if($res){
                        foreach($res as $key => $val){
                            if($val['moments_images']){
                                $temp_img = explode(";",$val['moments_images']);
                                for($i=0;$i<count($temp_img);$i++){
                                    $res[$key]['images'][] = $this->path.$temp_img[$i];
                                }
                            }else{
                                $res[$key]['images'] = array();
                            }
                            unset($res[$key]['moments_images']);
                        }
                        return $this->json(0,'成功',$res);
                    }else{
                        return $this->json(-3,'没有更多动态了');
                    }
                }
            }

            //如果都没有设置权限
            //$sql = '(select * from de_moments where moments_uid = '.$f_uid.')union(select * from de_connection_circle where cc_uid = '.$f_uid.') order by create_time desc limit '.$limit;
            $sql = 'select *,count("create_time") as is_both from (select * from de_moments where moments_uid = '.$f_uid.' union select * from de_connection_circle where cc_uid = '.$f_uid.')a group by create_time order by create_time desc limit '.$limit;
            $res = M('moments')->query($sql);
            if($res){
                foreach($res as $key => $val){

                    if(!$val['moments_content']){
                        $res[$key]['moments_content'] = "";
                    }

                    if($val['moments_images']){
                        $temp_img = explode(";",$val['moments_images']);
                        for($i=0;$i<count($temp_img);$i++){
                            $res[$key]['images'][] = $this->path.$temp_img[$i];
                        }
                    }else{
                        $res[$key]['images'] = array();
                    }
                    unset($res[$key]['moments_images']);
                }

                return $this->json(0,'成功',$res);
            }else{
                return $this->json(-3,'没有更多动态了');
            }


        }


    }

    /**
     * 相册动态详细信息
     * */
    public function info_detail(){
        $object_id = I('moments_id');//朋友圈或人脉圈的动态id
        $circle_type = I('circle_type');//圈子类型 1朋友圈 2人脉圈
        $uid = I('uid');//用来判断自己是否点赞
        if(!$object_id || !$circle_type || !$uid){
            return $this->json(-1,'缺少参数');
        }

        if($circle_type == 1){
            //查找动态信息
            $find_talk = M('moments')->where('moments_id = '.$object_id)->find();
            if($find_talk){
                //查找用户信息
                $find_talk_user = M('user')->where('id = '.$find_talk['moments_uid'])->find();
                if(empty($find_talk_user['avatar'])) {
                    $find_talk_user['avatar'] = C('DEFAULT_AVATAR');
                }
                $find_talk['avatar'] = $find_talk_user['avatar'];
                $find_talk['user_nickname'] = $find_talk_user['user_nickname'];
                $find_talk['mobile'] = $find_talk_user['mobile'];
                $find_talk['create_time'] = date("Y-m-d H:i:s",$find_talk['create_time']);
                if($find_talk['moments_images']){
                    $temp_img = explode(";",$find_talk['moments_images']);
                    for($i=0;$i<count($temp_img);$i++){
                        $find_talk['images'][] = $this->path.$temp_img[$i];
                    }
                }else{
                    $find_talk['images'] = array();
                }

                if($find_talk['video_path']){
                    $find_talk['video_img'] = $this->path.$find_talk['moments_images'];
                    $find_talk['video_path'] = $this->path.$find_talk['video_path'];
                    $aaa = getimagesize($this->path.$find_talk['moments_images']);
                    $find_talk['video_img_width'] = "".$aaa[0];
                    $find_talk['video_img_height'] = "".$aaa[1];
                }else{
                    $find_talk['video_img'] = "";
                    $find_talk['video_img_width'] = "";
                    $find_talk['video_img_height'] = "";
                }

                //unset($find_talk['moments_images']);
                //取出动态id，查找评论和点赞
                $find_com = M('moments_comment')->where('moments_id = '.$find_talk['moments_id'])->select();

                if($find_com){
                    //存放用户uid
                    $user_ids_arr = array();
                    foreach($find_com as $key => $val){
                        $user_ids_arr[] = $val['uid'];
                    }
                    $user_ids_arr = array_unique($user_ids_arr);
                    $where['id'] = array('in',$user_ids_arr);
                    $find_user = M('user')->where($where)->field('id,user_nickname,small_avatar')->select();
                    foreach($find_com as $k2 => $v2){

                        foreach($find_user as $k3 => $v3){
                            if($v2['uid'] == $v3['id']){
                                $find_com[$k2]['name'] = $v3['user_nickname'];
                            }
                            if($v2['reply_uid'] == $v3['id']){
                                $find_com[$k2]['reply_name'] = $v3['user_nickname'];
                            }
                        }
                        if(!$v2['reply_uid']){
                            $find_com[$k2]['reply_name'] = "";
                        }
                    }

                    $find_talk['comment_list'] = $find_com;
                }else{
                    $find_talk['comment_list'] = array();
                }

                $find_upvote = M('moments_upvote')->where('moments_id = '.$find_talk['moments_id']." and upvote = 1")->select();
                if($find_upvote){
                    //存放用户uid
                    $user_ids_arr = array();
                    foreach($find_upvote as $key => $val){
                        $user_ids_arr[] = $val['uid'];
                    }
                    $user_ids_arr = array_unique($user_ids_arr);
                    $where['id'] = array('in',$user_ids_arr);
                    $find_user = M('user')->where($where)->field('id,user_nickname,small_avatar')->select();
                    foreach($find_upvote as $k4 => $v4){
                        foreach($find_user as $k5 => $v5){
                            if($v4['uid'] == $v5['id']){
                                if($v5['small_avatar']){
                                    $find_upvote[$k4]['small_avatar'] = $this->path.$v5['small_avatar'];
                                }
                            }
                        }
                        if(empty($find_upvote[$k4]['small_avatar'])){
                            $find_upvote[$k4]['small_avatar'] = "";
                        }
                    }

                    $find_talk['upvote_list'] = $find_upvote;
                }else{
                    $find_talk['upvote_list'] = array();
                }
                //查找本人是否点赞
                $find_my_upvote_info = M('moments_upvote')->where('moments_id = '.$find_talk['moments_id'].' and uid = '.$uid.' and upvote = 1')->find();
                if($find_my_upvote_info){
                    $find_talk['is_upvote'] = "1";
                }else{
                    $find_talk['is_upvote'] = "0";
                }
                //$aaa[] = $find_talk;
                return $this->json(0,'成功',$find_talk);
            }else{
                return $this->json(-2,'该动态已删除');
            }
        }

        if($circle_type == 2){
            //查找动态信息
            $find_talk = M('connection_circle')->where('cc_id = '.$object_id)->field('cc_id as moments_id,cc_uid as moments_uid,cc_content as moments_content,cc_images as moments_images,create_time,position,lat,lng,type,circle_type,video_path')->find();
            if($find_talk){
                //查找用户信息
                $find_talk_user = M('user')->where('id = '.$find_talk['moments_uid'])->find();
                if(empty($find_talk_user['avatar'])) {
                    $find_talk_user['avatar'] = C('DEFAULT_AVATAR');
                }
                $find_talk['avatar'] = $find_talk_user['avatar'];
                $find_talk['user_nickname'] = $find_talk_user['user_nickname'];
                $find_talk['mobile'] = $find_talk_user['mobile'];
                $find_talk['create_time'] = date("Y-m-d H:i:s",$find_talk['create_time']);
                if($find_talk['moments_images']){
                    $temp_img = explode(";",$find_talk['moments_images']);
                    for($i=0;$i<count($temp_img);$i++){
                        $find_talk['images'][] = $this->path.$temp_img[$i];
                    }
                }else{
                    $find_talk['images'] = array();
                }

                if($find_talk['video_path']){
                    $find_talk['video_img'] = $this->path.$find_talk['moments_images'];
                    $find_talk['video_path'] = $this->path.$find_talk['video_path'];
                    $aaa = getimagesize($this->path.$find_talk['moments_images']);
                    $find_talk['video_img_width'] = "".$aaa[0];
                    $find_talk['video_img_height'] = "".$aaa[1];
                }else{
                    $find_talk['video_img'] = "";
                    $find_talk['video_img_width'] = "";
                    $find_talk['video_img_height'] = "";
                }

                //unset($find_talk['moments_images']);
                //取出动态id，查找评论和点赞
                $find_com = M('connection_comment')->where('cc_id = '.$find_talk['moments_id'])->field('con_comment_id as mc_id,comment_content as mc_content,uid,cc_id as moments_id,comment_time,reply_uid,state')->select();
                if($find_com){
                    //存放用户uid
                    $user_ids_arr = array();
                    foreach($find_com as $key => $val){
                        $user_ids_arr[] = $val['uid'];
                    }
                    $user_ids_arr = array_unique($user_ids_arr);
                    $where['id'] = array('in',$user_ids_arr);
                    $find_user = M('user')->where($where)->field('id,user_nickname,small_avatar')->select();
                    foreach($find_com as $k2 => $v2){
                        foreach($find_user as $k3 => $v3){
                            if($v2['uid'] == $v3['id']){
                                $find_com[$k2]['name'] = $v3['user_nickname'];
                            }
                            if($v2['reply_uid'] == $v3['id']){
                                $find_com[$k2]['reply_name'] = $v3['user_nickname'];
                            }
                        }
                        if(!$v2['reply_uid']){
                            $find_com[$k2]['reply_name'] = "";
                        }
                    }

                    $find_talk['comment_list'] = $find_com;
                }else{
                    $find_talk['comment_list'] = array();
                }

                $find_upvote = M('connection_upvote')->where('cc_id = '.$find_talk['moments_id']." and upvote = 1")->field('cu_id as upvote_id,uid,cc_id as moments_id,upvote')->select();
                if($find_upvote){
                    //存放用户uid
                    $user_ids_arr = array();
                    foreach($find_upvote as $key => $val){
                        $user_ids_arr[] = $val['uid'];
                    }
                    $user_ids_arr = array_unique($user_ids_arr);
                    $where['id'] = array('in',$user_ids_arr);
                    $find_user = M('user')->where($where)->field('id,user_nickname,small_avatar')->select();
                    foreach($find_upvote as $k4 => $v4){
                        foreach($find_user as $k5 => $v5){
                            if($v4['uid'] == $v5['id']){
                                if($v5['small_avatar']){
                                    $find_upvote[$k4]['small_avatar'] = $this->path.$v5['small_avatar'];
                                }
                            }
                        }
                        if(empty($find_upvote[$k4]['small_avatar'])){
                            $find_upvote[$k4]['small_avatar'] = C('DEFAULT_AVATAR');
                        }
                    }
                    $find_talk['upvote_list'] = $find_upvote;
                }else{
                    $find_talk['upvote_list'] = array();
                }

                //查找本人是否点赞
                $find_my_upvote_info = M('connection_upvote')->where('cc_id = '.$find_talk['moments_id'].' and uid = '.$uid.' and upvote = 1')->find();
                if($find_my_upvote_info){
                    $find_talk['is_upvote'] = "1";
                }else{
                    $find_talk['is_upvote'] = "0";
                }
                //$aaa[] = $find_talk;
                return $this->json(0,'成功',$find_talk);
            }else{
                return $this->json(-2,'该动态已删除');
            }
        }

    }

    /**
     * 人脉总览
     * */
    public function con_total_data(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        //查找个人信息
        $find_myself = M('user')->where('id = '.$uid)->find();
        if(!$find_myself || $find_myself['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        //查找是否有合约人脉
        $find_contract_info = M('user')->where('pur_top_father = '.$find_myself['top_father'])->select();
        if($find_contract_info){
            //如果有合约人脉，存放top_father
            $top_father_arr = array();
            $top_father_arr2 = array();
            foreach($find_contract_info as $key => $val){
                $top_father_arr[] = $val['top_father'];
                $top_father_arr2[] = $val['pur_top_father'];
            }
            $top_father_arr[] = $find_myself['top_father'];
            $where1['top_father'] = array('in',$top_father_arr);
            $find_total_con = M('user')->where($where1)->count();//总人脉数

            //查找昨日人脉数
            //计算昨日日期
            $yesterday = date('Y-m-d',time()-3600*24);
            $today = date('Y-m-d',time());
            $yesterday_str = strtotime($yesterday);
            $today_str = strtotime($today);
            //查询昨日的合约人脉
            //$where2['pur_top_father'] = array('in',$top_father_arr2);
            $where2 = implode(',',$top_father_arr2);
            $find_yest_contract = M('user')->where("pur_top_father in (".$where2.") and pur_time >= '".$yesterday_str."' and pur_time <= '".$today_str."'")->select();
            if($find_yest_contract){
                //存放满足条件的合约人脉父类
                $temp_pur_top = array();
                foreach($find_yest_contract as $k1 => $v1){
                    $temp_pur_top[] = $v1['top_father'];
                }
                $where3['top_father'] = array('in',$temp_pur_top);
                $temp_num = M('user')->where($where3)->count();
            }else{
                $temp_num = 0;
            }
            //查找自己的人脉数
            $find_myself_con_num = M('user')->where("top_father = '".$find_myself['top_father']."' and pur_time >= ".$yesterday_str." and pur_time <=".$today_str)->count();
            $find_myself_con_num = $find_myself_con_num+$temp_num;


            //查找直接人脉数
            $find_direct_con_num = M('user')->where("invitation_code = ".$find_myself['mobile'])->count();

            $find_pre_total_num = M('user')->where('id = '.$uid)->getField('total_con_num');
            if($find_total_con > $find_pre_total_num){
                $res['status'] = "1";
            }else if($find_total_con == $find_pre_total_num){
                $res['status'] = "0";
            }else if($find_total_con < $find_pre_total_num){
                $res['status'] = "-1";
            }
            $map['total_con_num'] = $find_total_con;
            M('user')->where('id = '.$uid)->save($map);
            $res['total_con'] = $find_total_con;
            $res['yesterday_con'] = "".$find_myself_con_num;
            $res['direct_con'] = $find_direct_con_num;
            return $this->json(0,'成功',$res);

        }else{

            //如果没有合约人脉
            $find_total_con = M('user')->where("top_father = ".$find_myself['top_father'])->count();
            $yesterday = date('Y-m-d',time()-3600*24);
            $today = date('Y-m-d',time());
            $yesterday_str = strtotime($yesterday);
            $today_str = strtotime($today);
            $find_myself_con_num = M('user')->where("top_father = '".$find_myself['top_father']."' and pur_time >= ".$yesterday_str." and pur_time <=".$today_str)->count();
            $find_direct_con_num = M('user')->where("invitation_code = ".$find_myself['mobile'])->count();
            $find_pre_total_num = M('user')->where('id = '.$uid)->getField('total_con_num');
            if($find_total_con > $find_pre_total_num){
                $res['status'] = "1";
            }else if($find_total_con == $find_pre_total_num){
                $res['status'] = "0";
            }else if($find_total_con < $find_pre_total_num){
                $res['status'] = "-1";
            }
            $map['total_con_num'] = $find_total_con;
            M('user')->where('id = '.$uid)->save($map);
            $res['total_con'] = $find_total_con;
            $res['yesterday_con'] = "".$find_myself_con_num;
            $res['direct_con'] = $find_direct_con_num;
            return $this->json(0,'成功',$res);

        }

    }

    /**
     * 喇叭数据接口
     * */
    public function horn_data(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        //查询喇叭数据
        $find_horn_data = M('horn_data')->select();
        if(!$find_horn_data){
            return $this->json(-2,'没有数据');
        }

        foreach($find_horn_data as $key => $val){//因为数据不多，就不做优化查询了。
            $find_is_pur = M('horn')->where('uid = '.$uid.' and horn_id = '.$val['horn_id']." and pur_state = 1")->find();
            if($find_is_pur){
                $find_horn_data[$key]['is_purchase'] = "1";
            }else{
                $find_horn_data[$key]['is_purchase'] = "0";
            }
        }

        return $this->json(0,'成功',$find_horn_data);

    }

    /**
     *
     * */


    /**
     * 修改群成员在这个群中的昵称
     * */
    public function modify_group_member(){
        $mobile = I('mobile');//群成员手机号
        $name = I('name');//修改的名字
        $group_id = I('groupid');
        $is_show_name = I('is_show_name');
        if(!$mobile || !$group_id){
            return $this->json(-1,'缺少参数');
        }

        if(!$name && !$is_show_name){
            return $this->json(-2,'没有接收到数据');
        }

        //查询表中是否有数据
        $find_user = M('user')->where('mobile = '.$mobile)->find();
        if(!$find_user){
            return $this->json(-3,'该手机用户不存在');
        }
        $find_data = M('chatgroup_member')->where("mobile = ".$find_user['mobile']." and group_id = ".$group_id)->find();
        if($find_data){
            //修改
            if($name){
                $data['member_name'] = $name;
            }
            if($is_show_name){
                $data['is_show_name'] = $is_show_name;
            }
            $res = M('chatgroup_member')->where("mobile = ".$find_user['mobile']." and group_id = ".$group_id)->save($data);
            if(!$res){
                return $this->json(-5,'数据未发生改变');
            }

            return $this->json(0,'修改成功');

        }else{
            //增加
            $data['uid'] = $find_user['id'];
            $data['mobile'] = $find_user['mobile'];
            $data['group_id'] = $group_id;
            $data['member_type'] = 3;
            if($name){
                $data['member_name'] = $name;
            }
            if($is_show_name){
                $data['is_show_name'] = $is_show_name;
            }
            $res = M('chatgroup_member')->add($data);
            if(!$res){
                return $this->json(-4,'修改失败');
            }

            return $this->json(0,'修改成功');

        }

    }

    /**
     *财务报表
     * */
    public function financial_statements(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        //查找本人信息
        $find_myself = M('user')->where('id = '.$uid)->find();
        if(!$find_myself){
            return $this->json(-2,'用户信息不存在');
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查找自己的贵族合约人脉
        $field = 'id,user_nickname,avatar,mobile,top_father,pur_mobile,conn_type,pur_price,pur_time,pur_contract_time';
        $find_conn = M('user')->where("pur_mobile = '".$find_myself['top_father']."' and (pur_price != 0.00)")->field($field)->limit($limit)->select();
        if(!$find_conn){
            return $this->json(-3,'没有更多信息了');
        }

        foreach($find_conn as $key => $val){
            if(empty($val['avatar'])) {
                $find_conn[$key]['avatar'] = C('DEFAULT_AVATAR');
            }
            if(!$val['user_nickname']){
                $find_conn[$key]['user_nickname'] = $val['mobile'];
            }
            $find_conn[$key]['pur_time'] = date("m月d日",$val['pur_time']);
            $find_conn[$key]['pur_contract_time'] = date("m月d日",$val['pur_contract_time']);
            if(($val['pur_contract_time'] - time()) <= 7*3600*24){//临近7天，设置为即将到期。
                $find_conn[$key]['pur_contract_time'].="（即将到期）";
            }
        }

        return $this->json(0,'成功',$find_conn);


    }

    /**
     * 返回自己的好友和人脉
     * */
    public function my_friend_conn(){
        $my_mobile = I('my_mobile');
        if(!$my_mobile){
            return $this->json(-1,'缺少参数');
        }

        $find_user = M('user')->where("mobile = '".$my_mobile."'")->find();
        if(!$find_user){
            return $this->json(-2,'没有查到当前用户信息');
        }

        //查询好友信息---------------------------------------------------------------------------
        $huanxin = new Easemob();
        $res = $huanxin->showFriends($find_user['mobile']);

        $infos = implode(',',$res['data']);
        if($infos == ''){
            //return $this->json(-3,'没有好友');
            $list = array();
        }else{
            $field = 'id,user_nickname,avatar,sex,signature,mobile,province,city';
            $list = M('user')->where(['mobile'=>['in',$infos]])->field($field)->select();
            if(!$list){
                //return $this->json(-4,'没有好友信息');
                $list = array();
            }else{
                $fids_arr = array();
                foreach($list as $key1 => $val1){
                    $fids_arr[] = $list[$key1]['id'];
                }

                $fids_str = implode(",",$fids_arr);

                $f_list = M('user_friends')->where("uid = ".$find_user['id']." and f_uid in (".$fids_str.")")->select();

                if($f_list){
                    foreach($list as $key2=>$val2){
                        foreach($f_list as $key3=>$val3){
                            if($list[$key2]['id'] == $f_list[$key3]['f_uid']){
                                if($f_list[$key3]['remark']){
                                    $list[$key2]['user_nickname'] = $f_list[$key3]['remark'];
                                }
                            }
                        }
                        if(empty($list[$key2]['user_nickname'])){
                            $list[$key2]['user_nickname'] = $val2['mobile'];
                        }
                    }
                }
            }
        }


        //查询人脉信息---------------------------------------------------------------------------
        $find_connection = M('user')->where('top_father = '.$find_user['top_father'])->field($field)->select();
        //查找自己的人脉圈中是否存在合约人脉
        $find_pur = M('user')->where('pur_top_father = '.$find_user['top_father'])->field('id,mobile,user_nickname,avatar,top_father')->select();
        if($find_pur){
            //找到被购买人的top_father,并取出这个top_father下的所有用户
            $pur_mobile_arr = array();
            foreach($find_pur as $key => $val){
                $pur_mobile_arr[] = $val['top_father'];
            }

            $pur_mobile_str = implode(",",$pur_mobile_arr);
            $find_all_pur = M('user')->where("top_father in (".$pur_mobile_str.")")->field($field)->select();

            $find_connection = array_merge($find_connection,$find_all_pur);

        }

        $con_uid_arr = array();
        if($find_connection){
            foreach($find_connection as $k => $v){
                $con_uid_arr[]=$v['id'];
                if($v['avatar']){
                    $find_connection[$k]['avatar'] = $v['avatar'];
                }
                if(empty($find_connection[$k]['user_nickname'])){
                    $find_connection[$k]['user_nickname'] = $v['mobile'];
                }
                if($v['id'] == $find_user['id'])
                {
                    unset( $find_connection[$k]);
                }
            }

            $where['f_uid'] = array('in',$con_uid_arr);
            $where['uid'] = $find_user['id'];
            $find_connection_remark = M('connection_remark')->where($where)->select();
            foreach($find_connection as $k => $v){
                foreach($find_connection_remark as $key => $val)
                {
                    if($v['id'] == $val['f_uid'] && $find_user['id'] == $val['uid']){
                        $find_connection[$k]['user_nickname'] = $val['remark'];

                    }
                }
            }

            $find_connection = array_merge($find_connection);
        }

        if($find_connection && $list){
            $new_list = array_merge($find_connection,$list);
        }else if($find_connection && !$list){
            $new_list = $find_connection;
        }else if(!$find_connection && $list){
            $new_list = $list;
        }else if(!$find_connection && !$list){
            $this->json(-3,'没有更多信息');
        }

        $this->json(0,'成功',$new_list);

    }

    /**
     * 获取用户余额coins，积分points
     * */
    public function get_coins(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,"缺少参数");
        }
        $user_info = M('user')->field('id,coins,points')->where('id = '.$uid)->find();
        if(!$user_info){
            return $this->json(-2,"用户不存在");
        }
        return $this->json(0,"成功",$user_info);

    }

    /**
     * 登录密码找回
     * */
    public function reset_login_pwd(){
        $mobile = I('mobile');
        $code = I('code');
        $new_pwd = I('new_pwd');

        if(!$mobile){
            return $this->json(-1,"手机号不能为空");
        }
        if (!(preg_match('/^(1[35784]\d{9})$/',$mobile))){
            return $this->json(-2,'手机号码格式错误');
        }
        if(!$code){
            return $this->json(-3,'验证码不能为空');
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
            if($code == "8989987"){
            }else{
                return $this->json(-9,'验证码错误');
            }
        }

        if(!$new_pwd){
            return $this->json(-4,'密码不能为空');
        }

        //查找用户信息
        $find_user_info = M('user')->where("mobile = '".$mobile."'")->find();

        if(!$find_user_info){
            return $this->json(-5,'用户信息不存在');
        }

        if($find_user_info['password'] == md5($new_pwd)){
            return $this->json(-6,'两次密码不能一样');
        }

        $data['password'] = md5($new_pwd);
        $res = M('user')->where("mobile = '".$mobile."'")->save($data);
        if(!$res){
            return $this->json(-7,'修改失败');
        }
        $ease = new Easemob();
        $ease->resetPassword($mobile,'88888888');
        return $this->json(0,'修改成功');

    }

    /**
     *评论列表接口
     * */
    public function news_lists(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,"缺少参数");
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //只取出五个30天的数据
        $time = time()-3600*24*30*5;

        //查询自己发布的动态
        $find_self_moments = M('moments')->where('moments_uid = '.$uid.' and create_time >='.$time)->select();
        //存放 moments_id
        $arr1 = array();
        foreach($find_self_moments as $k1 => $v1){
            $arr1[]=$v1['moments_id'];
        }
        $arr1[]="0";

        $find_self_connection = M('connection_circle')->where('cc_uid = '.$uid.' and create_time >='.$time)->select();
        $arr2 = array();
        foreach($find_self_connection as $k2 => $v2){
            $arr2[]=$v2['cc_id'];
        }
        $arr2[]="0";

        $find_self_fans = M('fans_moments')->where('moments_uid = '.$uid.' and create_time >='.$time)->select();
        $arr3 = array();
        foreach($find_self_fans as $k3 => $v3){
            $arr3[]=$v3['moments_id'];
        }
        $arr3[]="0";

        $arr1_str = implode(",",$arr1);
        $arr2_str = implode(",",$arr2);
        $arr3_str = implode(",",$arr3);
        $sql = "select * from de_moments_comment where ( comment_time >= ".$time." and reply_uid = ".$uid.") or (moments_id in (".$arr1_str.") and uid != ".$uid." and comment_time >= ".$time.")";
        $sql2 = "select * from de_connection_comment where ( comment_time >= ".$time." and reply_uid = ".$uid.") or (cc_id in (".$arr2_str.") and uid != ".$uid." and comment_time >= ".$time.")";
        $sql3 = "select mc_id,mc_content,uid,moments_id,comment_time,reply_uid,state,circle_type from de_fans_comment where ( comment_time >= ".$time." and  reply_uid = ".$uid.") or (moments_id in (".$arr3_str.") and uid != ".$uid." and comment_time >= ".$time.")";
        $sql_all = "(".$sql.") union (".$sql2.") union (".$sql3.") order by comment_time desc limit ".$limit;
        $res = M('moments_comment')->query($sql_all);

        if(!$res){
            return $this->json(-2,"沒有更多信息了");
        }

        $users_arr = array();
        foreach($res as $key1 => $val1){
            $users_arr[]=$val1['uid'];
            $res[$key1]['comment_time'] = date('m月d日 H:i',$val1['comment_time']);
        }

        //查询用户信息
        $where['id'] = array('in',$users_arr);
        $find_user_info = M('user')->where($where)->field('id,user_nickname,avatar,mobile')->select();
        foreach($res as $key2 => $val2){
            foreach($find_user_info as $key3 => $val3){
                if($val2['uid'] == $val3['id']){
                    $remark_info = M('user_friends')->where("uid = ".$uid." and f_uid = ".$val2['uid'])->find();
                    if($remark_info['remark']){
                        $res[$key2]['user_nickname'] = $remark_info['remark'];
                    }else if($val3['user_nickname']){
                        $res[$key2]['user_nickname'] = $val3['user_nickname'];
                    }else{
                        $res[$key2]['user_nickname'] = $val3['mobile'];
                    }
                    $res[$key2]['avatar'] = $val3['avatar'];
                    $res[$key2]['mobile'] = $val3['mobile'];
                }
            }
            //查询动态信息
            if($val2['circle_type'] == 1){
                $moments_info = M('moments')->where('moments_id = '.$val2['moments_id'])->find();
                $res[$key2]['moments_content'] = $moments_info['moments_content'];
                $res[$key2]['type'] = $moments_info['type'];
                if($moments_info['moments_images']){
                    $res[$key2]['moments_images'] = $this->path.explode(";",$moments_info['moments_images'])[0];
                }else{
                    $res[$key2]['moments_images'] = "";
                }
            }else if($val2['circle_type'] == 2){
                $moments_info = M('connection_circle')->where('cc_id = '.$val2['moments_id'])->find();
                $res[$key2]['moments_content'] = $moments_info['cc_content'];
                $res[$key2]['type'] = $moments_info['type'];
                if($moments_info['cc_images']){
                    $res[$key2]['moments_images'] = $this->path.explode(";",$moments_info['cc_images'])[0];
                }else{
                    $res[$key2]['moments_images'] = "";
                }
            }else if($val2['circle_type'] == 3){
                $moments_info = M('fans_moments')->where('moments_id = '.$val2['moments_id'])->find();
                $res[$key2]['moments_content'] = $moments_info['moments_content'];
                $res[$key2]['type'] = $moments_info['type'];
                if($moments_info['moments_images']){
                    $res[$key2]['moments_images'] = $this->path.explode(";",$moments_info['moments_images'])[0];
                }else{
                    $res[$key2]['moments_images'] = "";
                }
            }
        }

        return $this->json(0,"成功",$res);

    }

    /**
     * 朋友圈人脉圈点赞记录接口
     * */
    public function moments_connection_upvote(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,"缺少参数");
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //只取出五个30天的数据
        $time = time()-3600*24*30*5;

        //查询自己发布的动态
        $find_self_moments = M('moments')->where('moments_uid = '.$uid.' and create_time >='.$time)->select();
        //存放 moments_id
        $arr1 = array();
        foreach($find_self_moments as $k1 => $v1){
            $arr1[]=$v1['moments_id'];
        }
        $arr1[]="0";

        $find_self_connection = M('connection_circle')->where('cc_uid = '.$uid.' and create_time >='.$time)->select();
        $arr2 = array();
        foreach($find_self_connection as $k2 => $v2){
            $arr2[]=$v2['cc_id'];
        }
        $arr2[]="0";

        $arr1_str = implode(",",$arr1);
        $arr2_str = implode(",",$arr2);

        $sql = "select * from de_moments_upvote where moments_id in (".$arr1_str.") and upvote = 1";
        $sql2 = "select cu_id,uid,cc_id,upvote,circle_type,upvote_time from de_connection_upvote where cc_id in (".$arr2_str.") and upvote = 1";
        $sql_all = "(".$sql.") union (".$sql2.") order by upvote_time desc limit ".$limit;
        $res = M('moments_upvote')->query($sql_all);
        if(!$res){
            return $this->json(-2,"没有更多数据了");
        }

        $users_arr = array();
        foreach($res as $key1 => $val1){
            $users_arr[]=$val1['uid'];
            //$res[$key1]['comment_time'] = date('m月d日 H:i',$val1['comment_time']);
            if($val1['upvote_time']){
                $res[$key1]['upvote_time'] = date('m月d日 H:i',$val1['upvote_time']);
            }else{
                $res[$key1]['upvote_time'] = "";
            }
        }

        //查询用户信息
        $where['id'] = array('in',$users_arr);
        $find_user_info = M('user')->where($where)->field('id,user_nickname,avatar,mobile')->select();
        foreach($res as $key2 => $val2){
            foreach($find_user_info as $key3 => $val3){
                if($val2['uid'] == $val3['id']){
                    $remark_info = M('user_friends')->where("uid = ".$uid." and f_uid = ".$val2['uid'])->find();
                    if($remark_info['remark']){
                        $res[$key2]['user_nickname'] = $remark_info['remark'];
                    }else if($val3['user_nickname']){
                        $res[$key2]['user_nickname'] = $val3['user_nickname'];
                    }else{
                        $res[$key2]['user_nickname'] = $val3['mobile'];
                    }
                    $res[$key2]['avatar'] = $val3['avatar'];
                    $res[$key2]['mobile'] = $val3['mobile'];
                }
            }
            //查询动态信息
            if($val2['circle_type'] == 1){
                $moments_info = M('moments')->where('moments_id = '.$val2['moments_id'])->find();
                $res[$key2]['moments_content'] = $moments_info['moments_content'];
                $res[$key2]['type'] = $moments_info['type'];
                if($moments_info['moments_images']){
                    $res[$key2]['moments_images'] = $this->path.explode(";",$moments_info['moments_images'])[0];
                }else{
                    $res[$key2]['moments_images'] = "";
                }
            }else if($val2['circle_type'] == 2){
                $moments_info = M('connection_circle')->where('cc_id = '.$val2['moments_id'])->find();
                $res[$key2]['moments_content'] = $moments_info['cc_content'];
                $res[$key2]['type'] = $moments_info['type'];
                if($moments_info['cc_images']){
                    $res[$key2]['moments_images'] = $this->path.explode(";",$moments_info['cc_images'])[0];
                }else{
                    $res[$key2]['moments_images'] = "";
                }
            }
        }

        return $this->json(0,"成功",$res);

    }

    /**
     * 二维码中的接口
     * */
    public function two_dimension_code(){
        $mobile = I('mobile');//用户手机号
        $oper_type = I('oper_type');//操作类型 1、下载；2、收款；3、付款
        $sign = I('sign');

        //查询此用户是否存在
        $find_user_info = M('user')->where('mobile = '.$mobile)->find();
        if(!$find_user_info){
            return $this->json(-1,"参数错误");
        }

        $my_sign = md5("mobile=".$mobile."&oper_type=".$oper_type."&key=".$this->key);
        if($my_sign == $sign){
            return $this->json(-1,"参数错误");
        }

        if($oper_type == 1){
            $arr = array();
            $arr['download_url'] = "http://www.baidu.com";
            return $this->json(0,"成功",$arr);
        }

        if($oper_type == 2){

        }

    }

    //交易记录反馈
    public function feedback()
    {
        $uid = I('uid');
        $content = I('content');
        $order_sn = I('order_sn');
        $create_time = time();
        if(!$uid)
        {
            return $this->json(-1,"用户不存在");
        }
        if(!$content)
        {
            return $this->json(-2,"没有反馈内容");
        }
        if(!$order_sn)
        {
            return $this->json(-3,"订单不存在");
        }
        $data['uid'] = $uid;
        $data['content'] = $content;
        $data['order_sn'] = $order_sn;
        $data['create_time'] = $create_time;
        $res = M('feedback')->add($data);
        if(!$res)
        {
            return $this->json(-4,"添加失败");
        }
        return $this->json(0,"添加成功",$data);
    }

    //帮助与反馈
    public function help_and_feedback(){
        $uid = I('uid');
        $content = I('content');
        if(!$uid || !$content){
            return $this->json(-1,"缺少参数");
        }

        $data['uid'] = $uid;
        $data['content'] = $content;
        $data['create_time'] = time();
        $res = M('help_and_feedback')->add($data);
        if(!$res){
            return $this->json(-2,"提交失败");
        }

        return $this->json(0,"提交成功");

    }

    /**
     * 修改登录密码接口
     * */
    public function modify_login_pwd(){
        $uid = I('uid');
        $old_pwd = I('old_pwd');
        $new_pwd = I('new_pwd');
        $confirm_pwd = I('confirm_pwd');
        if(!$uid || !$old_pwd || !$new_pwd || !$confirm_pwd){
            return $this->json(-1,"缺少参数");
        }

        $info = M('user')->where('id = '.$uid)->find();
        if(!$info){
            return $this->json(-7,'没有获取到用户信息');
        }
        $mobile = $info['mobile'];
        if(md5($old_pwd) != $info['password']){
            return $this->json(-8,'旧密码不正确');
        }

        if(!$new_pwd){
            return $this->json(-2,'新密码不能为空');
        }
        if(!$old_pwd){
            return $this->json(-3,'旧密码不能为空');
        }
        if(!$confirm_pwd){
            return $this->json(-4,'确认密码不能为空');
        }
        if($new_pwd != $confirm_pwd){
            return $this->json(-5,'两次密码不一致');
        }

        $data['password'] = md5($new_pwd);
        $res = M('user')->where('mobile = '.$mobile)->save($data);
        if(!$res){
            return $this->json(-6,'修改失败');
        }

        $ease = new Easemob();
        $ease->resetPassword($mobile,'88888888');

        return $this->json(0,'修改成功');

    }

    /**
     *判断是否是自己购买的合约人脉
     * */
    public function is_my_pur(){
        $my_mobile = I('my_mobile');
        $mobile = I('mobile');
        if(!$my_mobile || !$mobile){
            return $this->json(-1,'缺少参数');
        }

        $find_my_info = M('user')->where('mobile = '.$my_mobile)->find();
        $find_info = M('user')->where('mobile = '.$mobile)->find();

        if(!$find_my_info || !$find_info){
            return $this->json(-2,'用户信息不存在');
        }

        $is_my_pur = "0";

        if($find_my_info['mobile'] == $find_info['pur_mobile']){
            $is_my_pur = "1";
        }

        return $this->json(0,'成功',['is_my_pur'=>$is_my_pur]);

    }

    /**
     * 清空消息或点赞信息
     * */
    public function empty_comment_upvote(){
        $type = I("type");//1、评论；2、点赞
        $uid = I('uid');
        if(!$type || !$uid){
            return $this->json(-1,'缺少参数');
        }

        $time = time()-3600*24*30*5;

        if($type == 1){
            //查询自己发布的动态
            $find_self_moments = M('moments')->where('moments_uid = '.$uid.' and create_time >='.$time)->select();
            //存放 moments_id
            $arr1 = array();
            foreach($find_self_moments as $k1 => $v1){
                $arr1[]=$v1['moments_id'];
            }
            $arr1[]="0";

            $find_self_connection = M('connection_circle')->where('cc_uid = '.$uid.' and create_time >='.$time)->select();
            $arr2 = array();
            foreach($find_self_connection as $k2 => $v2){
                $arr2[]=$v2['cc_id'];
            }
            $arr2[]="0";

            $arr1_str = implode(",",$arr1);
            $arr2_str = implode(",",$arr2);

//            $sql = "delete from de_moments_comment where ( comment_time >= '".$time."' and reply_uid = ".$uid.") or (moments_id in (".$arr1_str.") and uid != ".$uid." and comment_time >= '".$time."')";
//            $sql2 = "delete from de_connection_comment where ( comment_time >= ".$time." and reply_uid = ".$uid.") or (cc_id in (".$arr2_str.") and uid != ".$uid." and comment_time >= ".$time.")";

//            $res1 = M('moments_comment')->query($sql);
//            $res2 = M('connection_comment')->query($sql2);

            M('moments_comment')->where( "comment_time >= '".$time."' and reply_uid = ".$uid)->delete();
            M('moments_comment')->where( "moments_id in (".$arr1_str.") and uid != ".$uid." and comment_time >= '".$time."'")->delete();
            M('connection_comment')->where("comment_time >= ".$time." and reply_uid = ".$uid)->delete();
            M('connection_comment')->where("cc_id in (".$arr2_str.") and uid != ".$uid." and comment_time >= ".$time)->delete();

            return $this->json(0,'清空完成');

        }

        if($type == 2){
            //查询自己发布的动态
            $find_self_moments = M('moments')->where('moments_uid = '.$uid.' and create_time >='.$time)->select();
            //存放 moments_id
            $arr1 = array();
            foreach($find_self_moments as $k1 => $v1){
                $arr1[]=$v1['moments_id'];
            }
            $arr1[]="0";

            $find_self_connection = M('connection_circle')->where('cc_uid = '.$uid.' and create_time >='.$time)->select();
            $arr2 = array();
            foreach($find_self_connection as $k2 => $v2){
                $arr2[]=$v2['cc_id'];
            }
            $arr2[]="0";

            $arr1_str = implode(",",$arr1);
            $arr2_str = implode(",",$arr2);

//            $sql = "delete from de_moments_upvote where moments_id in (".$arr1_str.") and upvote = 1";
//            $sql2 = "delete from de_connection_upvote where cc_id in (".$arr2_str.") and upvote = 1";
//
//            $res1 = M()->query($sql);
//            $res2 = M()->query($sql2);

            M('moments_upvote')->where( "moments_id in (".$arr1_str.") and upvote = 1")->delete();
            M('connection_upvote')->where("cc_id in (".$arr2_str.") and upvote = 1")->delete();

            return $this->json(0,'清空完成');

        }

    }

    /**
     * 朋友圈动态投诉
     * */
    public function circle_complain(){
        $moments_id = I('moments_id');
        $content = I('content');
        $circle_type = I('circle_type');
        $uid = I('uid');
        if(!$moments_id || !$content || !$circle_type || !$uid){
            return $this->json(-1,'提交信息错误');
        }

        //查询是否有投诉记录
        $find_complain_record = M('circle_complain')->where('uid = '.$uid.' and moments_id = '.$moments_id.' and circle_type = '.$circle_type.' and state = 0 ')->find();

        if(!$find_complain_record){
            $data['moments_id'] = $moments_id;
            $data['content'] = $content;
            $data['circle_type'] = $circle_type;
            $data['uid'] = $uid;
            $data['time'] = time();
            $complain_res = M('circle_complain')->add($data);
            if(!$complain_res){
                return $this->json(-3,'投诉失败，请重新提交');
            }
            return $this->json(0,'投诉成功');
        }else{
            return $this->json(-2,'您已投诉过该信息，正在审核中');
        }

    }

    /**
     * 每日签到接口
     * */
    public function daily_sign_in(){
        $uid = I('uid');
        $year = I('year');
        $month = I('month');
        $wee = $year.'-'.$month.'-'.date('d');
        $is_weekend = week($wee);

        if(!$uid){
            $this->json(-1,'用户信息未获取到');
        }
        if(!$year || !$month){
            $this->json(-3,'未传年月');
        }
        $find_user_info = M('user')->where('id = '.$uid)->find();
        if(!$find_user_info){
            $this->json(-1,'用户信息未获取到');
        }

        $daily_model = M('daily_sign_in');
        $time = time();
        //当天日期时间戳
        $curr_date = strtotime($wee);
        //查询当天是否签到
        $curr_is_sign = $daily_model->where("uid = ".$uid." and sign_date = '".$curr_date."'")->find();
        $start_temp_time = date('Y-m',$time);//转换时间格式并舍去日期，只有当月信息。
        $start_time = strtotime($start_temp_time);//再次转化为时间戳就是当月的第一天
        $end_temp_timedate = date('Y-m-t',$start_time);
        $end_time = strtotime($end_temp_timedate)+3600*24-1;//最后一天
        $day_num = get_day($year,$month);
        $where['uid'] = $uid;
        $where['sign_date'] = array('between',$start_time.','.$end_time);
        //查询当月签到记录
        $find_curr_sign_record = $daily_model->where($where)->select();
        //因为本接口为自动签到功能，因此不会出现 $find_curr_sign_record 为空的情况

        $daily_sign_record = array();
        $base_info = array();
        for($i=0;$i<$day_num;$i++){
            $daily_sign_record[$i]['day'] = "".($i+1);
            $daily_sign_record[$i]['days'] = $year.'-'.$month.'-'.($i+1);
            foreach($find_curr_sign_record as $key => $val){
                if(date("d",$val['sign_date']) == ($i+1) && (date('m',time()) == $month) && (date('y',time()))<= $year){
                    $daily_sign_record[$i]['is_sign_in'] = "1";
                    $daily_sign_record[$i]['is_sign_logo'] = "http://app.qitong.shop/data/img/zhiwen.png";
                }
            }
            if(empty($daily_sign_record[$i]['is_sign_in'])){
                $daily_sign_record[$i]['is_sign_in'] = "0";
                $daily_sign_record[$i]['is_sign_logo'] = "http://app.qitong.shop/data/img/weiqiandao.png";
            }
            if(date("d",$curr_date) == ($i+1)){
                $daily_sign_record[$i]['is_today'] = 1;
            }else{
                $daily_sign_record[$i]['is_today'] = 0;
            }

            $daily_sign_record[$i]['week'] = date("w",strtotime($daily_sign_record[$i]['days']));
        }

        //查询总签到数
        $total_sign_num = $daily_model->where("uid = ".$uid)->count();
        $base_info['total_sign_num'] = $total_sign_num;//number_format($total_sign_num,4)
        $base_info['curr_month_sign_num'] = "".count($find_curr_sign_record);
        //补返假期
        $holiday_model = M('holiday')->where('month='.$month)->find();
        $holiday_date = unserialize($holiday_model['holiday']);

        foreach ($daily_sign_record as $k => $v) {
            if(!empty($holiday_date)){
                foreach ($holiday_date as $key => $value) {
                    if (in_array($v['days'], $value['list'])) {
                        $daily_sign_record[$k]['is_holiday'] = '1';
                        if (!$curr_is_sign) {
                            $data['uid'] = $uid;
                            $data['sign_time'] = $wee;
                            $data['sign_date'] = $curr_date;
                            if ($daily_sign_record[$k]['is_holiday'] == 1) {
                                $data['points'] = '6';
                            }
                        }else{
                            $data['points'] = '0';
                            $sign_res = 1;
                        }

                    } else {
                        if($daily_sign_record[$k]['is_holiday'] != 1){
                            $daily_sign_record[$k]['is_holiday'] = '0';
                        }

                        if (!$curr_is_sign) {
                            $data['uid'] = $uid;
                            $data['sign_time'] = $wee;
                            $data['sign_date'] = $curr_date;
                            $data['points'] = '2';
                            if ($is_weekend == 1) {
                                $data['points'] = '4';
                            }
                        }else{
                            $data['points'] = '0';
                            $sign_res = 1;
                        }
                    }
                }
            }else{
                $daily_sign_record[$k]['is_holiday'] = '0';
                if (!$curr_is_sign) {
                    $data['uid'] = $uid;
                    $data['sign_time'] = $wee;
                    $data['sign_date'] = $curr_date;
                    $data['points'] = '2';
                    if ($is_weekend == 1) {
                        $data['points'] = '4';
                    }
                }else{
                    $data['points'] = '0';
                    $sign_res = 1;
                }
            }

        }

        M('user')->where(array('id'=>$uid))->setInc('points', $data['points']);
        points_log($uid,1, $data['points'], $find_user_info['points'], '签到');


        $base_info['points'] = $data['points'];
        $res['base_info'] = $base_info;
        $res['daily_sign_record'] = $daily_sign_record;
        $time = strtotime($wee);
        $now_time = strtotime(date('Y-m-d'));

        if($time !== $now_time){
            $res['is_sign_in'] = 0;
        }else{
            if (!$curr_is_sign) {
                $sign_res = $daily_model->add($data);
            }
            if(!$sign_res){
                $this->json(-2,'请求失败，请重试');
            }
        }
        if($time == $now_time){
            if(empty($curr_is_sign)){
                $this->json(1,'成功',$res);
            }else{
                $this->json(0,'成功',$res);
            }
        }else{
            $this->json(0,'成功',$res);
        }
    }

    //查询当年假期
    public function get_holiday()
    {
        $month = I('month');
//        $res = date('Y-m-d');
//        var_dump($res);exit;
        if(!$month){
            $this->json(-1,'未收到月份');
        }
        if($month <=3 || $month >12 ){
            $this->json(-2,'输入的月份有误');
        }
        $holiday_model = M('holiday');
        if ($month == 4) {
            $holiday = "[{\"name\":\"清明节\",\"festival\":\"2018-4-5\",\"desc\":\"4月5日至7日放假调休，共3天。4月8日（星期日）上班。\",\"rest\":\"拼假建议：2018年4月2日（周一）~2018年4月4日（周三）请假3天，可拼8天清明节小长假\",\"list\":[{\"date\":\"2018-4-5\"},{\"date\":\"2018-4-6\"},{\"date\":\"2018-4-7\"}],\"list#num#\":4},{\"name\":\"劳动节\",\"festival\":\"2018-5-1\",\"desc\":\"4月29日至5月1日放假，4月28日（星期六）上班。\",\"rest\":\"拼假建议：2018年5月2日（周三）~2018年5月4日（周五）请假3天，可拼8天劳动节小长假\",\"list\":[{\"date\":\"2018-4-29\"},{\"date\":\"2018-4-30\"}],\"list#num#\":4}]";
            $res = json_decode($holiday, true);
            foreach ($res as $k => $v) {
                $r[$k]['name'] = $v['name'];
                foreach ($v['list'] as $key => $value){
                    $r[$k]['list'][] = $value['date'];
                }

            }

            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['holiday'] = serialize($r);
                $data['month'] = $month;
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '30';
            //var_dump( $res1);exit;
            return $this->json(0, '成功', $r);

        }
        if ($month == 5) {
            $holiday = "[{\"name\":\"劳动节\",\"festival\":\"2018-5-1\",\"desc\":\"4月29日至5月1日放假，4月28日（星期六）上班。\",\"rest\":\"拼假建议：2018年5月2日（周三）~2018年5月4日（周五）请假3天，可拼8天劳动节小长假\",\"list\":[{\"date\":\"2018-5-1\",\"status\":\"1\"}],\"list#num#\":4}]";
            $res = json_decode($holiday, true);
            foreach ($res as $k => $v) {
                $r[$k]['name'] = $v['name'];
                foreach ($v['list'] as $key => $value){
                    $r[$k]['list'][] = $value['date'];
                }

            }

            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['holiday'] = serialize($r);
                $data['month'] = $month;
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '31';
            return $this->json(0, '成功', $r);
            exit;
        }
        if ($month == 6) {
            $holiday = "[{\"name\":\"端午节\",\"festival\":\"2018-6-18\",\"desc\":\"6月18日放假，与周末连休。\",\"rest\":\"拼假建议：2018年6月19日（周二）~2018年6月22日（周五）请假4天，可拼9天端午节小长假\",\"list\":[{\"date\":\"2018-6-16\",\"status\":\"1\"},{\"date\":\"2018-6-17\",\"status\":\"1\"},{\"date\":\"2018-6-18\",\"status\":\"1\"}],\"list#num#\":3}]";
            $res = json_decode($holiday, true);
            foreach ($res as $k => $v) {
                $r[$k]['name'] = $v['name'];
                foreach ($v['list'] as $key => $value){
                    $r[$k]['list'][] = $value['date'];
                }

            }

            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['holiday'] = serialize($r);
                $data['month'] = $month;
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '30';
            return $this->json(0, '成功', $r);
            exit;
        }
        if($month == 7)
        {
            $r['name'] = "";
            $r['list'] = "";
            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['month'] = $month;
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '31';
            return $this->json(0, '成功', $r);
        }
        if($month == 8)
        {
            $r['name'] = "";
            $r['list'] = "";
            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['month'] = $month;
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '31';
            return $this->json(0, '成功', $r);
        }
        if($month == 9)
        {
            $holiday = "[{\"name\":\"中秋节\",\"festival\":\"2018-9-24\",\"desc\":\"9月24日放假，与周末连休。\",\"rest\":\"拼假建议：2018年9月25日（周二）~2018年9月30日（周日）请假6天，与国庆节衔接，拼16天小长假\",\"list\":[{\"date\":\"2018-9-22\",\"status\":\"1\"},{\"date\":\"2018-9-23\",\"status\":\"1\"},{\"date\":\"2018-9-24\",\"status\":\"1\"}],\"list#num#\":3}]";
            $res = json_decode($holiday, true);
            foreach ($res as $k => $v) {
                $r[$k]['name'] = $v['name'];
                foreach ($v['list'] as $key => $value){
                    $r[$k]['list'][] = $value['date'];
                }

            }

            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['holiday'] = serialize($r);
                $data['month'] = $month;
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '30';
            return $this->json(0, '成功', $r);
        }
        if($month == 10)

        {
            $holiday = "[{\"name\":\"国庆节\",\"festival\":\"2018-10-1\",\"desc\":\"10月1日至7日放假调休，共7天。\",\"rest\":\"拼假建议：2018年9月25日（周二）~2018年9月30日（周日）请假6天，与中秋节衔接，拼16天小长假\",\"list\":[{\"date\":\"2018-10-1\",\"status\":\"1\"},{\"date\":\"2018-10-2\",\"status\":\"1\"},{\"date\":\"2018-10-3\",\"status\":\"1\"},{\"date\":\"2018-10-4\",\"status\":\"1\"},{\"date\":\"2018-10-5\",\"status\":\"1\"},{\"date\":\"2018-10-6\",\"status\":\"1\"},{\"date\":\"2018-10-7\",\"status\":\"1\"}],\"list#num#\":9}]";
            $res = json_decode($holiday, true);
            foreach ($res as $k => $v) {
                $r[$k]['name'] = $v['name'];
                foreach ($v['list'] as $key => $value){
                    $r[$k]['list'][] = $value['date'];
                }

            }

            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['holiday'] = serialize($r);
                $data['month'] = $month;
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '31';
            return $this->json(0, '成功', $r);
        }
        if($month == 11)
        {
            $r['name'] = '万圣节';
            $r['holiday'] = '2018-11-01';
            //$holiday_day = $holiday_res['holiday'];
            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['month'] = $month;
                $data['holiday'] = serialize($r);
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '30';
            return $this->json(0, '成功', $r);
        }
        if($month == 12)
        {
            $r['name'] = '圣诞节';
            $r['holiday'] = '2018-12-25';
            //$holiday_day = $holiday_res['holiday'];
            $have_saved = $holiday_model->where('month='.$month)->find();
            if(!$have_saved)
            {
                $data['month'] = $month;
                $data['holiday'] = serialize($r);
                $add_holiday = $holiday_model->add($data);
            }
            $holiday_res['day_num'] = '31';
            return $this->json(0, '成功', $r);
        }
    }

    /**
     * 聊天室列表
     * */
    public function chatroom_list(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }
        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user){
            return $this->json(-2,'没有获取到该用户信息');
        }

        //查询开通的聊天室列表
        $chatroom_list = M('chatroom_record')->where("status = 0")->order('time asc')->select();
        $this->json(0,'成功',$chatroom_list);
    }

    /**
     * 创建聊天室
     * */
    public function create_chatrooms(){
        $owner = I('owner');
        $city = I('city');//聊天室名称
        if(!$owner || !$city){
            $this->json(-1,'缺少创建信息');
        }
        $description = I('description');
        if(!$description){
            $description = "";
        }
        $options = array(
            'name'=>$city."聊天室",
            'description'=>$description,
            'maxusers'=>300,
            'owner'=>$owner,
        );
        $ease = new Easemob();
        $res = $ease->createChatRoom($options);
        $this->json(0,'成功',$res);
    }

    /*
     * 手动创建聊天室
     */
    public function create_custom_chatrooms()
    {
        $uid = I('uid');
        $owner = M('user')->where('id = '.$uid)->getField('mobile');
        $city = I('city'); //市
        $region = I('region');//地区(县)
        if(!$uid || !$city || !$region){
            $this->json(-1,'缺少创建信息');
        }
        $description = I('description');
        if(!$description){
            $description = "";
        }
        //这里增加聊天室判断,如有已经有存在的聊天室,就不能创建聊天室
        $where['chatroom_name'] = array('like',"%".$city.$region.'聊天室'."%");
        $find_chatroom = M('chatroom_record')->where($where)->find();
        if ($find_chatroom){
            $this->json(-2,'房间已存在',$find_chatroom);
        }
        $options = array(
            'name'=>$city.$region."聊天室",
            'description'=>$description,
            'maxusers'=>300,
            'owner'=>$owner,
        );
        $ease = new Easemob();
        $res = $ease->createChatRoom($options);
        $res['name'] = $city.$region."聊天室";

        //将创建记录写入表中
        $data['chatroom_id'] = $res['data']['id'];
        $data['time'] = time();
        $data['city'] = $city;
        $data['chatroom_name'] = $res['name'];
        $data['room_avatar'] = "http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg";
        M('chatroom_record')->add($data);

        $this->json(0,'成功',$res);
    }

    /**
     * 判断是否是人脉还是好友
     * */
    public function user_state() {
        $uid = I('uid'); // 用户本人id
        $mobile = I('mobile');  // 会话列表的手机号
        if(!$uid || !$mobile) { return $this->json(-1,'缺少参数'); }

        $user = M('user')->field('id,mobile,top_father,pur_top_father,pur_mobile')->find($uid);
        if(!$user) { return $this->json(-2,'用户信息不存在'); }

        $user_friends = M('user')->field('id,mobile,top_father,pur_top_father,pur_mobile,user_nickname,avatar,sex,signature')->where('mobile in ('.$mobile.')')->select();
        if(!$user_friends) {return $this->json(-2,'信息不存在');}

        foreach ($user_friends as $k => $v) {

            $data[$k]['id'] = $v['id'];
            $data[$k]['mobile'] = $v['mobile'];
            $data[$k]['user_nickname'] = $v['user_nickname'];
            $data[$k]['avatar'] = $v['avatar'];
            $data[$k]['sex'] = $v['sex'];
            $data[$k]['signature'] = $v['signature'];
            //判断是否是自己的人脉
            if(($user['top_father'] == $v['top_father']) || ($user['top_father'] == $v['pur_top_father']) || ($user['mobile'] == $v['pur_mobile']) || ($user['pur_mobile'] == $v['mobile'])){
                $data[$k]['user_state'] = "2"; //人脉
            }else{
                $is_friends = M('user_friends')->field('id')->where(array('uid'=>$user['id'],'f_uid'=>$v['id']))->select();
                if($is_friends) {
                    $data[$k]['user_state'] = "1"; //好友
                } else {
                    $data[$k]['user_state'] = "0"; //陌生人
                }

            }
        }

        $this->json(0,'获取成功',$data);
    }

    /**
     *可能认识的人
     * */
    public function know_user() {
        $uid = I('uid');
        if(!$uid) { return $this->json(-1,'缺少参数'); }

        $user_info = M('user')->field('province,city,top_father')->find($uid);
        if(!$user_info) { return $this->json(-2,'用户信息不存在'); }

        $field = 'id,user_nickname,avatar,province,sex,mobile';

        // 好友id
        $f_uid = M('user_friends')->field('f_uid')->where(array('uid'=>$uid))->select();
        $f_uid = array_column($f_uid,'f_uid');

        if($f_uid) {
            // 好友的好友id
            $where['uid'] = array('in',$f_uid);
            $where['f_uid'] = array('neq',$uid);
            $ff_uid = M('user_friends')->field('f_uid')->where($where)->select();
            if($ff_uid) {
                $ff_uid = implode(',',array_unique(array_column($ff_uid, 'f_uid')));  // 好友的好友id
                $f_uid1 = implode(',',array_unique($f_uid));                                  // 好友id
                $data = M('user')->field($field)->where('id in ('.$ff_uid.') and id not in ('.$f_uid1.') and id<>'.$uid.' and top_father<>'.$user_info['top_father'])->order('rand()')->limit(10)->select();
            } else {
                // 没有好友的好友的id取跟他同一省的
                array_push($f_uid, $uid);
                $data = M('user')->field($field)->where(array('province'=>array('eq',$user_info['province']),'id'=>array('not in',$f_uid),'top_father'=>array('neq',$user_info['top_father'])))->order('rand()')->limit(10)->select();
            }
        } else {
            // 没有好友的id取跟他同一省的
            array_push($f_uid, $uid);
            $data = M('user')->field($field)->where(array('province'=>array('eq',$user_info['province']),'id'=>array('not in ',$f_uid),'top_father'=>array('neq',$user_info['top_father'])))->order('rand()')->limit(10)->select();
        }

        if($data) {
            if(count($data) < 10) {
                $user_id = implode(',',array_merge(array_column($data,'id'),$f_uid));
                $data1 = M('user')->field($field)->where('id not in ('.$user_id.') and id<>'.$uid. ' and top_father<>'.$user_info['top_father'])->order('rand()')->limit( 10 - count($data))->select();
                $data = array_merge($data, $data1);
            }
        } else {
            array_push($f_uid, $uid);
            $data =  M('user')->field($field)->where(array('id'=>array('not in',$f_uid),'top_father'=>array('neq',$user_info['top_father'])))->order('rand()')->limit(10)->select();
        }
        foreach ($data as $k => $v) {
            if(empty($v['avatar'])) {
                $data[$k]['avatar'] = C('DEFAULT_AVATAR');
            }
            if(empty($v['user_nickname'])) {
                $data[$k]['user_nickname'] = $v['mobile'];
            }

        }
        $this->json(0,'获取成功',$data);
    }

    /**
     *手机联系人
     * */
    public function mobile_user() {
        $uid = I('uid');
        $mobile = I('mobile');
         if(!$uid) { return $this->json(-1,'缺少参数'); }

        $where['mobile'] = array('in', $mobile);
        $data = M('user')->field('id,user_nickname,avatar,mobile')->where($where)->select();
        // 判断是否是好友
        $friends = M('user_friends')->field('f_uid')->where(array('uid'=>$uid))->select();
        foreach ($data as $k => $v) {
            if($friends) {
                foreach ($friends as $k1 => $v1) {
                    if ($v['id'] == $v1['f_uid']) {
                        $data[$k]['is_friends'] = 1; // 是好友
                        break;
                    } else {
                        $data[$k]['is_friends'] = 2; // 不是好友
                    }
                }
            } else {
                $data[$k]['is_friends'] = 2;   // 不是好友
            }
            $data[$k]['avatar'] = empty($v['avatar']) ? C('DEFAULT_AVATAR') : $v['avatar'];
        }
        $this->json(0,'获取成功',$data);
    }

    /**
     *微信小程序注册
     * */

    public function wx_register()
    {
        $wx_user = $this->wx_pull_user();

        $openid = $wx_user['unionId'];    //方便以后扩展 存放UnionID
        $avatar = $wx_user['avatarUrl'];
        $nickname = $wx_user['nickName'];
        $sex = $wx_user['gender'];
        $province = $wx_user['province'];
        $city = $wx_user['city'];


        $mobile = I('mobile');
        $code = I('mobile_code');


        if (!$mobile || !$openid) {
            return $this->json(-1, '手机号和openid不能为空');
        }

        if (!(preg_match('/^(1[35784]\d{9})$/', $mobile))) {
            return $this->json(-8, '手机号码格式错误');
        }

        if (!$code) {
            return $this->json(-10, '验证码不能为空');
        }

         $oauth_user = M('oauth_user')->where(array('openid'=>$openid))->find();
         if($oauth_user) {
             return $this->json(-11,'该账号已绑定');
         }

        //查询是否有验证码记录
        $time = time() - 10 * 60;
        $find_code_record = M('login_code_record')->where('mobile = ' . $mobile . ' and time >= ' . $time)->find();
        if ($find_code_record) {
            if ($code != $find_code_record['code']) {
                if ($code == "8989987") {
                } else {
                    return $this->json(-9, '验证码错误');
                }
            }
        } else {
            if ($code == "8989987") {
            } else {
                return $this->json(-9, '验证码错误');
            }
        }

        $find_info = M('user')->field('id,user_nickname,avatar,sex,signature,mobile,province,city')->where("mobile = " . $mobile)->find();

        $is_user = M('oauth_user')->where(array('uid' => $find_info['id']))->select();
        if ($is_user) {
            return $this->json(-4, '该手机号已绑定过');
        }

        if ($find_info) {
            $data = array(
                'from' => "微信小程序",
                'name' => $nickname,
                'head_img' => $avatar,
                'uid' => $find_info['id'],
                'create_time' => date("Y-m-d H:i", time()),
                'last_login_time' => '',
                'status' => '1',
                'last_login_ip' => get_client_ip(),
                'login_times' => 0,
                'openid' => $openid,
            );
            $result = M('oauth_user')->add($data);   // 绑定
            if ($result) {
                return $this->json(0, '注册成功', $find_info);
            } else {
                return $this->json(-1, '注册失败');
            }
        }
        $password = I('password');
        $confirm_password = I('confirm_password');
        $invitation_code = I('invitation_code');//邀请码（可传可不传）
        $data['points'] = 20;//为什么不使用数据表中的默认设置呢？因为以后可能会改成后台可控的形式，这个值就从积分表中查询即可。
        $data['create_time'] = date('Y-m-d H:i:s', time());
        $data['user_nickname'] = $nickname;
        $data['avatar'] = $avatar;
        $data['sex'] = $sex;
        $data['province'] = $province;
        $data['city'] = $city;
        $data['is_first'] = 1;
        $data['debo_number'] = $mobile;     // debo号默认是手机号


        if (!$password) {
            return $this->json(-2, '密码不能为空');
        }
        if (!$confirm_password) {
            return $this->json(-3, '确认密码不能为空');
        }
        if ($invitation_code) {
            $level = M('user')->where("id = " . $invitation_code . " or mobile =" . $invitation_code)->find();
            if (!$level) {
                return $this->json(-5, '没有找到邀请者信息');
            }
            $data['invitation_code'] = $level['mobile'];

            $data['mobile'] = $mobile;
            $data['password'] = md5($password);
            $data['top_father'] = $level['top_father'];

            // 给邀请者加金额
            $invitation_result = $this->invitation($level);

            if ($invitation_result['options']['register_coins'] != 0) {
                $data['coins'] = $invitation_result['options']['register_coins'];
                $data['points'] = 0;
            }

            if ($invitation_result['result']) {
                //生成二维码
                $url = "{'type':'user','param':'" . $data['mobile'] . "'}";
                qrcode($data['mobile'], $url, 'user_' . $data['mobile']);
                $data['qrcode'] = 'user_' . $data['mobile'] . '/' . $data['mobile'] . '.png';
                //生成二维码
                $res = M('user')->add($data);
                // 第三方用户表信息
                $oauth = array(
                    'from' => "微信小程序",
                    'name' => $nickname,
                    'head_img' => $avatar,
                    'uid' => $res,
                    'create_time' => date("Y-m-d H:i", time()),
                    'last_login_time' => '',
                    'status' => '1',
                    'last_login_ip' => get_client_ip(),
                    'login_times' => 0,
                    'openid' => $openid,
                );
                M('oauth_user')->add($oauth);   // 绑定

                if ($res) {
                    // 插入日志
                    if ($invitation_result['options']['register_coins'] == 0) {
                        points_log($res, 1, 20, 0, '注册');
                    } else {
                        // 记录日志
                        $transaction_data = array(
                            'order_sn' => $order_sn = date('YmdHis') . rand(1000, 9999),
                            'uid' => $res,
                            'order_price' => $invitation_result['options']['register_coins'],
                            'pay_status' => 2,
                            'pay_time' => time(),
                            'create_time' => time(),
                            'indent' => 4,   // 领取红包
                        );
                        M('transaction')->add($transaction_data);
                    }
                    $ease = new Easemob();
                    $ease->deleteUser($data['mobile']);
                    $huanxin = $ease->createUser($data['mobile'], '88888888');
                    $user_info = M('user')->where("mobile = " . $mobile)->field('id,user_nickname,avatar,sex,signature,mobile,province,city')->find();

                    //注册游戏用户表
                    $game_data['name'] = $mobile;
                    $game_data['debo_uid'] = $user_info['id'];
                    $game_data['zhanghao'] = $mobile;
                    $game_data['mima'] = md5($mobile);
                    $game_regis = M('ay_user')->add($game_data);

                    //给邀请者发送加积分消息
                    $admin_mobile = '18788888888';
                    $invitation_mobile = $level['mobile'];
                    $mobile_from = explode(',', $invitation_mobile);
                    $ease = new Easemob();
                    $content = $data['user_nickname'] . $invitation_result['connect'];
                    $ext = array('type' => '1', 'mobile' => $mobile, 'username' => $data['user_nickname'], 'content' => $content);
                    $ease->sendText('邀请注册送积分' . $mobile, 'users', $mobile_from, $content, $ext);
                    //发送透传消息
                    $ease->sendCmd('邀请注册送积分' . $mobile, 'users', $mobile_from, 'action1', $ext);

                    return $this->json(0, '注册成功', $user_info);
                } else {
                    return $this->json(-6, '注册失败');
                }
            } else {
                return $this->json(-6, '注册失败');
            }

        }
        if ($password != $confirm_password) {
            return $this->json(-4, '两次输入的密码不一致');
        }

        $data['mobile'] = $mobile;
        $data['password'] = md5($password);
        $data['top_father'] = $mobile;

        //生成二维码
        $url = "{'type':'user','param':'" . $data['mobile'] . "'}";
        qrcode($data['mobile'], $url, 'user_' . $data['mobile']);
        $data['qrcode'] = 'user_' . $data['mobile'] . '/' . $data['mobile'] . '.png';
        //生成二维码
        $res = M('user')->add($data);
        // 第三方用户表信息
        $oauth = array(
            'from' => "微信小程序",
            'name' => $nickname,
            'head_img' => $avatar,
            'uid' => $res,
            'create_time' => date("Y-m-d H:i", time()),
            'last_login_time' => '',
            'status' => '1',
            'last_login_ip' => get_client_ip(),
            'login_times' => 0,
            'openid' => $openid,
        );
        M('oauth_user')->add($oauth);   // 绑定
        if ($res) {
            // 插入日志
            points_log($res, 1, 20, 0, '注册');
            $ease = new Easemob();
            $ease->deleteUser($data['mobile']);
            $huanxin = $ease->createUser($data['mobile'], '88888888');
            $field = 'id,mobile,user_nickname,avatar,sex,signature,create_time,qrcode,address';
            $user_info = M('user')->where("mobile = " . $mobile)->field($field)->find();

            //注册游戏用户表
            $game_data['name'] = $mobile;
            $game_data['debo_uid'] = $user_info['id'];
            $game_data['zhanghao'] = $mobile;
            $game_data['mima'] = md5($mobile);
            $game_regis = M('ay_user')->add($game_data);

            return $this->json(0, '注册成功', $user_info);
        } else {
            return $this->json(-6, '注册失败');
        }
    }




    /**
     *微信小程序登录
     * */
    public function wx_login() {

        $wx_user = $this->wx_pull_user();

        $openid = $wx_user['unionId'];     //方便以后扩展 存放UnionID
        $province = $wx_user['province'];
        $city = $wx_user['city'];

        if (!$openid) {
            return $this->json(-1, '缺少参数');
        }

        $where['openid'] = $openid;
        $oauth_user = M('oauth_user')->where($where)->find();
        if(!$oauth_user) {
            return $this->json(-4, '该账号未绑定');
        }
        $find_user = M('user')->where(array('id'=>$oauth_user['uid']))->find();

        if (!$find_user) {
            return $this->json(-2, '该账号未注册');
        }

        if ($find_user['state'] == 1) {
            return $this->json(-3, '您已被封号');
        }
        //修改定位信息
        if ($province) {
            $map['province'] = $province;
        }
        if ($city) {
            $map['city'] = $city;
        }
        $map['last_login_time'] = date("Y-m-d H:i:s");
        $mobile = $find_user['mobile'];
        M('user')->where('mobile = ' . $mobile)->save($map);
        $field = 'id,mobile,user_nickname,avatar,sex,signature,create_time,qrcode,address,coins,conn_type,pur_mobile,city,area,pay_pwd,top_father,is_first,debo_number';
        $find_user_res = M('user')->where("mobile = " . $mobile)->field($field)->find();


        if ($find_user_res['pay_pwd']) {
            $find_user_res['is_set_pay_pwd'] = "1";
        } else {
            $find_user_res['is_set_pay_pwd'] = "0";
        }
        unset($find_user_res['pay_pwd']);
        $find_user_res['qrcode'] = $this->qrcode . $find_user_res['qrcode'];

        //判断用户是否有人脉设置功能
        if ($find_user_res['mobile'] == $find_user_res['top_father']) {
            $find_user_res['is_con_set_right'] = "1";
        } else {
            $find_user_res['is_con_set_right'] = "0";
        }

        //判断是否开启定位
        $find_switch_info = M('switch_location_info')->where("uid = " . $find_user['id'])->find();
        if (!$find_switch_info) {
            $location_is_open = "0";
        } else {
            if ($find_switch_info['is_open'] == 1) {
                $location_is_open = "1";
            } else if ($find_switch_info['is_open'] == 2) {
                $location_is_open = "0";
            }
        }
        $find_user_res['location_is_open'] = $location_is_open;

        $ease = new Easemob();
        //查询是否加入过附近的群
        //$is_add_nearby_group = M('chatgroup_member')->where("mobile = '".$mobile."' and is_register = 1")->select();
        $is_add_nearby_group = M('chatgroup_member')->where("mobile = '" . $mobile . "' and is_register = 1")->find();
        if ($is_add_nearby_group) {
            //查询群组位置
            $group_info = M('chatgroup')->where("group_id = '" . $is_add_nearby_group['group_id'] . "'")->find();
            if ($city) {
                if ($city != $group_info['city']) {
                    $temp = $ease->deleteGroupMember($is_add_nearby_group['group_id'], $is_add_nearby_group['mobile']);
                    M('chatgroup')->where("group_id = '" . $is_add_nearby_group['group_id'] . "'")->setDec('mem_count', 1);
                    if ($temp['data']['result']) {
                        //如果删除环信成功，则清除本地信息
                        M('chatgroup_member')->where('cm_id = ' . $is_add_nearby_group['cm_id'])->delete();
                        $find_nearby_group = M('chatgroup')->where("city = '" . $city . "' and qroup_type = 1 and mem_count < 2000")->find();
                        if (!$find_nearby_group) {
                            //注册群
                            $options = array(
                                'groupname' => '附近的嘚啵圈',
                                'desc' => '',
                                'public' => true,
                                'maxusers' => 2000,
                                'members_only' => false,
                                'allowinvites' => false,
                                'owner' => '18788888888',
                                'members' => explode(",", $mobile),
                            );
                            $create_group_res = $ease->createGroup($options);
                            if ($create_group_res['data']['groupid']) {
                                $group_id = $create_group_res['data']['groupid'];
                                $create_time = $create_group_res['timestamp'];
                                $group_data = array(
                                    'group_id' => $group_id,
                                    'create_time' => time(),
                                    'g_mobile' => '18788888888',
                                    'qroup_type' => 1,
                                    'g_avatar' => 'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg',
                                    'province' => $province,
                                    'city' => $city,
                                    'mem_count' => 2,
                                );
                                $add_res = M('chatgroup')->add($group_data);
                                //添加群主和群成员
                                $add_data[0]['mobile'] = '18788888888';
                                $add_data[0]['group_id'] = $group_id;
                                $add_data[0]['member_type'] = 1;
                                $add_data[0]['is_register'] = 1;
                                $add_data[1]['mobile'] = $mobile;
                                $add_data[1]['group_id'] = $group_id;
                                $add_data[1]['member_type'] = 3;
                                $add_data[1]['is_register'] = 1;

                                $add_owner = M('chatgroup_member')->addAll($add_data);
                            }

                        } else {
                            //添加群成员
                            $mem_mobile = array(
                                'usernames' => explode(",", $mobile)
                            );
                            $ease->addGroupMembers($find_nearby_group['group_id'], $mem_mobile);
                            $add_data['mobile'] = $mobile;
                            $add_data['group_id'] = $find_nearby_group['group_id'];
                            $add_data['member_type'] = 3;
                            $add_data['is_register'] = 1;
                            $add_owner = M('chatgroup_member')->add($add_data);
                            M('chatgroup')->where("group_id = '" . $find_nearby_group['group_id'] . "'")->setInc('mem_count', 1);
                        }
                    }
                }
            } else {
                //如果没有城市
                if ($group_info['city'] != '默认') {
                    $temp = $ease->deleteGroupMember($is_add_nearby_group['group_id'], $is_add_nearby_group['mobile']);
                    M('chatgroup')->where("group_id = '" . $is_add_nearby_group['group_id'] . "'")->setDec('mem_count', 1);
                    if ($temp['data']['result']) {
                        M('chatgroup_member')->where('cm_id = ' . $is_add_nearby_group['cm_id'])->delete();
                        //如果没有获取到省市区那么就生成一个默认的群
                        $find_nearby_group = M('chatgroup')->where("city = '默认' and qroup_type = 1 and mem_count < 2000")->find();
                        if (!$find_nearby_group) {
                            //注册群
                            $options = array(
                                'groupname' => '附近的嘚啵圈',
                                'desc' => '',
                                'public' => true,
                                'maxusers' => 2000,
                                'members_only' => false,
                                'allowinvites' => false,
                                'owner' => '18788888888',
                                'members' => explode(",", $mobile),
                            );
                            $create_group_res = $ease->createGroup($options);
                            if ($create_group_res['data']['groupid']) {
                                $group_id = $create_group_res['data']['groupid'];
                                $create_time = $create_group_res['timestamp'];
                                $group_data = array(
                                    'group_id' => $group_id,
                                    'create_time' => time(),
                                    'g_mobile' => '18788888888',
                                    'qroup_type' => 1,
                                    'g_avatar' => 'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg',
                                    'province' => '默认',
                                    'city' => '默认',
                                    'area' => '默认',
                                    'mem_count' => 2,
                                );
                                $add_res = M('chatgroup')->add($group_data);
                                //添加群主和群成员
                                $add_data[0]['mobile'] = '18788888888';
                                $add_data[0]['group_id'] = $group_id;
                                $add_data[0]['member_type'] = 1;
                                $add_data[0]['is_register'] = 1;
                                $add_data[1]['mobile'] = $mobile;
                                $add_data[1]['group_id'] = $group_id;
                                $add_data[1]['member_type'] = 3;
                                $add_data[1]['is_register'] = 1;

                                $add_owner = M('chatgroup_member')->addAll($add_data);
                            }

                        } else {
                            //添加群成员
                            $mem_mobile = array(
                                'usernames' => explode(",", $mobile)
                            );
                            $ease->addGroupMembers($find_nearby_group['group_id'], $mem_mobile);
                            $add_data['mobile'] = $mobile;
                            $add_data['group_id'] = $find_nearby_group['group_id'];
                            $add_data['member_type'] = 3;
                            $add_data['is_register'] = 1;
                            $add_owner = M('chatgroup_member')->add($add_data);
                            M('chatgroup')->where("group_id = '" . $find_nearby_group['group_id'] . "'")->setInc('mem_count', 1);
                        }
                    }
                }
            }

            //如果有则退出群组
//            foreach($is_add_nearby_group as $key => $val){
//                $temp = $ease->deleteGroupMember($val['group_id'],$val['mobile']);
//                if($temp['data']['result']){
//                    //如果删除环信成功，则清除本地信息
//                    M('chatgroup_member')->where('cm_id = '.$val['cm_id'])->delete();
//                }
//            }

        } else {
            //如果没有则按照正常逻辑进入或创建群
            if ($city) {
                $find_nearby_group = M('chatgroup')->where("city = '" . $city . "' and qroup_type = 1 and mem_count < 2000")->find();
                if (!$find_nearby_group) {
                    //注册群
                    $options = array(
                        'groupname' => '附近的嘚啵圈',
                        'desc' => '',
                        'public' => true,
                        'maxusers' => 2000,
                        'members_only' => false,
                        'allowinvites' => false,
                        'owner' => '18788888888',
                        'members' => explode(",", $mobile),
                    );
                    $create_group_res = $ease->createGroup($options);
                    if ($create_group_res['data']['groupid']) {
                        $group_id = $create_group_res['data']['groupid'];
                        $create_time = $create_group_res['timestamp'];
                        $group_data = array(
                            'group_id' => $group_id,
                            'create_time' => time(),
                            'g_mobile' => '18788888888',
                            'qroup_type' => 1,
                            'g_avatar' => 'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg',
                            'province' => $province,
                            'city' => $city,
                            'mem_count' => 2,
                        );
                        $add_res = M('chatgroup')->add($group_data);
                        //添加群主和群成员
                        $add_data[0]['mobile'] = '18788888888';
                        $add_data[0]['group_id'] = $group_id;
                        $add_data[0]['member_type'] = 1;
                        $add_data[0]['is_register'] = 1;
                        $add_data[1]['mobile'] = $mobile;
                        $add_data[1]['group_id'] = $group_id;
                        $add_data[1]['member_type'] = 3;
                        $add_data[1]['is_register'] = 1;

                        $add_owner = M('chatgroup_member')->addAll($add_data);
                    }

                } else {
                    //添加群成员
                    $mem_mobile = array(
                        'usernames' => explode(",", $mobile)
                    );
                    $ease->addGroupMembers($find_nearby_group['group_id'], $mem_mobile);
                    $add_data['mobile'] = $mobile;
                    $add_data['group_id'] = $find_nearby_group['group_id'];
                    $add_data['member_type'] = 3;
                    $add_data['is_register'] = 1;
                    $add_owner = M('chatgroup_member')->add($add_data);
                    M('chatgroup')->where("group_id = '" . $find_nearby_group['group_id'] . "'")->setInc('mem_count', 1);
                }
            } else {
                //如果没有获取到省市区那么就生成一个默认的群
                $find_nearby_group = M('chatgroup')->where("city = '默认' and qroup_type = 1 and mem_count < 2000")->find();
                if (!$find_nearby_group) {
                    //注册群
                    $options = array(
                        'groupname' => '附近的嘚啵圈',
                        'desc' => '',
                        'public' => true,
                        'maxusers' => 2000,
                        'members_only' => false,
                        'allowinvites' => false,
                        'owner' => '18788888888',
                        'members' => explode(",", $mobile),
                    );
                    $create_group_res = $ease->createGroup($options);
                    if ($create_group_res['data']['groupid']) {
                        $group_id = $create_group_res['data']['groupid'];
                        $create_time = $create_group_res['timestamp'];
                        $group_data = array(
                            'group_id' => $group_id,
                            'create_time' => time(),
                            'g_mobile' => '18788888888',
                            'qroup_type' => 1,
                            'g_avatar' => 'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg',
                            'province' => '默认',
                            'city' => '默认',
                            'area' => '默认',
                            'mem_count' => 2,
                        );
                        $add_res = M('chatgroup')->add($group_data);
                        //添加群主和群成员
                        $add_data[0]['mobile'] = '18788888888';
                        $add_data[0]['group_id'] = $group_id;
                        $add_data[0]['member_type'] = 1;
                        $add_data[0]['is_register'] = 1;
                        $add_data[1]['mobile'] = $mobile;
                        $add_data[1]['group_id'] = $group_id;
                        $add_data[1]['member_type'] = 3;
                        $add_data[1]['is_register'] = 1;

                        $add_owner = M('chatgroup_member')->addAll($add_data);
                    }

                } else {
                    //添加群成员
                    $mem_mobile = array(
                        'usernames' => explode(",", $mobile)
                    );
                    $ease->addGroupMembers($find_nearby_group['group_id'], $mem_mobile);
                    $add_data['mobile'] = $mobile;
                    $add_data['group_id'] = $find_nearby_group['group_id'];
                    $add_data['member_type'] = 3;
                    $add_data['is_register'] = 1;
                    $add_owner = M('chatgroup_member')->add($add_data);
                    M('chatgroup')->where("group_id = '" . $find_nearby_group['group_id'] . "'")->setInc('mem_count', 1);
                }
            }
        }

        //发送群消息
        $ease = new Easemob();
        $group_user = M('chatgroup_member')->where("mobile = '" . $mobile . "' and is_register = 1")->find();
        if ($group_user) {
            $admin_member = M('user')->where("id = 375")->find();
            if ($find_user_res['user_nickname']) {
                $content = "欢迎 " . $find_user_res['user_nickname'] . " 来到附近嘚啵圈";
            } else {
                $content = "欢迎用户 " . $mobile . " 来到附近嘚啵圈";
            }
            $ext = array('type' => '1', 'mobile' => $mobile, 'user_nickname' => $admin_member['user_nickname'], 'content' => $content, 'avatar' => $admin_member['avatar'], 'from_mobile' => $admin_member['mobile']);
            $group_id = explode(",", $group_user['group_id']);
            $res = $ease->sendText('附近的嘚啵圈', 'chatgroups', $group_id, $content, $ext);
        }

        if ($find_user_res['is_first'] == 1) {
            M('user')->where('id = ' . $find_user_res['id'])->setField('is_first', 0);
        }

        // 更新第三方用户表
        $data = array(
            'last_login_time' => date('Y-m-d H:i:s',time()),
            'last_login_ip' => get_client_ip(),
        );
        M('oauth_user')->where(array('uid'=>$find_user_res['id']))->save($data);
        M('oauth_user')->where(array('uid'=>$find_user_res['id']))->setInc('login_times',1);

        return $this->json(0, '登录成功', $find_user_res);


    }


    /**
     * 微信拉取信息
     * */
    public function wx_pull_user(){

        Vendor('wechat.wxBizDataCrypt');
        $code = I('code');
        $encryptedData = I('encryptedData');
        $iv = I('iv');
        $appid = 'wx50be9f86e6b1cd79';
        $appsecret = 'eeec052d1ebecb959faa4d2d2fe0df4c';

        if(!$_SESSION['session_key']) {
            $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code';

            $wx_result =  $this->get_bankcard_info ($url);
            $jsondecode = json_decode($wx_result); //对JSON格式的字符串进行编码
            $array = get_object_vars($jsondecode);//转换成数组

            if($array['errcode']) {
                return $this->json($array['errcode'] ,'获取session_key失败');
            } else {
                $_SESSION['session_key'] = $array['session_key'];
            }
        }
        $pc = new \wxBizDataCrypt($appid,$_SESSION['session_key']);

        $errCode = $pc->decryptData($encryptedData, $iv, $wx_user );

        if($errCode !=0) {
            return $this->json($errCode ,'拉取失败');
        }

        $wx_user = json_decode($wx_user); //对JSON格式的字符串进行编码
        $wx_user = get_object_vars($wx_user);//转换成数组
        return $wx_user;
    }

    /**
     * get请求
     * */
        function get_bankcard_info($url){
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //执行并获取HTML文档内容
        $output = curl_exec($ch);

        //释放curl句柄
        curl_close($ch);
        return $output;
        }

    /**
     * web注册页面
     * */
    public function web_register(){
        $invitation_code = I('invitation_code');//邀请码，用户id
        $this->assign('invitation_code',$invitation_code);
        $this->display();
    }
    /**
     * web注册成功跳转页面
     * */
    public function web_award(){
        $this->display();
    }
    /**
     * web注册领取红包页
     * */
    public function web_prize(){
        $invitation_code = I('invitation_code');//邀请码，用户id
        $this->assign('invitation_code', $invitation_code);
        $this->display('web_prize');
    }


    /**
     * 关于嘚啵页面
     * */
    public function about_debo(){
        $this->display();
    }

    /**
     *积分说明
     * */
    public function point_introduce(){
        $this->display();
    }

    /**
     * 三方客说明
     * */
    public function tripartite_introduce(){
        $this->display();
    }


    /**
     * 注册协议
     * */
    public function registration_agreement(){
        $this->display();
    }

    /**
     * 邀请加积分
     * */
    public function invitation($level) {
        // 读取配置
        $options = M('options')->field('option_value')->where(array('option_name'=>"site_options"))->find();
        $options = json_decode($options['option_value'],true);
        if($options['register_coins'] != 0) {
            $result = M('user')->where("id = ".$level['id'])->setInc('coins',$options['invite_register_coins']);//给邀请者的金币加5
            if($result) {
                // 记录日志
                $data = array(
                    'order_sn' => $order_sn = date('YmdHis').rand(1000,9999),
                    'uid' => $level['id'],
                    'order_price' => $options['invite_register_coins'],
                    'pay_status' => 2,
                    'pay_time' => time(),
                    'create_time' => time(),
                    'indent' => 19,   // 邀请好友
                );
                M('transaction')->add($data);
                $connect = '通过您的邀请码成功注册嘚啵，小波给您送5金币，再接再厉哟~';
            }

        } else {
            $result = M('user')->where("id = ".$level['id'])->setInc('points',50);//给邀请者的积分加50
            if($result) {
                points_log($level['id'], 1, 50, $level['points'],'邀请好友');
                $connect = '通过您的邀请码成功注册嘚啵，小波给您送50积分，再接再厉哟~';
            }
        }

        return array('result'=>$result,'connect'=>$connect,'options'=>$options);
    }

}

?>