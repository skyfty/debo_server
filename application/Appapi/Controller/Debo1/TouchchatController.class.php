<?php
/**
 * Created by PhpStorm.
 * User: qcwl04
 * Date: 2018/5/22
 * Time: 17:45
 * 碰聊和撞一撞
 */

namespace Appapi\Controller\Debo1;

use Common\Controller\ApibaseController;
use Org\Net\IpLocation;
use Common\Common\Easemob;
use prism_notify;

class TouchchatController extends ApibaseController{


    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 碰聊-选择聊天的人
     */
    public function rand_user()
    {
        $uid = I('uid');
        $time = date('Y-m-d H:i:s');
        //根据uid更新登录时间
        if (!$uid){
            $this->json(-1,'参数不全');
        }
        $res = M('user')->field('id,mobile,sex,user_nickname,avatar')->where('id='.$uid)->find();
        if (!$res){
            $this->json(-2,'用户信息不存在');
        }
        //更新登录时间
        $savedate = M('user')->where('id='.$uid)->setField('last_login_time',$time);

        if (!$savedate){
            $this->json(-3,'刷新过于频繁,请稍后再试');
        }

        $now = strtotime($time);
        $where = array();
        if ($res['sex'] =='1' || $res['sex'] =='男'){
            $where['sex']=2;
        }else if($res['sex'] =='2'||$res['sex']=='女'){
            $where['sex']=1;
        }else{
            $where['sex']=0;
        }
        $where['id'] =array('neq',$uid);
        $user_info = M('user')->where($where)->field('id,mobile,sex,user_nickname,UNIX_TIMESTAMP(last_login_time) as time,avatar')->order('UNIX_TIMESTAMP(last_login_time) desc')->limit(10)->select();
        $select_id = array_rand($user_info,1);
        $account_info = $user_info[$select_id];
        unset($account_info['time']);

        if (!$account_info){
                $this->json(-4,'暂时无人');
        }
        if(empty($account_info['avatar'])) { $account_info['avatar'] = C('DEFAULT_AVATAR'); }
        if(empty($account_info['user_nickname'])) { $account_info['user_nickname'] = $account_info['mobile']; }
        if(empty($res['avatar'])) { $res['avatar'] = C('DEFAULT_AVATAR'); }
        if(empty($res['user_nickname'])) { $res['user_nickname'] = $res['mobile']; }

        $data = array(
            'user' => $res,
            'rand_user' => $account_info
        );
        $this->json(0,'成功',$data);
    }

    /**
     * 碰聊-文字聊天-选择聊天的人
     */
    public function select_chat()
    {
        $sex = I('sex');
        $uid = I('uid');
//        $type = I('type');//聊天类型,1为文字,2为视频
        $time = date('Y-m-d H:i:s');
        //根据uid更新登录时间
        if (!$uid){
            $this->json(-1,'参数不全');
        }
        $res = M('user')->where('id='.$uid)->find();
        if (!$res){
            $this->json(-2,'用户信息不存在');
        }
        //更新登录时间
        $savedate = M('user')->where('id='.$uid)->setField('last_login_time',$time);

        if (!$savedate){
            $this->json(-3,'刷新过于频繁,请稍后再试');
        }

        $now = strtotime($time);
        $where = array();
        if ($sex =='1' || $sex =='男'){
            $where['sex']=1;
        }else if($sex =='2'||$sex=='女'){
            $where['sex']=2;
        }else{
            $where['sex']=0;
        }
        $where['id'] =array('neq',$uid);
        $user_info = M('user')->where($where)->field('id,mobile,sex,user_nickname,UNIX_TIMESTAMP(last_login_time) as time,avatar')->order('UNIX_TIMESTAMP(last_login_time) desc')->limit(10)->select();
        $select_id = array_rand($user_info,1);
        $account_info = $user_info[$select_id];
        unset($account_info['time']);

        if (!$account_info){
//            $this->json(-4,'暂时无人');
            //如果没有信息,返回同性
            if ($where['sex']==1 ||$where['sex']=='男'){
                $account_info = M('user')->where("sex = 2 or sex ='女'")->field('id,mobile,sex,user_nickname,UNIX_TIMESTAMP(last_login_time) as time,avatar')->order('UNIX_TIMESTAMP(last_login_time) desc')->limit(1)->find();
            }elseif ($where['sex']==2 ||$where['sex']=='女'){
                $account_info = M('user')->where("sex = 1 or sex ='男'")->field('id,mobile,sex,user_nickname,UNIX_TIMESTAMP(last_login_time) as time,avatar')->order('UNIX_TIMESTAMP(last_login_time) desc')->limit(1)->find();
            }else{
                $this->json(-4,'暂时无人');
            }
        }
        if(empty($account_info['avatar'])) {
            $account_info['avatar'] = C('DEFAULT_AVATAR');
        }
        $this->json(0,'成功',$account_info);
//        $info1 = $ease->sendText('users-15110165273','users',['15110165273'],'喜欢你',$ext);
    }

