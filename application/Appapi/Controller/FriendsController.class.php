<?php
/**
 * Created by PhpStorm.
 * User: 石磊
 * Date: 2017/6/20 0020
 * Time: 上午 9:38
 */
namespace Appapi\Controller;

use Common\Common\Easemob;
use Common\Controller\ApibaseController;

class FriendsController extends ApibaseController{
    private $path = 'http://debo.shangtongyuntian.com';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 添加好友接口，不包括人脉。
     * */
    public function add_friend(){
        $uid = I('uid');//当前用户本人uid
        if(!$uid){
            return $this->json(-1,'没有获取到本人ID');
        }
        $f_mobile = I('f_mobile');//好友的uid
        if(!$f_mobile){
            return $this->json(-2,'没有获取到好友的手机号');
        }
        if($f_mobile == '18788888888'){
            return $this->json(-99,'没有获取到好友的手机号');
        }
        //查找好友信息
        //查找好友用户信息
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
        //$huanxin->addFriend($f_info['mobile'],$u_info['mobile']);

        return $this->json(0,'添加好友成功');

    }

    /**
     *获取好友列表
     * */
    public function friend_list(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'没有获取到用户id');
        }
        $find_user = M('user')->where("id = ".$uid)->find();
        if(!$find_user){
            return $this->json(-2,'没有查到当前用户信息');
        }
        $huanxin = new Easemob();
        $res = $huanxin->showFriends($find_user['mobile']);

        $infos = implode(',',$res['data']);
        if($infos == ''){
            return $this->json(-3,'没有好友');
        }

        $field = 'id,user_nickname,avatar,sex,signature,mobile,province,city,debo_number';
        $list = M('user')->where("mobile in (".$infos.")")->field($field)->select();//['mobile'=>['in',$infos]]
        if(!$list){
            return $this->json(-4,'没有好友信息');
        }

        $fids_arr = array();
        foreach($list as $key1 => $val1){
            $fids_arr[] = $list[$key1]['id'];
        }

        $fids_str = implode(",",$fids_arr);

        $f_list = M('user_friends')->where("uid = ".$uid." and f_uid in (".$fids_str.")")->select();

        if(!$f_list){
            return $this->json(-4,'没有好友信息');
        }
        foreach($list as $key2=>$val2){
            foreach($f_list as $key3=>$val3){
                if($list[$key2]['id'] == $f_list[$key3]['f_uid']){
                    if($f_list[$key3]['remark']){
                        $list[$key2]['user_nickname'] = $f_list[$key3]['remark'];
                    }
                    $list[$key2]['create_time'] = $f_list[$key3]['create_time'];
                }
            }
            if(empty($list[$key2]['user_nickname'])){
                $list[$key2]['user_nickname'] = $val2['mobile'];
            }
            if(!trim($list[$key2]['user_nickname'])){
                $list[$key2]['user_nickname'] = $val2['mobile'];
            }
            if(empty($list[$key2]['avatar'])){
                $list[$key2]['avatar'] = C('DEFAULT_AVATAR');
            }
        }

        $this->json(0,'成功',$list);

    }

    /**
     *修改好友备注
     * */
    public function modify_remark(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1, '没有获取用户ID');
        }
        $f_uid = I('f_uid');
        $f_mobile = I('f_mobile');
        if(!$f_uid && !$f_mobile){
            return $this->json(-2, '没有获取好友ID或手机号');
        }

        if($f_uid){
            $data['remark'] = trim(I('remark'));
            $data['update_time'] = time();
            $find_res = M('user_friends')->where("uid = ".$uid." and f_uid = ".$f_uid)->find();
            if(!$find_res){
                return $this->json(-3, '没有找到该好友');
            }else{
                if($find_res['remark'] == $data['remark']){
                    return $this->json(0, '修改成功');
                }
            }

            $where['uid'] = $uid;
            $where['f_uid'] = $f_uid;
            $res = M('user_friends')->where($where)->save($data);
            if(!$res){
                return $this->json(-4, '修改失败');
            }
            return $this->json(0, '修改成功');
        }

        if($f_mobile){
            $data['remark'] = trim(I('remark'));
            $data['update_time'] = time();
            $find_friend_info = M('user')->where('mobile = '.$f_mobile)->find();
            $find_res = M('user_friends')->where("uid = ".$uid." and f_uid = ".$find_friend_info['id'])->find();
            if(!$find_res){
                return $this->json(-3, '没有找到该好友');
            }else{
                if($find_res['remark'] == $data['remark']){
                    return $this->json(0, '修改成功');
                }
            }
            $where['uid'] = $uid;
            $where['f_uid'] = $find_friend_info['id'];
            $res = M('user_friends')->where($where)->save($data);
            if(!$res){
                return $this->json(-4, '修改失败');
            }

            return $this->json(0, '修改成功');
        }

    }

    /**
     *搜索自己的好友
     * */
    public function find_my_friends(){
        $uid = I('uid');
        $search_info = I('search_info');//可以是昵称，可以是备注，可以是手机号,可以是嘚啵号
        if(!$uid){
            return $this->json(-1,'没有获取到用户id');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        if(!$search_info){
            return $this->json(-3,'搜索内容不能为空');
        }

        $where['de_user_friends.remark|de_user.user_nickname|de_user.mobile|debo_number'] = array("like","%".$search_info."%");
        $where['de_user_friends.uid'] = $uid;

        $find_my_friends = M('user')->join('de_user_friends on de_user.id = de_user_friends.f_uid')->where($where)->field('de_user.id as uid,de_user.user_nickname,de_user.avatar,de_user.mobile,de_user_friends.remark,de_user.debo_number')->select();

        if($find_my_friends){
            foreach($find_my_friends as $key => $val){
                if(empty($val['avatar'])) {
                    $find_my_friends[$key]['avatar'] = C('DEFAULT_AVATAR');
                } else {
                    $find_my_friends[$key]['avatar'] = $val['avatar'];
                }
            }
            return $this->json(0,'成功',$find_my_friends);
        }else{
            return $this->json(-4,'没有搜索到好友');
        }

    }

    /**
     * 删除好友
     * */
    public function del_friend(){
        $uid = I('uid');
        $f_mobile = I('f_mobile');
        if(!$f_mobile || !$uid){
            return $this->json(-1,'缺少参数');
        }

        //查找好友信息
        $find_friend_info = M('user')->where('mobile = '.$f_mobile)->find();
        if(!$find_friend_info){
            return $this->json(-2,'没有查到该好友信息');
        }

        //开始删除好友
        M('user_friends')->where('uid = '.$uid.' and f_uid = '.$find_friend_info['id'])->delete();
        M('user_friends')->where('uid = '.$find_friend_info['id'].' and f_uid = '.$uid)->delete();

        $find_my_info = M('user')->where('id = '.$uid)->find();
        $huanxin = new Easemob();
        $huanxin->deleteFriend($find_my_info['mobile'],$find_friend_info['mobile']);
        //$huanxin->deleteFriend($find_friend_info['mobile'],$find_my_info['mobile']);
        return $this->json(0,'删除成功');

    }

    /**
     * 创建一个群聊组
     * */
    public function create_chat_group(){
        $groupname = I('groupname');//群组名称，此属性为必须的
        $desc = I('desc'); //群组描述，此属性为必须的
//        $public = I('public');//是否是公开群，此属性为必须的 true,false
//        $members_only = I('members_only');//加入群是否需要群主或者群管理员审批，默认是false
//        $allowinvites = I('allowinvites');//是否允许群成员邀请别人加入此群。 true：允许群成员邀请人加入此群，false：只有群主或者管理员才可以往群里加人。
        $owner = I('owner');//群组的管理员，此属性为必须的
        $members = I('members');//群组成员，此属性为可选的，但是如果加了此项，数组元素至少一个（注：群主jma1不需要写入到members里面）
        $qroup_type = I('qroup_type');//群的类型；1、好友群；2、人脉群

        if(!$owner){
            return $this->json(-1,'缺少参数');
        }

        //通过群主

        $options = array(
            'groupname'=>$groupname,
            'desc'=>$desc,
            'public'=>true,
            'maxusers'=>1000,
            'members_only'=>false,
            'allowinvites'=>false,
            'owner'=>$owner
        );

        if($members){
            $temp_arr = explode(",",$members);
            $options['members'] = $temp_arr;
        }

        $ease = new Easemob();
        $res = $ease->createGroup($options);

        if($res['data']['groupid']){
            $group_id = $res['data']['groupid'];
            $create_time = $res['timestamp'];
            $data = array(
                'group_id'=>$group_id,
                'create_time'=>time(),
                'g_mobile'=>$owner,
                'qroup_type'=>$qroup_type
            );
            if(!empty($_FILES)){
                $info = $this->upload('chatgroup/mobile_'.$owner);
                if($info){
                    $first = array_shift($info);
                    $data['g_avatar'] = "http://".$_SERVER['SERVER_NAME'].'/debo'.UPLOADS.$first['savepath'].$first['savename'];
                }
            }
            $add_res = M('chatgroup')->add($data);
            if($add_res){
                $find_group = M('chatgroup')->where('g_id = '.$add_res)->find();
                if(empty($find_group['g_avatar'])) {
                    $find_group['g_avatar'] = C('DEFAULT_AVATAR');
                }
            }else{
                $find_group['error'] = "数据添加失败";
            }

            //将群主添加到群成员表中
            $add_data['mobile'] = $owner;
            $add_data['group_id'] = $res['data']['groupid'];
            $add_data['member_type'] = 1;

            $add_owner = M('chatgroup_member')->add($add_data);

            if(!empty($temp_arr)){
                foreach($temp_arr as $kk => $vv){
                    $add_all[$kk]['mobile'] = $vv;
                    $add_all[$kk]['group_id'] = $res['data']['groupid'];
                    $add_all[$kk]['member_type'] = 3;
                }
                $add_owner = M('chatgroup_member')->addAll($add_all);
            }

            return $this->json(0,'成功',$find_group);
        }else{
            return $this->json(-2,'创建失败');
        }


    }

    /**
     *获取一个用户参与的所有群组
     * */
    public function get_group_for_user(){
        $mobile = I('mobile');
        if(!$mobile){
            return $this->json(-1,'缺少参数');
        }

        $ease = new Easemob();
        $res = $ease->getGroupsForUser($mobile);
        if(!empty($res['data'])){
            $info = $res['data'];
            //存放group_is
            $group_ids = array();
            foreach($info as $key => $val){
                $group_ids[] = $val['groupid'];
            }
            //查询相应的数据
            $where['group_id'] = array('in',$group_ids);
            $find_info = M('chatgroup')->where($where)->select();

            //查询自己是否设置不显示自己的昵称
            //$find_myself_info = M('user')->where('mobile = '.$mobile)->find();
            $where['mobile'] = $mobile;
            $find_group_info = M('chatgroup_member')->where($where)->select();
            if($find_group_info){
                foreach($info as $key2 => $val2){
                    foreach($find_group_info as $key3 => $val3){
                        if($val2['groupid'] == $val3['group_id']){
                            $info[$key2]['is_show_name'] = $val3['is_show_name'];
                        }
                    }
                    if(empty($info[$key2]['is_show_name'])){
                        $info[$key2]['is_show_name'] = "1";
                    }
                }
            }else{
                foreach($info as $key2 => $val2){
                    $info[$key2]['is_show_name'] = "1";
                }
            }

            foreach($info as $key2 => $val2){
                foreach($find_info as $key3 => $val3){
                    if($val3['group_id'] == $val2['groupid']){
                        $info[$key2]['g_avatar'] = $val3['g_avatar'];
                        $info[$key2]['qroup_type'] = $val3['qroup_type'];
                    }
                }
                if(empty($info[$key2]['g_avatar'])){
                    $info[$key2]['g_avatar'] = C('DEFAULT_AVATAR');
                }
                if(!$info[$key2]['groupname']){
                    $info[$key2]['groupname'] = $val2['groupid'];
                }
            }

            return $this->json(0,'成功',$info);
        }else{
            return $this->json(-2,'您还没有群组');
        }

    }

    /**
     *修改群组信息（修改头像，其他部分前端自己使用环信功能，需要后台增加功能再增加。）
     * */
    public function modify_group_info(){
        $g_id = I('groupid');
        $mobile = I('mobile');
        if(!$g_id || !$mobile){
            return $this->json(-1,'缺少参数');
        }

        if(!empty($_FILES)){
            $info = $this->upload('chatgroup/mobile_'.$mobile);
            if($info){
                $first = array_shift($info);
                $data['g_avatar'] = "http://".$_SERVER['SERVER_NAME'].'/debo'.UPLOADS.$first['savepath'].$first['savename'];

                //删除旧的头像
                $find_user_info = M('chatgroup')->where("g_mobile = '".$mobile."'")->find();
                $path = explode("debo/",$find_user_info['g_avatar'])[1];
                if(file_exists(SITE_PATH.$path)){
                    unlink(SITE_PATH.$path);
                }

                $modify_res = M('chatgroup')->where("group_id = '".$g_id."'")->save($data);
                if($modify_res){
                    return $this->json(0,'修改成功');
                }else{
                    return $this->json(-3,'修改失败');
                }

            }
        }else{
            return $this->json(-2,'没有获取到图片');
        }

    }

    /**
     * 删除群组
     * */
    public function delete_group(){
        $group_id = I('groupid');
        if(!$group_id){
            return $this->json(-1,'缺少参数');
        }

        $find_info = M('chatgroup')->where('group_id = '.$group_id)->find();
        if(!$find_info){
            return $this->json(-2,'群组信息不存在');
        }

        $res = M('chatgroup')->where('group_id = '.$group_id)->delete();
        if(!$res){
            return $this->json(-3,'删除失败');
        }

        //删除全部的群成员
        $del_res = M('chatgroup_member')->where("group_id = '".$group_id."'")->delete();

        $ease = new Easemob();
        $res = $ease->deleteGroup($group_id);

        return $this->json(0,'删除成功');

    }

    /**
     *获取群组中的成员
     * */
    public function get_group_users(){
        $group_id = I('groupid');
        $search_group_id = $group_id;//方面查询个人名称使用
        $my_mobile = I('my_mobile');
        if(!$group_id && !$my_mobile){
            return $this->json(-1,'缺少参数');
        }
        $ease = new Easemob();
        $res = $ease->getGroupUsers($group_id);

        if(empty($res['data'])){
            return $this->json(-2,'您还没有群成员');
        }

        $mobile_arr = array();
        foreach($res['data'] as $key => $val){
            if($val['member']){
                $mobile_arr[] = $val['member'];
            }else if($val['owner']){
                $mobile_arr[] = $val['owner'];
            }
        }
        $where['mobile'] = array('in',$mobile_arr);
        $find_user_info = M('user')->where($where)->select();
        $where['group_id'] = $group_id;
        $find_name_for_group = M('chatgroup_member')->where($where)->select();
        foreach($res['data'] as $key => $val){
            foreach($find_user_info as $key2 => $val2){
                if($val['member'] == $val2['mobile']){
                    $res['data'][$key]['user_nickname'] = $val2['user_nickname'];
                    if(empty($val2['avatar'])) {
                        $res['data'][$key]['avatar'] = C('DEFAULT_AVATAR');
                    } else {
                        $res['data'][$key]['avatar'] = $val2['avatar'];
                    }
                    $res['data'][$key]['member_type'] = "3";
                }else if($val['owner'] == $val2['mobile']){
                    $res['data'][$key]['user_nickname'] = $val2['user_nickname'];
                    if(empty($val2['avatar'])) {
                        $res['data'][$key]['avatar'] = C('DEFAULT_AVATAR');
                    } else {
                        $res['data'][$key]['avatar'] = $val2['avatar'];
                    }
                    $res['data'][$key]['member_type'] = "1";
                }
            }

            if($find_name_for_group){
                foreach($find_name_for_group as $key3 => $val3){
                    if($val['member'] == $val3['mobile']){
                        $res['data'][$key]['member_name'] = $val3['member_name'];
                        $res['data'][$key]['member_type'] = $val3['member_type'];
                    }else if($val['owner'] == $val3['mobile']){
                        $res['data'][$key]['member_name'] = $val3['member_name'];
                        $res['data'][$key]['member_type'] = "1";
                    }
                }
                if(empty($res['data'][$key]['member_name'])){
                    $res['data'][$key]['member_name'] = "";
                }
            }else{
                $res['data'][$key]['member_name'] = "";
            }

        }

        //查询是否显示用户昵称
        $find_is_show_name = M('chatgroup_member')->where("group_id = '".$group_id."' and mobile = '".$my_mobile."'")->find();
        if($find_is_show_name){
            $is_show_name = $find_is_show_name['is_show_name'];
            $myname_for_group = $find_is_show_name['member_name'];
        }else{
            $is_show_name = "1";
            $myname_for_group = "";
        }

        $group_id = explode(",",$group_id);
        $res1 = $ease->getGroupDetail($group_id);
        $res1['data'][0]['affiliations'] = $res['data'];
        $res1['data'][0]['is_show_name'] = $is_show_name;
        $res1['data'][0]['is_public'] = $res1['data'][0]['public'];
        unset($res1['data'][0]['public']);
        array_multisort(array_column($res1['data'][0]['affiliations'],'member_type'),SORT_ASC,$res1['data'][0]['affiliations']);

        //只放群组管理员的数组
        $only_arr = array();
        foreach($res1['data'][0]['affiliations'] as $kk => $vv){
            if($vv['member_type'] != 3){
                $only_arr[] = $vv;
            }
        }
        $res1['data'][0]['manager_owner'] = $only_arr;

        //查询本人在群里面的昵称
        $find_myself_name = M('chatgroup_member')->where("group_id = '".$search_group_id."' and mobile = '".$my_mobile."'")->find();
        if(!$find_myself_name){
            $res1['data'][0]['myname_for_group'] = "";
        }else{
            $res1['data'][0]['myname_for_group'] = $find_myself_name['member_name'];
        }

        //查询个人信息
        $find_myself_info = M('user')->where("mobile = '".$my_mobile."'")->find();
        if($find_myself_info){
            if($find_myself_info['user_nickname']){
                $res1['data'][0]['user_nickname'] = $find_myself_info['user_nickname'];
            }else{
                $res1['data'][0]['mobile'] = $find_myself_info['mobile'];
            }
        }

        return $this->json(0,'成功',$res1['data']);

    }

    /**
     * 群组批量加人
     * */
    public function add_group_members(){
        $group_id = I('groupid');
        $mobile = I('mobile');//多个手机号使用“，”分割（普通用户）
        if(!$group_id || !$mobile){
            return $this->json(-1,'缺少参数');
        }

        $mobile = explode(",",$mobile);
        $temp_arr = array();
        foreach($mobile as $k => $v){
            $temp_arr[$k]['mobile'] = $v;
            $temp_arr[$k]['group_id'] = $group_id;
            $temp_arr[$k]['member_type'] = 3;
        }

        $add_res = M('chatgroup_member')->addAll($temp_arr);
        if($add_res){
            $mobile = array(
                'usernames'=>$mobile
            );

            $ease = new Easemob();
            $res = $ease->addGroupMembers($group_id,$mobile);
            if(empty($res['data'])){
                return $this->json(-2,'添加失败');
            }

            //查询用户信息
            $where['mobile'] = array('in',$res['data']['newmembers']);
            $find_users_arr = M('user')->where($where)->select();
            foreach($res['data']['newmembers'] as $key => $val){
                foreach($find_users_arr as $key2 => $val2){
                    if($val == $val2['mobile']){
                        unset($res['data']['newmembers'][$key]);
                        $res['data']['newmembers'][$key]['mobile'] = $val2['mobile'];
                        $res['data']['newmembers'][$key]['user_nickname'] = $val2['user_nickname'];
                        if(empty($val2['avatar'])) {
                            $res['data']['newmembers'][$key]['avatar'] = C('DEFAULT_AVATAR');
                        } else {
                            $res['data']['newmembers'][$key]['avatar'] = $val2['avatar'];
                        }
                    }
                }
            }

            return $this->json(0,'成功',$res['data']);
        }else{
            return $this->json(-2,'添加失败');
        }

    }

    /**
     *添加群管理员
     * */
    public function add_group_manager(){
        $group_id = I('groupid');
        $mobile = I('mobile');
        if(!$group_id || !$mobile){
            return $this->json(-1,'缺少参数');
        }

        $data['member_type'] = 2;
        $modify_type = M('chatgroup_member')->where("group_id = '".$group_id."' and mobile = '".$mobile."'")->save($data);
        if($modify_type){
            //$mobile = explode(",",$mobile);

            $mobile = array(
                'newadmin'=>$mobile
            );

            $ease = new Easemob();
            $res = $ease->addGroupManager($group_id,$mobile);
            if(empty($res['data'])){
                return $this->json(-2,'添加失败');
            }

            $find_user_info = M('user')->where('mobile = '.$res['data']['newadmin'])->find();
            $res['data']['mobile'] = $find_user_info['mobile'];
            $res['data']['user_nickname'] = $find_user_info['user_nickname'];
            if(empty($find_user_info['avatar'])) {
                $res['data']['avatar'] = C('DEFAULT_AVATAR');
            } else {
                $res['data']['avatar'] = $find_user_info['avatar'];
            }
            //查询群昵称
            $find_user_info2 = M('chatgroup_member')->where("group_id = '".$group_id."' and mobile = '".$mobile."'")->find();
            $res['data']['member_name'] = $find_user_info['member_name'];

            return $this->json(0,'成功',$res['data']);
        }else{
            return $this->json(-2,'添加失败');
        }

    }

    /**
     *群组批量减人
     * */
    public function delete_group_members(){
        $group_id = I('groupid');
        $mobile = I('mobile');//多个手机号使用“，”分割
        if(!$group_id || !$mobile){
            return $this->json(-1,'缺少参数');
        }
        $mobile2 = explode(',',$mobile);
        $where['mobile'] = array('in',$mobile);
        $where['group_id'] = $group_id;
        $del_res = M('chatgroup_member')->where($where)->delete();
        M('chatgroup')->where("group_id = '".$group_id."'")->setDec('mem_count',count($mobile2));
        if($del_res){
            $ease = new Easemob();
            $res = $ease->deleteGroupMembers($group_id,$mobile);

            if(empty($res['data'])){
                return $this->json(-2,'删除失败');
            }

            return $this->json(0,'成功');
        }else{
            return $this->json(-2,'删除失败');
        }
    }

    /**
     *移除群管理员
     * */
    public function remove_group_manager(){
        $group_id = I('groupid');
        $mobile = I('mobile');//不能多个
        if(!$group_id || !$mobile){
            return $this->json(-1,'缺少参数');
        }

        $ease = new Easemob();
        $res = $ease->removeGroupManager($group_id,$mobile);
        if(empty($res['data'])){
            return $this->json(-2,'删除失败');
        }

        $del_res = M('chatgroup_member')->where("group_id = '".$group_id."' and mobile = '".$mobile."'")->setField('member_type',3);

        return $this->json(0,'成功',$res['data']);

    }

    /**
     *群组转让
     * */
    public function change_group_owner(){
        $group_id = I('groupid');
        $mobile = I("mobile");
        $my_mobile = I("my_mobile");
        if(!$group_id && !$mobile && !$my_mobile){
            return $this->json(-1,'缺少参数');
        }

        $newowner = array(
            'newowner'=>$mobile
        );

        $ease = new Easemob();
        $res = $ease->changeGroupOwner($group_id,$newowner);

        if(empty($res['data'])){
            return $this->json(-2,'转让失败');
        }

        //修改本地组成员表
        $change_res = M('chatgroup_member')->where("group_id = '".$group_id."' and mobile = '".$my_mobile."'")->setField('mobile',$mobile);

        return $this->json(0,'成功',$res['data']);

    }


}

?>