    /**
     * 发送文字聊天
     */
    public function startChat(){
        $uid = I('uid');//发起聊天的人
        $oid = I('oid');//被聊的人
        $content = I('post.content');
        //查询每个人的信息
        $uid_info = M('user')->where('id='.$uid)->field('mobile,avatar,user_nickname')->find();
        $oid_info = M('user')->where('id='.$oid)->field('mobile,avatar,user_nickname')->find();

        $ease = new Easemob();

        $find_friend_conn = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$oid)->find();
        if($find_friend_conn){
            $ext = array('type'=>'1','mobile'=>$oid_info['mobile'],'user_nickname'=>$uid_info['user_nickname'],'avatar'=>$uid_info['avatar'],'content'=>$content);
        }else{
            $ext = array('type'=>'2','mobile'=>$oid_info['mobile'],'user_nickname'=>$uid_info['user_nickname'],'avatar'=>$uid_info['avatar'],'content'=>$content);
        }

        //$from="admin",$target_type,$target,$content,$ext
        $info = $ease->sendText($uid_info['user_nickname'].$uid_info['mobile'],'users',[$oid_info['mobile']],$content,$ext);
//        SeasLog::info($uid_info['mobile'].'-'.[$oid_info['mobile']].'--'.json_encode($info));
        $info = $info['data'];
        if (empty($info)){
            $this->json(-1,'消息发送失败');
        }
        $this->json(0,'发送成功',$info);


    }


    /**
     *碰聊--视频聊天
     */
     public function faceTime(){
         $sex = I('sex');
         $uid = I('uid');
         $time = date('Y-m-d H:i:s');
         //根据uid更新登录时间
         if (!$uid){
             $this->json(-1,'参数不全');
         }
         $res = M('user')->where('id='.$uid)->find();
         if (!$res){
             $this->json(-2,'用户信息不存在');
         }
         //更新登录时间
         $savedate = M('user')->where('id='.$uid)->setField('last_login_time',$time);

         if (!$savedate){
             $this->json(-3,'刷新过于频繁,请稍后再试');
         }

         $now = strtotime($time);
         $where = array();
         if ($sex =='1' || $sex =='男'){
             $where['sex']=1;
         }else if($sex =='2'||$sex=='女'){
             $where['sex']=2;
         }else{
             $where['sex']=0;
         }
         $where['id'] =array('neq',$uid);
         $user_info = M('user')->where($where)->field('id,mobile,UNIX_TIMESTAMP(last_login_time) as time')->order('UNIX_TIMESTAMP(last_login_time) desc')->limit(10)->select();
        $user_id = array_rand($user_info,1);
        $account_info = $user_info[$user_id];
        unset($account_info['time']);

         if (!$account_info){
             //如果没有信息,返回同性
             if ($where['sex']==1 ||$where['sex']=='男'){
                 $account_info = M('user')->where("sex = 2 or sex ='女'")->field('id,mobile,UNIX_TIMESTAMP(last_login_time) as time')->order('UNIX_TIMESTAMP(last_login_time) desc')->limit(1)->find();
             }elseif ($where['sex']==2 ||$where['sex']=='女'){
                 $account_info = M('user')->where("sex = 1 or sex ='男'")->field('id,mobile,UNIX_TIMESTAMP(last_login_time) as time')->order('UNIX_TIMESTAMP(last_login_time) desc')->limit(1)->find();
             }else{
                 $this->json(-4,'暂时无人');
             }
         }
         if(empty($account_info['avatar'])) {
             $account_info['avatar'] = C('DEFAULT_AVATAR');
         }
         $this->json(0,'成功',$account_info);
     }

    /**
     *  碰聊--附近的碰聊
     */
    public function nearbyChat(){
        $lat = I('lat');//纬度
        $lng = I('lng');//经度
        $uid = I('uid');
        $sex = I('sex');
        $time = date('Y-m-d H:i:s');
        if (!$uid || !$lng || !$lat){
            $this->json(-1,'参数不全');
        }
        if (!$sex){
            $sex = 0;
        }
        $res = M('user')->where('id='.$uid)->find();
        if (!$res){
            $this->json(-2,'用户信息不存在');
        }

        //更新时间
        $data['last_login_time'] = $time;
        $savedate = M('user')->where('id='.$uid)->save($data);

        if (!$savedate){
            $this->json(-3,'刷新过于频繁,请稍后再试');
        }

        $range = I('range');

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
                return $this->json(-4,'请重新请求数据');
            }
        }else{
            $add_res = M('nearby_user')->where('uid = '.$uid)->save($data);
            if(!$add_res){
                return $this->json(-4,'请重新请求数据');
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
            return $this->json(-5,'没有更多附近的人');
        }

        //存放用户uid
        $uids_arr = array();
        foreach($find_nearby_user as $key => $val){
            $uids_arr[] = $val['uid'];
        }
        $uids_arr = array_unique($uids_arr);
        //$uids_arr = array_merge(array_diff($uids_arr, array($uid)));//去除自己的uid
        if(empty($uids_arr)){
            return $this->json(-5,'没有更多附近的人');
        }

        //查询用户信息
        $user_where['id'] = array('in',$uids_arr);
        $find_user_info = M('user')->field('id,sex,user_nickname,avatar,mobile')->where($user_where)->select();

        foreach($find_nearby_user as $k2 => $v2){
            foreach($find_user_info as $k3 => $v3){
                if($v2['uid'] == $v3['id']){
                    $find_nearby_user[$k2]['user_nickname'] = $v3['user_nickname'];
                    $find_nearby_user[$k2]['avatar'] = $v3['avatar'];
                    $find_nearby_user[$k2]['sex'] = $v3['sex'];
                    $find_nearby_user[$k2]['mobile'] = $v3['mobile'];
                }
            }

            if(empty($find_nearby_user[$k2]['user_nickname'])){
                $find_nearby_user[$k2]['user_nickname'] = "";
            }
            if(empty($find_nearby_user[$k2]['avatar'])){
                $find_nearby_user[$k2]['avatar'] =  C('DEFAULT_AVATAR');
            }

        }

        $user_info = array();
        if ($sex =='1' || $sex =='男'){
            //想碰到男的
            foreach ($find_nearby_user as $kk => $vv){
                if ($vv['sex'] ==1){
                    $user_info[] = $vv;
                    if (empty($user_info)){
                        $user_info = $find_nearby_user;
                    }
                }
            }

        }else if($sex =='2'||$sex=='女'){
            //想碰到女的
            foreach ($find_nearby_user as $kk => $vv){
                if ($vv['sex'] ==2){
                    $user_info[] = $vv;
                    if (empty($user_info)){
                        $user_info = $find_nearby_user;
                    }
                }
            }
        }else{
            //其他随机
            $user_info = $find_nearby_user;
//            foreach ($find_nearby_user as $kk => $vv){
//                $user_info = $find_nearby_user;
//            }
        }
        $select_id = array_rand($user_info,1);
        $account_info = $user_info[$select_id];

        if (empty($account_info)){
            return $this->json(-5,'没有更多附近的人');
        }

        unset($account_info['time']);
        unset($account_info['n_id']);
        unset($account_info['create_time']);
        unset($account_info['signature']);
        unset($account_info['is_connection']);
        unset($account_info['juli']);

        $this->json(0,'成功',$account_info);

    }


    /**
     * 碰聊-附近-视频聊天
     */
    public function nearbyFacetime(){
        $lat = I('lat');//纬度
        $lng = I('lng');//经度
        $uid = I('uid');
        $sex = I('sex');
        $time = date('Y-m-d H:i:s');
        if (!$uid || !$lng || !$lat){
            $this->json(-1,'参数不全');
        }
        if (!$sex){
            $sex = 0;
        }
        $res = M('user')->where('id='.$uid)->find();
        if (!$res){
            $this->json(-2,'用户信息不存在');
        }

        //更新时间
        $data['last_login_time'] = $time;
        $savedate = M('user')->where('id='.$uid)->save($data);

        if (!$savedate){
            $this->json(-3,'刷新过于频繁,请稍后再试');
        }

        $range = I('range');

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
                return $this->json(-4,'请重新请求数据');
            }
        }else{
            $add_res = M('nearby_user')->where('uid = '.$uid)->save($data);
            if(!$add_res){
                return $this->json(-4,'请重新请求数据');
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
            return $this->json(-5,'没有更多附近的人');
        }

        //存放用户uid
        $uids_arr = array();
        foreach($find_nearby_user as $key => $val){
            $uids_arr[] = $val['uid'];
        }
        $uids_arr = array_unique($uids_arr);
        //$uids_arr = array_merge(array_diff($uids_arr, array($uid)));//去除自己的uid
        if(empty($uids_arr)){
            return $this->json(-5,'没有更多附近的人');
        }
        $where['id'] = array('in',$uids_arr);
        $find_user_info = M('user')->where($where)->select();

        //查找自己的信息
        $find_my_info = M('user')->where('id = '.$uid)->find();

        foreach($find_nearby_user as $key2 => $val2){
            foreach($find_user_info as $key3 => $val3){
                if($val2['uid'] == $val3['id']){
                    $find_nearby_user[$key2]['user_nickname'] = $val3['user_nickname'];
                    $find_nearby_user[$key2]['avatar'] = $val3['avatar'];
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

        $user_info = array();
        if ($sex =='1' || $sex =='男'){
            //想碰到男的
            $unset_ids = array();
            foreach ($find_nearby_user as $kk => $vv){
                if ($vv['sex'] ==1){
                    $user_info[] = $vv;
                }
            }

        }else if($sex =='2'||$sex=='女'){
            //想碰到女的
            $unset_ids = array();
            foreach ($find_nearby_user as $kk => $vv){
                if ($vv['sex'] ==2){
                    $user_info[] = $vv;
                }
            }
        }else{
            //其他随机
            $unset_ids = array();
            foreach ($find_nearby_user as $kk => $vv){
                if ($vv['sex'] ==0 || $vv['sex'] ==1 || $vv['sex']==2){
                    $user_info[] = $vv;
                }
            }
        }

        $select_id = array_rand($user_info,1);
        $account_info = $user_info[$select_id];

        if (empty($account_info)){
            return $this->json(-5,'没有更多附近的人');
        }

        unset($account_info['time']);
        unset($account_info['n_id']);
        unset($account_info['create_time']);
        unset($account_info['create_time']);
        unset($account_info['signature']);
        unset($account_info['is_connection']);
        unset($account_info['juli']);
        unset($account_info['lng']);
        unset($account_info['lat']);
        unset($account_info['user_nickname']);
        unset($account_info['avatar']);
        unset($account_info['sex']);

        $this->json(0,'成功',$account_info);
    }

    /**
     * bumpChat撞一撞
     */
    public function bumpChat(){
        
        $uid = I('uid');
        $time = date('Y-m-d H:i:s');
        $now = time();
        $today_time = strtotime(date("Y-m-d"));
        //根据uid更新登录时间
        if (!$uid){
            $this->json(-1,'参数不全');
        }
        $res = M('user')->where('id='.$uid)->find();
        if (!$res){
            $this->json(-2,'用户信息不存在');
        }
        //更新登录时间
        $savedate = M('user')->where('id='.$uid)->setField('last_login_time',$time);

        if (!$savedate){
            $this->json(-3,'过于频繁,请稍后再试');
        }

        $where1['id'] =array('neq',$uid);
        $account_info = M('user')
            ->field('id,mobile,sex,user_nickname,UNIX_TIMESTAMP(last_login_time) as time,avatar')
            ->order('UNIX_TIMESTAMP(last_login_time) desc,rand()')
            ->where($where1)
            ->limit(1)
            ->find();

//        print_r($now -$account_info['time']);
        if ($now -$account_info['time'] >30){
            //时间相差超过30秒

            $bribery_money_record_count = M('bribery_money_record')->where('uid = '.$uid.' and time >= '.$today_time.' and ad_type = 2')->count();
            if($bribery_money_record_count > 10){
                return $this->json(-4,"今天已收获太多红包，请明天再来！");
            }
            $condition = 'ad_type = 2 and p_status = 2 and remaining_sum != 0 and start_time <= '.$now.' and time_range >= '.$now;
            $info = M('touch')->where($condition)->select();
            if (!$info){
                $this->json(-5,'非常遗憾,没有撞到红包');
            }
            $who = array_rand($info,1);
            $info1 = $info[$who];

            unset($info1['p_num']);
            unset($info1['p_time']);
            unset($info1['ad_type']);
            unset($info1['remaining_sum']);
            unset($info1['remain_num']);
            unset($info1['time_range']);
            unset($info1['p_status']);
            unset($info1['start_time']);
            unset($info1['price']);
            $this->json(1,'恭喜撞到红包',$info1);
        }
        unset($account_info['time']);//删除时间

        if(empty($account_info['avatar'])) {
            $account_info['avatar'] = C('DEFAULT_AVATAR');
        }

        $this->json(0,'成功',$account_info);
    }

    /**
     * add_friend   加好友
     * @return string
     */
    public function add_friend(){

        $uid = I('uid');//当前用户本人uid
        if(!$uid){
            return $this->json(-1,'没有获取到本人ID');
        }
        $f_mobile_id = I('oid');//好友的uid
        if(!$f_mobile_id){
            return $this->json(-2,'没有获取到好友的id');
        }

        //查找好友信息
        //查找好友用户信息
        $f_mobile = M('user')->where('id='.$f_mobile_id)->getField('mobile');

        $f_info = M('user')->where("mobile = ".$f_mobile)->find();
        if(!$f_info){
            return $this->json(-5,'没有查到好友用户信息');
        }

        if($uid == $f_info['id']){
            return $this->json(-3,'不能加自己为好友');
        }

        //查找本人用户信息
        $u_info = M('user')->where("id = ".$uid)->find();
        if(!$u_info){
            return $this->json(-4,'没有查到本人用户信息');
        }


        if($f_info['top_father'] == $u_info['top_father']){
            return $this->json(-6,'您所加的好友已经和您是同一人脉圈');
        }

        //查找好友表，检查是否已经建立好友关系。
        $friend_info = M('user_friends')->where("uid = ".$uid." and f_uid = ".$f_info['id'])->find();
        if($friend_info){
            return $this->json(-7,'好友已经存在');
        }

        $data['uid'] = $uid;
        $data['f_uid'] = $f_info['id'];
        $data['create_time'] = time();
        $add_res1 = M('user_friends')->add($data);

        $data['f_uid'] = $uid;
        $data['uid'] = $f_info['id'];
        $data['create_time'] = time();
        $add_res2 = M('user_friends')->add($data);
        if(!$add_res1 || !$add_res2){
            return $this->json(-8,'添加好友失败');
        }

        $huanxin = new Easemob();
        $huanxin->addFriend($u_info['mobile'],$f_info['mobile']);


        return $this->json(0,'添加好友成功');
    }

    /**
     * delete_friend  删除好友
     * @return string
     */
    public function delete_friend(){
        $self_uid = I('uid');
        $oid = I('oid');
        $self_mobile = M('user')->where('id='.$self_uid)->getField('mobile');
        $oid_mobile = M('user')->where('id='.$oid)->getField('mobile');

        if (!$self_uid ||!$oid_mobile){
            $this->json(-1,'用户信息有误,请核对');
        }
        $ease = new Easemob();
        $ease->deleteFriend($self_mobile,$oid_mobile);

        return $this->json(0,'删除好友成功');
    }



}