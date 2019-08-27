<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/12 0012
 * Time: 下午 3:21
 */

namespace Appapi\Controller;

use Common\Controller\ApibaseController;
use Common\Common\Easemob;

class ConnectionController extends ApibaseController{

    private $path = 'http://debo.shangtongyuntian.com';

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 人脉列表（type=0 全部有人脉关系的；type=1 直接人脉；type=2 邀请人脉；type=3 合约人脉；）
     * */
    public function connect_list(){

        //因为人脉可能会有很多，所以增加分页功能，如果不需要可以去掉

        $mobile = I('mobile');//用户自己的手机号
        $type = I('type');//0全部 1直接 2邀请 3合约

        if(!$mobile){
            return $this->json(-1,'缺少参数');
        }

        $page = I('page') ? I('page'):1;
        $row = 40;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查找用户信息
        $find_my_info = M('user')->where("mobile = ".$mobile)->find();

        if(!$find_my_info || $find_my_info['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        $field = 'id,mobile,user_nickname,avatar,top_father,pur_time';

        if(!$type){
            //查找和自己是同一人脉圈的所有用户（包含合约人脉）
            $find_connection = M('user')->where('top_father = '.$find_my_info['top_father']." or pur_mobile = ".$find_my_info['top_father'])->field($field)->select();
            //查找自己的人脉圈中是否存在合约人脉
//            $find_pur = M('user')->where('pur_top_father = '.$find_my_info['top_father'])->field('id,mobile,user_nickname,avatar,top_father,pur_time')->select();
//            if($find_pur){
//                //找到被购买人的top_father,并取出这个top_father下的所有用户
//                $pur_mobile_arr = array();
//                foreach($find_pur as $key => $val){
//                    $pur_mobile_arr[] = $val['top_father'];
//                }
//
//                $pur_mobile_str = implode(",",$pur_mobile_arr);
//                $find_all_pur = M('user')->where("top_father in (".$pur_mobile_str.")")->field($field)->select();
//
//                $find_connection = array_merge($find_connection,$find_all_pur);
//
//            }

            //查询自己是否被别人购买过
            if($find_my_info['pur_mobile']){
                //查询购买自己的人的信息
                $find_pur_me = M('user')->where("mobile = ".$find_my_info['pur_mobile'])->field('id,mobile,user_nickname,avatar,top_father,pur_time,pur_mobile')->find();
                if($find_connection){
                    array_push($find_connection,$find_pur_me);
                }else{
                    $find_connection = $find_pur_me;
                }
            }

        }

        if($type == 1){

            //查找直接人脉（显不显示自己的直接上级人脉）
            $find_connection = M('user')->where('invitation_code = '.$mobile)->field($field)->select();

        }else if($type == 2){

            //查找邀请人脉（也就是同一人脉圈中的人脉，不包含合约人脉）
            $find_connection = M('user')->where('top_father = '.$find_my_info['top_father'])->field($field)->select();

        }else if($type == 3){

            //查找合约人脉
            //$find_connection = M('user')->where('pur_top_father = '.$find_my_info['top_father'])->field($field)->select();
            $find_connection = M('user')->where("pur_mobile = '".$find_my_info['mobile']."'")->field($field)->select();
            if($find_connection){
                //$temp_top_father = array();
                foreach($find_connection as $kk => $vv){
                    //$temp_top_father[] = $vv['top_father'];//购买的合约人的顶级父类
                    $find_num = M('user')->where("top_father = '".$vv['top_father']."'")->count();
                    $find_connection[$kk]['con_fans_num'] = "".($find_num-1);
                    $find_connection[$kk]['pur_time'] = date("m月d日",$vv['pur_time'])."购买";
                }

            }

        }


        $con_uid_arr = array();

        if($find_connection){
            foreach($find_connection as $k => $v){
                $con_uid_arr[]=$v['id'];
                if($v['avatar']){
                    $find_connection[$k]['avatar'] = $v['avatar'];
                } else {
                    $find_connection[$k]['avatar'] = C('DEFAULT_AVATAR');
                }
                if(empty($find_connection[$k]['user_nickname'])){
                    $find_connection[$k]['user_nickname'] = $v['mobile'];
                }

                if($v['id'] == $find_my_info['id'])
                {
                    unset($find_connection[$k]);
                }
            }


            $where['f_uid'] = array('in',$con_uid_arr);
            $where['uid'] = $find_my_info['id'];
            $find_connection_remark = M('connection_remark')->where($where)->select();
            foreach($find_connection as $k => $v){
                foreach($find_connection_remark as $key => $val)
                {
                    if($v['id'] == $val['f_uid'] && $find_my_info['id'] == $val['uid']){
                        $find_connection[$k]['user_nickname'] = $val['remark'];

                    }
                }
                if(empty($find_connection[$k]['con_fans_num'])){
                    $find_connection[$k]['con_fans_num'] = "0";
                }

            }

            $find_connection = array_merge($find_connection);

            return $this->json(0,'成功',$find_connection);
        }else{
            return $this->json(-3,'没有更多人脉');
        }

    }

    /**
     * 点击合约人脉查询此合约人脉的所有下线
     * */
    public function contract_sub_lists(){
        $uid = I('uid');
        $con_uid = I('con_uid');//人脉的用户id
        if(!$uid || !$con_uid){
            return $this->json(-1,'缺少参数');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        //找到此合约人信息
        $find_info = M('user')->where('id = '.$con_uid)->find();
        if($find_info){
            //查找该合约人脉的下线
            $field = 'id,mobile,user_nickname,avatar,top_father';
            $sub_lists = M('user')->where("top_father = ".$find_info['top_father']." and id != ".$con_uid)->field($field)->select();
            if($sub_lists){
                foreach($sub_lists as $key => $val){
                    $sub_lists[$key]['avatar'] = $val['avatar'] ? $val['avatar'] : C('DEFAULT_AVATAR');
                }
                return $this->json(0,'成功',$sub_lists);
            }else{
                return $this->json(-3,'没有找到下线');
            }
        }else{
            return $this->json(-4,'没找到此人相关信息');
        }

    }

    /**
     * 搜索自己的人脉（包括合约人脉）
     * */
    public function find_mycon(){
        $uid = I('uid');
        $search_con = I('search_con');//可以是昵称，手机号
        if(!$uid || !$search_con){
            return $this->json(-1,'缺少参数');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        //先查查看有没有合约人脉
        $find_contract = M('user')->where('pur_top_father = '.$find_user['top_father'])->select();
        if($find_contract){
            $top_fathers_arr = array();
            foreach($find_contract as $key => $val){
                $top_fathers_arr[] = $val['top_father'];
            }
            $top_fathers_arr[] = $find_user['top_father'];
            $where['top_father'] = array('in',$top_fathers_arr);
            $where['user_nickname|mobile'] = array('like','%'.$search_con.'%');
            $field = 'id,mobile,user_nickname,avatar,top_father';
            $find_res = M('user')->where($where)->field($field)->select();

            if($find_res){
                foreach($find_res as $kk => $vv){
                    $find_res[$kk]['avatar'] = $vv['avatar'] ? $vv['avatar'] : C('DEFAULT_AVATAR');
                }
                return $this->json(0,'成功',$find_res);
            }else{
                return $this->json(-3,'没有找到');
            }
        }else{
            $where['top_father'] = $find_user['top_father'];
            $where['user_nickname|mobile'] = array('like','%'.$search_con.'%');
            $field = 'id,mobile,user_nickname,avatar,top_father';
            $find_res = M('user')->where($where)->field($field)->select();

            if($find_res){
                foreach($find_res as $kkk => $vvv){
                    $find_res[$kkk]['avatar'] = $vvv['avatar'] ? $vvv['avatar'] : C('DEFAULT_AVATAR');
                }
                return $this->json(0,'成功',$find_res);
            }else{
                return $this->json(-3,'没有找到');
            }
        }

    }

    /**
     *发布动态
     * */
    public function publish_talking(){
        $uid = I('uid');
        $cc_content = trim(I('cc_content'));
        $position = I('position');
        $lat = I('lat');
        $lng = I('lng');
        if($position){$data['position'] = $position;}
        if($lat){$data['lat'] = $lat;}
        if($lng){$data['lng'] = $lng;}
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        if($cc_content){
            $data['cc_content'] = $cc_content;
        }
        if(!empty($_FILES)){
            $image = $this->uploads('connection_pub_talking/talking_'.$uid);
            $data['cc_images'] = $image;
        }
        if(empty($_FILES) && !$data['cc_content']){
            return $this->json(-3,'发布内容不能为空');
        }
        $data['cc_uid'] = $uid;
        $data['create_time'] = time();

        $publish_res = M('connection_circle')->add($data);
        if($publish_res){
            return $this->json(0,'发布成功');
        }else{
            return $this->json(-4,'发布失败');
        }

    }

    /**
     *人脉圈评论
     * */
    public function connection_comment(){
        $uid = I('uid');//发布评论者uid
        $cc_id = I('moments_id');//朋友圈的动态id
        $comment_content = I('mc_content');//朋友圈评论内容
        $reply_uid = I('reply_uid');//回复人uid
        if(!$uid || !$cc_id || !$comment_content){
            return $this->json(-1,'缺少参数');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        if($uid == $reply_uid){
            return $this->json(-4,'不能给自己评论');
        }

        $data['uid'] = $uid;
        $data['cc_id'] = $cc_id;
        $data['comment_content'] = $comment_content;
        $data['comment_time'] = time();//评论时间
        if($reply_uid){
            $data['reply_uid'] = $reply_uid;
        }

        $com_res = M('connection_comment')->add($data);
        if($com_res){
            $find_res = M('connection_comment')->where('con_comment_id = '.$com_res)->field('con_comment_id as mc_id,comment_content as mc_content,uid,cc_id as moments_id,comment_time,reply_uid,state')->find();
            $find_user_info1 = M('user')->where('id = '.$find_res['uid'])->field('id,user_nickname,mobile')->find();
            $find_user_info2 = M('user')->where('id = '.$find_res['reply_uid'])->field('id,user_nickname,mobile')->find();
            $find_res['name'] = $find_user_info1['user_nickname'];
            $find_res['mobile'] = $find_user_info1['mobile'];
            $find_res['reply_name'] = $find_user_info2['user_nickname'];
            $find_res['reply_mobile'] = $find_user_info2['mobile'];
            $find_res['cpmment_time'] = date('Y-m-d H:i:s',$find_res['cpmment_time']);

            if($reply_uid){
                $find_remark = M('connection_remark')->where('uid = '.$uid.' and f_uid = '.$reply_uid)->find();
                if($find_remark['remark']){
                    $find_res['reply_name'] = $find_remark['remark'];
                }
            }

            return $this->json(0,'评论成功',$find_res);
        }else{
            return $this->json(-3,'评论失败');
        }

    }

    /**
     *人脉圈点赞
     * */
    public function connection_upvote(){
        $uid = I('uid');
        $cc_id = I('moments_id');//朋友圈的动态id
        $upvote = I('upvote');//点赞与否 1 已点赞 0 未点赞

        if(!$uid || !$cc_id){
            return $this->json(-1,'缺少参数');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        //查找点赞表中是否有你的记录
        $find_moments_upvote = M('connection_upvote')->where("uid = ".$uid." and cc_id = ".$cc_id)->find();
        if($find_moments_upvote){
            if($find_moments_upvote['upvote'] == $upvote){
                return $this->json(-3,'状态未发生改变');
            }
            $upvote_res = M('connection_upvote')->where("uid = ".$uid." and cc_id = ".$cc_id)->setField('upvote',$upvote);
            $upvote_res = M('connection_upvote')->where("uid = ".$uid." and cc_id = ".$cc_id)->setField('upvote_time',time());
            if($upvote_res){
                $find_res = M('connection_upvote')->where("uid = ".$uid." and cc_id = ".$cc_id)->field('cu_id as upvote_id,uid,cc_id as moments_id,upvote')->find();
                $find_user_res = M('user')->where('id = '.$find_res['uid'])->field('small_avatar')->find();
                if(empty($find_user_res['small_avatar'])) {
                    $find_res['small_avatar'] = C('DEFAULT_AVATAR');
                } else {
                    $find_res['small_avatar'] = $this->path.$find_user_res['small_avatar'];
                }


                //返回这条动态的点赞列表
//                $find_upvote_list = M('connection_upvote')->where("cc_id = ".$cc_id)->field('cu_id as upvote_id,uid,cc_id as moments_id,upvote')->select();
//                //收集用户uid
//                $arr_upvote_uids = array();
//                foreach($find_upvote_list as $key => $val){
//                    $arr_upvote_uids[] = $val['uid'];
//                }
//                $upvote_uid['id'] = array('in',$arr_upvote_uids);
//                $find_users_upvote_info = M('user')->where($upvote_uid)->field('id,small_avatar')->select();
//                foreach($find_upvote_list as $kk1 => $vv1){
//                    foreach($find_users_upvote_info as $kk2 => $vv2){
//                        if($vv1['uid'] == $vv2['id']){
//                            $find_upvote_list[$kk1]['small_avatar'] = $this->path.$vv2['small_avatar'];
//                        }
//                    }
//                }
//                $find_res['upvote_list'] = $find_upvote_list;

                return $this->json(0,'成功',$find_res);
            }else{
                return $this->json(-3,'状态未发生改变');
            }
        }else{
            $data['uid'] = $uid;
            $data['cc_id'] = $cc_id;
            $data['upvote'] = $upvote;
            $data['upvote_time'] = time();
            $upvote_res = M('connection_upvote')->add($data);
            if($upvote_res){
                $find_res = M('connection_upvote')->where('cu_id = '.$upvote_res)->field('cu_id as upvote_id,uid,cc_id as moments_id,upvote')->find();
                $find_user_res = M('user')->where('id = '.$find_res['uid'])->field('small_avatar')->find();
                if(empty($find_user_res['small_avatar'])) {
                    $find_res['small_avatar'] = C('DEFAULT_AVATAR');
                } else {
                    $find_res['small_avatar'] = $this->path.$find_user_res['small_avatar'];
                }

                //返回这条动态的点赞列表
                $find_upvote_list = M('connection_upvote')->where("cc_id = ".$cc_id)->field('cu_id as upvote_id,uid,cc_id as moments_id,upvote')->select();
                //收集用户uid
//                $arr_upvote_uids = array();
//                foreach($find_upvote_list as $key => $val){
//                    $arr_upvote_uids[] = $val['uid'];
//                }
//                $upvote_uid['id'] = array('in',$arr_upvote_uids);
//                $find_users_upvote_info = M('user')->where($upvote_uid)->field('id,small_avatar')->select();
//                foreach($find_upvote_list as $kk1 => $vv1){
//                    foreach($find_users_upvote_info as $kk2 => $vv2){
//                        if($vv1['uid'] == $vv2['id']){
//                            $find_upvote_list[$kk1]['small_avatar'] = $this->path.$vv2['small_avatar'];
//                        }
//                    }
//                }
//                $find_res['upvote_list'] = $find_upvote_list;

                return $this->json(0,'成功',$find_res);
            }else{
                return $this->json(-4,'失败');
            }
        }

    }

    /**
     * 人脉圈列表
     * */
    public function con_circle_lists(){

        $uid = I('uid');//有分页
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        if(empty($find_user['avatar'])) {
            $find_user['avatar'] = C('DEFAULT_AVATAR');
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查询自己是否有合约人脉
        $find_contract = M('user')->where('pur_top_father = '.$find_user['top_father'])->select();
        if($find_contract){

            //如果有合约人脉
            $contract_lists_arr = array();
            foreach($find_contract as $key => $val){
                $contract_lists_arr[] = $val['top_father'];
            }
            $contract_lists_arr[] = $find_user['top_father'];
            $where1['top_father'] = array('in',$contract_lists_arr);
            $find_my_con_ids = M('user')->where($where1)->field('id')->select();

            $my_con_ids_arr = array();//存放所有和自己有关系的人脉uid
            foreach($find_my_con_ids as $key1 => $val1){
                $my_con_ids_arr[] = $val1['id'];
            }

            //查询人脉圈权限表，排除掉数组中的被设置了权限的用户
            $find_authority_user = M('connection_authority')->where('uid = '.$uid." and (circle_state1 = 2 or circle_state2 = 2 or blacklist = 1)")->field('c_uid')->select();
            // 查询把你拉黑的用户
            $find_authority_user1 = M('connection_authority')->where('c_uid = '.$uid." and blacklist = 1")->field('uid')->select();
            if($find_authority_user){
                $authority_uids_arr = array();
                foreach($find_authority_user as $ky => $vl){
                    $authority_uids_arr[] = $vl['c_uid'];
                }
                $my_con_ids_arr = array_diff($my_con_ids_arr,$authority_uids_arr);//去掉重复的数组中的值
            }
            if($find_authority_user1) {
                $my_con_ids_arr = array_diff($my_con_ids_arr,array_column($find_authority_user1,'uid'));//去掉拉黑的id
            }

            $my_con_ids_arr_string = implode(',',$my_con_ids_arr);

            //查找这些uid发布过的动态，并且没有被设置朋友圈权限的。
            $where['cc_uid'] = array('in',$my_con_ids_arr);

            // 可见或不可见过滤
            $type_where = 'cc_uid in ('.$my_con_ids_arr_string.') and '.$uid.' = (case when connection_type=1 then '.$uid.' when connection_type=2 and cc_uid='.$uid.' then '.$uid.' when connection_type=3 and find_in_set('."$uid".',connection_visible_id)>0 then '.$uid.' when connection_type=4 and find_in_set('."$uid".',connection_visible_id)=0 then '.$uid.' else null end)';
            $find_res = M('connection_circle')->where($type_where)->field('cc_id as moments_id,cc_uid as moments_uid,cc_content as moments_content,cc_images as moments_images,create_time,position,lat,lng,type,circle_type,video_path,connection_type,connection_visible_id')->order('create_time desc')->limit($limit)->select();

            if(!$find_res){ return $this->json(-4,'没有更多动态了');}

            //查询这些uid的用户信息
            $temp_where['id'] = array('in',$my_con_ids_arr);
            $find_user_detail_info = M('user')->where($temp_where)->field('id,user_nickname,avatar,small_avatar,mobile')->select();

            //存取动态id
            $cc_ids_arr = array();
            foreach($find_res as $ky1 => $vl1){
                $cc_ids_arr[] = $vl1['moments_id'];
                //$find_res[$ky1]['create_time'] = date("Y-m-d H:i:s",$vl1['create_time']);
                if((time()-$vl1['create_time']) < 60){
                    $find_res[$ky1]['create_time'] = "刚刚";
                }else if((time()-$vl1['create_time']) >= 60 && (time()-$vl1['create_time']) < 60*60){
                    $find_res[$ky1]['create_time'] = ceil(((time()-$vl1['create_time'])/60))."分钟前";
                }else if((time()-$vl1['create_time']) >= 60*60 && (time()-$vl1['create_time']) < 60*60*24){
                    $find_res[$ky1]['create_time'] = ceil(((time()-$vl1['create_time'])/3600))."小时前";
                }else if((time()-$vl1['create_time']) >= 60*60*24 && (time()-$vl1['create_time']) < 60*60*24*2){
                    $find_res[$ky1]['create_time'] = "1天前";
                }else if((time()-$vl1['create_time']) >= 60*60*24*2 && (time()-$vl1['create_time']) < 60*60*24*3){
                    $find_res[$ky1]['create_time'] = "2天前";
                }else{
                    $find_res[$ky1]['create_time'] = date('Y-m-d',$vl1['create_time']);
                }
            }
            $where['moments_id'] = array('in',$cc_ids_arr);
            //根据动态id获取评论和点赞信息
            $find_comments = M('connection_comment')->where($where)->field('con_comment_id as mc_id,comment_content as mc_content,uid,cc_id as moments_id,comment_time,reply_uid,state')->order('comment_time asc')->select();
            //存放用户uid
            $info_uids_arr = array();
            foreach($find_comments as $ky2 => $vl2){
                $info_uids_arr[] = $vl2['uid'];
                $find_comments[$ky2]['comment_time'] = date("Y-m-d H:i:s",$vl2['comment_time']);
            }
            $where['upvote'] = 1;
            $find_upvote = M('connection_upvote')->where($where)->field('cu_id as upvote_id,uid,cc_id as moments_id,upvote')->select();
            foreach($find_upvote as $ky3=>$vl3){
                $info_uids_arr[] = $vl3['uid'];
            }
            $info_uids_arr = array_unique($info_uids_arr);

            if(!empty($info_uids_arr)){
                $temp_where['id'] = array('in',$info_uids_arr);
                $find_user_info = M('user')->where($temp_where)->field('id,user_nickname,avatar,small_avatar,mobile')->select();
                foreach($find_comments as $ky5 => $vl5){
                    foreach($find_user_info as $ky6 => $vl6){
                        if($vl5['uid'] == $vl6['id']){
                            $find_comments[$ky5]['name'] = $vl6['user_nickname'];
                            $find_comments[$ky5]['mobile'] = $vl6['mobile'];
                            $find_comments[$ky5]['avatar'] = $vl6['avatar'] ? $vl6['avatar'] : C('DEFAULT_AVATAR');
                        }
                        if($vl5['reply_uid']){
                            if($vl5['reply_uid'] == $vl6['id']){
                                $find_comments[$ky5]['reply_name'] = $vl6['user_nickname'];
                                $find_comments[$ky5]['reply_mobile'] = $vl6['mobile'];
                                $find_comments[$ky5]['reply_avatar'] = $vl6['avatar'] ? $vl6['avatar'] : C('DEFAULT_AVATAR');

                            }
                            $find_remark = M('connection_remark')->where('uid = '.$uid.' and f_uid = '.$vl5['reply_uid'])->find();
                            if($find_remark['remark']){
                                $find_comments[$ky5]['reply_name'] = $find_remark['remark'];
                            }
                        }else{
                            $find_comments[$ky5]['reply_name'] = "";
                        }
                    }
                    $find_remark = M('connection_remark')->where('uid = '.$uid.' and f_uid = '.$vl5['uid'])->find();
                    if($find_remark['remark']){
                        $find_comments[$ky5]['name'] = $find_remark['remark'];
                    }
                }

                foreach($find_upvote as $ky7 => $vl7){
                    foreach($find_user_info as $ky8 => $vl8){
                        if($vl7['uid'] == $vl8['id']){
                            if($vl8['small_avatar']){
                                $find_upvote[$ky7]['small_avatar'] = $this->path.$vl8['small_avatar'];
                            }
                            $find_upvote[$ky7]['mobile'] = $vl8['mobile'];
                        }
                    }
                    if(empty($find_upvote[$ky7]['small_avatar'])){
                        $find_upvote[$ky7]['small_avatar'] = C('DEFAULT_AVATAR');
                    }
                }

            }


            if($find_res){
                foreach($find_res as $k1 => $v1){
                    if($v1['moments_images']){

                        if($v1['video_path']){
                            $find_res[$k1]['video_img'] = $this->path.$v1['moments_images'];
                            $find_res[$k1]['video_path'] = $this->path.$v1['video_path'];
                        }else{
                            $find_res[$k1]['video_img'] = "";
                            $find_res[$k1]['video_path'] = "";
                        }

                        $temp_arr = explode(";",$v1['moments_images']);
                        for($i=0;$i<count($temp_arr);$i++){
                            $find_res[$k1]['images'][$i] = $this->path.$temp_arr[$i];
                        }
                        if($v1['video_path']){
                            $aaa = getimagesize($find_res[$k1]['video_img']);
                            $find_res[$k1]['video_img_width'] = "".$aaa[0];
                            $find_res[$k1]['video_img_height'] = "".$aaa[1];
                        }else{
                            $find_res[$k1]['video_img_width'] = "";
                            $find_res[$k1]['video_img_height'] = "";
                        }
                    }else{
                        $find_res[$k1]['images'] = array();
                    }

                    foreach($find_user_detail_info as $k4 => $v4){
                        if($v1['moments_uid'] == $v4['id']){
                            $find_res[$k1]['user_nickname'] = $v4['user_nickname'];
                            $find_res[$k1]['avatar'] = $v4['avatar'];
                            $find_res[$k1]['mobile'] = $v4['mobile'];
                            // 发表动态的好友信息
                            $find_remark = M('connection_remark')->where('uid = '.$uid.' and f_uid = '.$v1['moments_uid'])->find();
                            if($find_remark['remark']){
                                $find_res[$k1]['user_nickname'] = $find_remark['remark'];
                            }
                        }

                    }

                    foreach($find_comments as $k5 => $v5){
                        if($v5['moments_id'] == $v1['moments_id']){
                            $find_res[$k1]['comment_list'][] = $find_comments[$k5];
                        }
                    }

                    if(empty($find_res[$k1]['comment_list'])){
                        $find_res[$k1]['comment_list'] = array();
                    }

                    foreach($find_upvote as $k6 => $v6){
                        if($v6['moments_id'] == $v1['moments_id']){
                            $find_res[$k1]['upvote_list'][] = $find_upvote[$k6];
                            if($v6['uid'] == $uid && $v6['upvote'] == 1){
                                $find_res[$k1]['is_upvote'] = "1";
                            }
                        }
                        if(!$find_res[$k1]['is_upvote']){
                            $find_res[$k1]['is_upvote'] = "0";
                        }
                    }

                    if(empty($find_res[$k1]['upvote_list'])){
                        $find_res[$k1]['upvote_list'] = array();
                        $find_res[$k1]['is_upvote'] = "0";
                    }

                    unset($find_res[$k1]['moments_images']);

                }
                return $this->json(0,'成功',$find_res);
            }else{
                return $this->json(-3,'没有更多内容');
            }

        }else{

            //如果没有合约人脉
            $find_my_con_ids = M('user')->where("top_father = ".$find_user['top_father'])->field('id')->select();
            $my_con_ids_arr = array();//存放所有和自己有关系的人脉uid
            foreach($find_my_con_ids as $k2 => $v2){
                $my_con_ids_arr[] = $v2['id'];
            }

            //查询人脉圈权限表，排除掉数组中的被设置了权限的用户
            $find_authority_user = M('connection_authority')->where('uid = '.$uid." and (circle_state1 = 2 or circle_state2 = 2 or blacklist = 1)")->field('c_uid')->select();
            // 查询把你拉黑的用户
            $find_authority_user1 = M('connection_authority')->where('c_uid = '.$uid." and blacklist = 1")->field('uid')->select();

            if($find_authority_user){
                $authority_uids_arr = array();
                foreach($find_authority_user as $ky => $vl){
                    $authority_uids_arr[] = $vl['c_uid'];
                }
                $my_con_ids_arr = array_diff($my_con_ids_arr,$authority_uids_arr);//去掉重复的数组中的值
            }
            if($find_authority_user1) {
                $my_con_ids_arr = array_diff($my_con_ids_arr,array_column($find_authority_user1,'uid'));//去掉拉黑的id
            }

            $my_con_ids_arr_string = implode(',',$my_con_ids_arr);
             $where['cc_uid'] = array('in',$my_con_ids_arr);

            // 可见或不可见过滤
            $type_where = 'cc_uid in ('.$my_con_ids_arr_string.') and '.$uid.' = (case when connection_type=1 then '.$uid.' when connection_type=2 and cc_uid='.$uid.' then '.$uid.' when connection_type=3 and find_in_set('."$uid".',connection_visible_id)>0 then '.$uid.' when connection_type=4 and find_in_set('."$uid".',connection_visible_id)=0 then '.$uid.' else null end)';
            $find_res = M('connection_circle')->where($type_where)->field('cc_id as moments_id,cc_uid as moments_uid,cc_content as moments_content,cc_images as moments_images,create_time,position,lat,lng,type,circle_type,video_path,connection_type,connection_visible_id')->order('create_time desc')->limit($limit)->select();
            if(!$find_res){ return $this->json(-4,'没有更多动态了');}
            //查询这些uid的用户信息
            $temp_where['id'] = array('in',$my_con_ids_arr);
            $find_user_detail_info = M('user')->where($temp_where)->field('id,user_nickname,avatar,mobile')->select();

            //存取动态id
            $cc_ids_arr = array();
            foreach($find_res as $ky1 => $vl1){
                $cc_ids_arr[] = $vl1['moments_id'];
                $find_res[$ky1]['create_time'] = date("Y-m-d H:i:s",$vl1['create_time']);

            }
            $where['moments_id'] = array('in',$cc_ids_arr);
            //根据动态id获取评论和点赞信息
            $find_comments = M('connection_comment')->where($where)->field('con_comment_id as mc_id,comment_content as mc_content,uid,cc_id as moments_id,comment_time,reply_uid,state')->order('comment_time asc')->select();

            //存放用户uid
            $info_uids_arr = array();
            foreach($find_comments as $ky2 => $vl2){
                $info_uids_arr[] = $vl2['uid'];
                $find_comments[$ky2]['comment_time'] = date("Y-m-d H:i:s",$vl2['comment_time']);
            }
            $where['upvote'] = 1;
            $find_upvote = M('connection_upvote')->where($where)->field('cu_id as upvote_id,uid,cc_id as moments_id,upvote')->select();

            foreach($find_upvote as $ky3=>$vl3){
                $info_uids_arr[] = $vl3['uid'];
            }
            $info_uids_arr = array_unique($info_uids_arr);

            if(!empty($info_uids_arr)){
                $temp_where['id'] = array('in',$info_uids_arr);
                $find_user_info = M('user')->where($temp_where)->field('id,user_nickname,avatar,small_avatar,mobile')->select();
                foreach($find_comments as $ky5 => $vl5){
                    foreach($find_user_info as $ky6 => $vl6){
                        if($vl5['uid'] == $vl6['id']){
                            $find_comments[$ky5]['name'] = $vl6['user_nickname'];
                            $find_comments[$ky5]['mobile'] = $vl6['mobile'];
                            $find_comments[$ky5]['avatar'] = $vl6['avatar'] ? $vl6['avatar'] : C('DEFAULT_AVATAR');
                        }
                        if($vl5['reply_uid']){
                            if($vl5['reply_uid'] == $vl6['id']){
                                $find_comments[$ky5]['reply_name'] = $vl6['user_nickname'];
                                $find_comments[$ky5]['reply_mobile'] = $vl6['mobile'];
                                $find_comments[$ky5]['reply_avatar'] = $vl6['avatar'] ? $vl6['avatar'] : C('DEFAULT_AVATAR');
                            }

                            $find_remark = M('connection_remark')->where('uid = '.$uid.' and f_uid = '.$vl5['reply_uid'])->find();
                            if($find_remark['remark']){
                                $find_comments[$ky5]['reply_name'] = $find_remark['remark'];
                            }
                        }else{
                            $find_comments[$ky5]['reply_name'] = "";
                        }
                    }
                    $find_remark = M('connection_remark')->where('uid = '.$uid.' and f_uid = '.$vl5['uid'])->find();
                    if($find_remark['remark']){
                        $find_comments[$ky5]['name'] = $find_remark['remark'];
                    }
                }

                foreach($find_upvote as $ky7 => $vl7){
                    foreach($find_user_info as $ky8 => $vl8){
                        if($vl7['uid'] == $vl8['id']){
                            if($vl8['small_avatar']){
                                $find_upvote[$ky7]['small_avatar'] = $this->path.$vl8['small_avatar'];
                            }else{
                                $find_upvote[$ky7]['small_avatar'] =  C('DEFAULT_AVATAR');
                            }
                            $find_upvote[$ky7]['mobile'] = $vl8['mobile'];
                        }
                    }
                }

            }


            if($find_res){
                foreach($find_res as $k3 => $v3){
                    if($v3['video_path']){
                        $find_res[$k3]['video_img'] = $this->path.$v3['moments_images'];
                        $find_res[$k3]['video_path'] = $this->path.$v3['video_path'];

                        $aaa = getimagesize($find_res[$k3]['video_img']);
                        $find_res[$k3]['video_img_width'] = "".$aaa[0];
                        $find_res[$k3]['video_img_height'] = "".$aaa[1];
                    }else{
                        $find_res[$k3]['video_img'] = "";
                        $find_res[$k3]['video_path'] = "";
                        $find_res[$k3]['video_img_width'] = "";
                        $find_res[$k3]['video_img_height'] = "";
                    }

                    $temp_arr = explode(";",$v3['moments_images']);
                    for($i=0;$i<count($temp_arr);$i++){
                        $find_res[$k3]['images'][$i] = $this->path.$temp_arr[$i];
                    }

                    foreach($find_user_detail_info as $k4 => $v4){
                        if($v3['moments_uid'] == $v4['id']){
                            $find_res[$k3]['user_nickname'] = $v4['user_nickname'];
                            $find_res[$k3]['avatar'] = $v4['avatar'];
                            $find_res[$k3]['mobile'] = $v4['mobile'];
                        }
                        $find_remark = M('connection_remark')->where('uid = '.$uid.' and f_uid = '.$v3['moments_uid'])->find();
                        if($find_remark['remark']){
                            $find_res[$k3]['user_nickname'] = $find_remark['remark'];
                        }
                    }

                    foreach($find_comments as $k5 => $v5){
                        if($v5['moments_id'] == $v3['moments_id']){
                            $find_res[$k3]['comment_list'][] = $find_comments[$k5];
                        }
                    }

                    if(empty($find_res[$k3]['comment_list'])){
                        $find_res[$k3]['comment_list'] = array();
                    }

                    foreach($find_upvote as $k6 => $v6){
                        if($v6['moments_id'] == $v3['moments_id']){
                            $find_res[$k3]['upvote_list'][] = $find_upvote[$k6];
                            if($v6['uid'] == $uid && $v6['upvote'] == 1){
                                $find_res[$k3]['is_upvote'] = "1";
                            }
                        }
                        if(!$find_res[$k3]['is_upvote']){
                            $find_res[$k3]['is_upvote'] = "0";
                        }
                    }

                    if(empty($find_res[$k3]['upvote_list'])){
                        $find_res[$k3]['upvote_list'] = array();
                        $find_res[$k3]['is_upvote'] = "0";
                    }


                    unset($find_res[$k3]['moments_images']);
                }
                return $this->json(0,'成功',$find_res);
            }else{
                return $this->json(-3,'没有更多内容');
            }

        }

    }

    /**
     * 设置人脉圈权限
     * */
    public function set_con_authority(){
        $uid = I('uid');
        $c_uid = I('c_uid');//要设置权限的用户uid
        $circle_state1 = I('circle_state1');//不让朋友c_uid看我圈子 1-看 2-不看
        $circle_state2 = I('circle_state2');//我不看朋友c_uid圈子 1-看 2-不看
        $blacklist = I('blacklist');//我不看朋友c_uid圈子 1-看 2-不看

        if(!$uid || !$c_uid){
            return $this->json(-1,'缺少参数');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        if($circle_state1){

            //查找权限表中是否有该信息
            $find_state1 = M('connection_authority')->where("uid = ".$c_uid." and c_uid = ".$uid)->find();
            if($find_state1){
                $data['circle_state1'] = $circle_state1;
                $state1_res = M('connection_authority')->where("uid = ".$c_uid." and c_uid = ".$uid)->save($data);
                if(!$state1_res){
                    return $this->json(-3,'没做任何修改');
                }else{
                    return $this->json(0,'成功');
                }
            }else{
                $data['circle_state1'] = $circle_state1;
                $data['uid'] = $c_uid;
                $data['c_uid'] = $uid;
                $state1_res = M('connection_authority')->add($data);
                if(!$state1_res){
                    return $this->json(-4,'失败');
                }else{
                    return $this->json(0,'成功');
                }
            }
        }

        if($circle_state2){
            $find_state2 = M('connection_authority')->where("uid = ".$uid." and c_uid = ".$c_uid)->find();
            if($find_state2){
                $map['circle_state2'] = $circle_state2;
                $state2_res = M('connection_authority')->where("uid = ".$uid." and c_uid = ".$c_uid)->save($map);
                if(!$state2_res){
                    return $this->json(-2,'没做任何修改');
                }else{
                    return $this->json(0,'成功');
                }
            }else{
                $map['circle_state2'] = $circle_state2;
                $map['uid'] = $uid;
                $map['c_uid'] = $c_uid;
                $state2_res = M('connection_authority')->add($map);
                if(!$state2_res){
                    return $this->json(-4,'失败');
                }else{
                    return $this->json(0,'成功');
                }
            }
        }

        if($blacklist != '') {
            // 查询好友信息
            $f_user = M('user')->field('mobile')->find($c_uid);
            $blacklist_user = M('connection_authority')->where("uid = ".$uid." and c_uid = ".$c_uid)->find();
                $map['blacklist'] = $blacklist;
                $map['uid'] = $uid;
                $map['c_uid'] = $c_uid;
                $data['blacklist'] = $blacklist;
                // 设置黑名单
                if($blacklist == 1) {
                    $huanxin = new Easemob();
                    $huanxin->addUserForBlacklist($find_user['mobile'],array('usernames'=>array($f_user['mobile'])));
                    if($blacklist_user) {
                        $state1_res =  M()->execute("UPDATE `de_connection_authority` SET blacklist = 1 WHERE  uid= ".$uid." and c_uid =".$c_uid);
                    } else {
                        $state1_res = M('connection_authority')->where("uid = ".$uid." and c_uid = ".$c_uid)->add($map);
                    }
                } elseif($blacklist == 0) {
                    $huanxin = new Easemob();
                    $huanxin->deleteUserFromBlacklist($find_user['mobile'],$f_user['mobile']);
                    if($blacklist_user) {
                        $state1_res =  M()->execute("UPDATE `de_connection_authority` SET blacklist = 0 WHERE  uid= ".$uid." and c_uid =".$c_uid);
                    } else {
                        $state1_res = M('connection_authority')->where("uid = ".$uid." and c_uid = ".$c_uid)->add($map);
                    }
                }
            if($state1_res) {
                return $this->json(0,'成功', $blacklist);
            } else {
                return $this->json(-3,'失败');
            }
        }

    }


    /**
     * 通过人脉圈动态id获取评论信息（暂时不做共同好友才能看见评论内容的形式）
     * */
    public function con_com_lists(){
        $cc_id = I('cc_id');//人脉圈动态id
        if(!$cc_id){
            return $this->json(-1,'缺少参数');
        }

        //查询评论
        $find_com = M('connection_comment')->where('cc_id = '.$cc_id)->select();
        if(!$find_com){
            return $this->json(-2,'还没有评论');
        }

        //存放评论用户的id
        $com_uids_arr = array();
        foreach($find_com as $key => $val){
            $com_uids_arr[] = $val['uid'];
        }
        $com_uids_arr = array_unique($com_uids_arr);

        //查询参与评论人的用户信息
        $where['id'] = array('in',$com_uids_arr);
        $find_user_info = M('user')->where($where)->field('id,user_nickname')->select();
        foreach($find_com as $k1 => $v1){
            foreach($find_user_info as $k2 => $v2){
                if($v1['uid'] == $v2['id']){
                    $find_com[$k1]['name'] = $v2['user_nickname'];//评论者昵称
                }

                if($v1['reply_uid'] == $v2['id']){
                    $find_com[$k1]['reply_name'] = $v2['user_nickname'];//被评论者昵称
                }
                if(!$v1['reply_uid']){
                    $find_com[$k1]['reply_name'] = "";//被评论者昵称
                }
            }
        }

        return $this->json(0,'成功',$find_com);

    }

    /**
     * 通过人脉圈动态id获取点赞信息
     * */
    public function con_upvote_lists(){
        $cc_id = I('cc_id');//人脉圈动态id
        if(!$cc_id){
            return $this->json(-1,'缺少参数');
        }

        //查询点赞信息
        $find_upvote_info = M('connection_upvote')->where('cc_id = '.$cc_id." and upvote = 1")->select();
        if(!$find_upvote_info){
            return $this->json(-2,'没有点赞信息');
        }

        //存放点赞者的用户id
        $upvote_uids_arr = array();
        foreach($find_upvote_info as $key => $val){
            $upvote_uids_arr[] = $val['uid'];
        }

        //查找点赞人用户信息
        $where['id'] = array('in',$upvote_uids_arr);
        $find_user_info = M('user')->where($where)->field('id,user_nickname,small_avatar')->select();

        foreach($find_upvote_info as $k1 => $v1){
            foreach($find_user_info as $k2 => $v2){
                if($v1['uid'] == $v2['id']){
                    $find_upvote_info[$k1]['user_nickname'] = $v2['user_nickname'];
                    if($v2['small_avatar']){
                        $find_upvote_info[$k1]['small_avatar'] = $this->path.$v2['small_avatar'];
                    }else{
                        $find_upvote_info[$k1]['small_avatar'] = C('DEFAULT_AVATAR');

                    }
                }
            }
        }

        return $this->json(0,'成功',$find_upvote_info);

    }

    /**
     * 我发布的人脉圈动态 或者 某个人脉发布过的人脉圈动态（某一用户的的动态列表）
     * */
    public function my_con_lists(){
        $uid = I('uid');//有分页 page
        $c_uid = I('c_uid');//传两个id是判断是否设置了朋友圈权限
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        if($uid && !$c_uid){//查看我自己的信息

            //查找用户信息
            $find_user = M('user')->where('id = '.$uid)->find();
            if(!$find_user || $find_user['state'] == 1){
                return $this->json(-2,'用户信息不存在或已被封号');
            }

            //查找自己在人脉圈中发布的动态信息
            $find_my_circle = M('connection_circle')->where('cc_uid = '.$uid)->order("create_time desc")->limit($limit)->select();
            if(!$find_my_circle){
                return $this->json(-3,'没有更多的信息');
            }
            foreach($find_my_circle as $key => $val){
                $temp_arr = explode(";",$val['cc_images']);
                for($i=0;$i<count($temp_arr);$i++){
                    $find_my_circle[$key]['images'][$i] = $this->path.$temp_arr[$i];
                }
                unset($find_my_circle[$key]['cc_images']);
            }
            return $this->json(0,'成功',$find_my_circle);

        }

        if($uid && $c_uid){//你查看别的人脉发布过的动态信息

            //判断对方是否设置了朋友圈权限
            $find_con_auth = M('connection_authority')->where('uid = '.$uid.' and c_uid = '.$c_uid)->find();
            if($find_con_auth){
                //判断是否对你设置了权限
                if($find_con_auth['circle_state1'] == 2){
                    return $this->json(-4,'对方设置了好友权限');
                }
            }

            //查找 c_uid 发布过的人脉圈动态
            $where = 'cc_uid = '.$c_uid.' and '.$c_uid.' = (case when connection_type=1 then '.$c_uid.' when connection_type=2 then null when connection_type=3 and find_in_set('."$c_uid".',connection_visible_id)>0 then '.$c_uid.' when connection_type=4 and find_in_set('."$c_uid".',connection_visible_id)=0 then '.$uid.' else null end)';
            $find_other_circle = M('connection_circle')->where($where)->order("create_time desc")->limit($limit)->select();
            if(!$find_other_circle){
                return $this->json(-3,'没有更多的信息');
            }
            foreach($find_other_circle as $k => $v){
                $temp_arr = explode(";",$v['cc_images']);
                for($i=0;$i<count($temp_arr);$i++){
                    $find_other_circle[$k]['images'][$i] = $this->path.$temp_arr[$i];
                }
                unset($find_other_circle[$k]['cc_images']);
            }
            return $this->json(0,'成功',$find_other_circle);

        }

    }


    /**
     *人脉圈中我的消息接口
     * */


    /**
     * 不让所有人查看我发布的动态（这个考虑要不要和朋友圈的做成一个接口）
     * */

    /**
     *人脉圈中的备注
     * */
    public function connect_remark()
    {
        $uid = I('uid');
        $f_uid = I('f_uid');
        $f_mobile = I('f_mobile');
        $remark = I('remark');


        if (!$uid) {
            return $this->json(-1, '缺少参数！');
        }

        if(!$f_uid && !$f_mobile){
            return $this->json(-1, '缺少参数！');
        }

        if($f_uid){
            if($uid == $f_uid)
            {
                return $this->json(-2,'不能为自己添加备注！');
            }
            $res = M('connection_remark')->where("uid = ".$uid." AND f_uid = ".$f_uid)->find();
            if($res){
                $data['remark'] = $remark;
                $data['create_time'] = time();
                $info = M('connection_remark')->where("uid = ".$uid." AND f_uid = ".$f_uid)->save($data);
            }else{
                $data['uid'] = $uid;
                $data['f_uid'] = $f_uid;
                $data['remark'] = $remark;
                $data['create_time'] = time();
                $info = M('connection_remark')->add($data);
            }
            if($info){
                $res1 = M('connection_remark')->where("uid = ".$uid." AND f_uid = ".$f_uid)->find();
                return $this->json(0,'备注成功！',$res1);
            }
        }

        if($f_mobile){
            $find_my_info = M('user')->where("mobile = '".$f_mobile."'")->find();
            $f_uid = $find_my_info['id'];
            if($uid == $f_uid)
            {
                return $this->json(-2,'不能为自己添加备注！');
            }
            $res = M('connection_remark')->where("uid = ".$uid." AND f_uid = ".$f_uid)->find();
            if($res){
                $data['remark'] = $remark;
                $data['create_time'] = time();
                $info = M('connection_remark')->where("uid = ".$uid." AND f_uid = ".$f_uid)->save($data);
            }else{
                $data['uid'] = $uid;
                $data['f_uid'] = $f_uid;
                $data['remark'] = $remark;
                $data['create_time'] = time();
                $info = M('connection_remark')->add($data);
            }
            if($info){
                $res1 = M('connection_remark')->where("uid = ".$uid." AND f_uid = ".$f_uid)->find();
                return $this->json(0,'备注成功！',$res1);
            }
        }

    }

    /**
     * 人脉设置（只有顶级人脉才有此功能）
     * */
    public function connection_set(){
        $uid = I('uid');
        $conn_type = I('conn_type');//1 开放人脉 2 贵族人脉
        $pur_price = I('pur_price');//合约购买金额
        $day_num = I('day_num');//合约购买天数
        $operator = I('operator');//1、开启；2、关闭
        if(!$uid || !$conn_type || !$operator){
            return $this->json(-1, '缺少参数！');
        }

        if($conn_type == 1){
            if($operator == 1){
                $set_conn_type = M('user')->where("id = ".$uid)->setField('conn_type',1);
                //查找设置后的用户信息
                $find_user_info = M('user')->where("id = ".$uid)->find();
                //声明一个返回值数组
                $res = array();
                $res['conn_type'] = $find_user_info['conn_type'];
                $res['is_forbid'] = "0";//是否禁用此功能，1、禁用；0、不禁用
                $res['operator'] = 1;
                return $this->json(0, '操作成功',$res);
            }else if($operator == 2){
                $set_conn_type = M('user')->where("id = ".$uid)->setField('conn_type',0);
                //查找设置后的用户信息
                $find_user_info = M('user')->where("id = ".$uid)->find();
                //声明一个返回值数组
                $res = array();
                $res['conn_type'] = $find_user_info['conn_type'];
                $res['is_forbid'] = "0";//是否禁用此功能，1、禁用；0、不禁用
                $res['operator'] = 2;
                return $this->json(0, '操作成功',$res);
            }

        }else if($conn_type == 2){

            if(!$pur_price || !$day_num){
                return $this->json(-1, '缺少参数！');
            }

            //接受金额和天数
            if($operator == 1){
                $data['pur_price'] = $pur_price;
                $data['pur_contract_time'] = $day_num*3600*24+time();
                $data['day_num'] = $day_num;
                $data['conn_type'] = 2;
                $set_conn_type = M('user')->where("id = ".$uid)->save($data);
//                if(!$set_conn_type){
//                    return $this->json(-3, '修改失败');
//                }
                //查找设置后的用户信息
                $find_user_info = M('user')->where("id = ".$uid)->find();
                //声明一个返回值数组
                $res = array();
                $res['conn_type'] = $find_user_info['conn_type'];
                if($find_user_info['pur_mobile'] && ($data['pur_contract_time'] < time())){
                    $res['is_forbid'] = "1";//是否禁用此功能，1、禁用；0、不禁用
                }else{
                    $res['is_forbid'] = "0";
                }
                $res['operator'] = 1;
                return $this->json(0, '操作成功',$res);
            }else if($operator == 2){
                $set_conn_type = M('user')->where("id = ".$uid)->setField('conn_type',0);
                //查找设置后的用户信息
                $find_user_info = M('user')->where("id = ".$uid)->find();
                //声明一个返回值数组
                $res = array();
                $res['conn_type'] = $find_user_info['conn_type'];
                if($find_user_info['pur_mobile']){
                    $res['is_forbid'] = "1";//是否禁用此功能，1、禁用；0、不禁用
                }else{
                    $res['is_forbid'] = "0";
                }
                $res['operator'] = 2;
                return $this->json(0, '操作成功',$res);
            }
        }else{
            return $this->json(-2, '参数错误');
        }

    }

    //查找人脉
    public function find_connection()
    {
        $uid = I('uid');
        $conn_type = I('conn_type');
        $content = I('content');
        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        if(!$uid | !$conn_type)
        {
            return $this->json(-1,'参数不完整！');
        }
        if($conn_type == 1)
        {
            if($content)
            {
                $where['mobile|user_nickname'] = $content;
            }
            $where['conn_type'] = 1;
            $field ='id,user_nickname,avatar,signature,mobile,pur_price,pur_mobile,conn_type';
            $where['pur_mobile'] = "";
            $connection_info = M('user')->where($where)->field($field)->limit($limit)->select();
        }
        if($conn_type ==2)
        {
            if($content)
            {
                $where['mobile|user_nickname'] = $content;
            }
            $where['conn_type'] = 2;
            $field ='id,user_nickname,avatar,signature,mobile,pur_price,pur_mobile,conn_type';
            $where['pur_mobile'] = "";
            $connection_info = M('user')->where($where)->field($field)->limit($limit)->select();

        }
        if($connection_info){

            //收集用户id
            $user_ids = array();
            foreach($connection_info as $k => $v){
                $user_ids[]=$v['id'];
                $connection_info[$k]['avatar'] = $v['avatar'] ? $v['avatar'] : C('DEFAULT_AVATAR');
            }
            $temp['f_uid'] = array('in',$user_ids);
            $temp['uid'] = $uid;
            //查询和自己有好友关系的
            $find_my_friends = M('user_friends')->where($temp)->select();
            //var_dump($find_my_friends);
            if($find_my_friends){
                foreach($connection_info as $k1 => $v1){
                    foreach($find_my_friends as $k2 => $v2){
                        if($v1['id'] == $v2['f_uid']){
                            $connection_info[$k1]['is_friend'] = "1";
                        }else{
                            $connection_info[$k1]['is_friend'] = "0";
                        }
                    }
                }
            }else{
                foreach($connection_info as $k1 => $v1){
                    $connection_info[$k1]['is_friend'] = "0";
                }
            }


            return $this->json(0,'查询成功',$connection_info);
        }else{
            return $this->json(-2,'没有贵族人脉');
        }
    }

    /**
     * 邀请开放人脉接口（点击同意的时候使用）
     * */
    public function invite_free_conn(){
        $my_mobile = I('mobile');//
        $mobile = I('my_mobile');//
        if(!$my_mobile || !$mobile){
            return $this->json(-1,'参数不完整！');
        }

        //查找本人信息
        $find_myself_info = M('user')->where("mobile = '".$my_mobile."'")->find();
        if(!$find_myself_info){
            return $this->json(-2,'用户信息不存在');
        }

        //查找邀请人信息
        $find_invite_info = M('user')->where("mobile = '".$mobile."' and conn_type = 1")->find();

        if(!$find_invite_info){
            return $this->json(-3,'合约人信息不存在或对方取消了开放人脉。');
        }

        //判断是否是顶级父类
        if($find_invite_info['invitation_code']){
            return $this->json(-4,'邀请的人不能是邀请人脉');
        }

        //邀请时判断是否已经被别人邀请
        if($find_invite_info['pur_mobile'] && ($find_invite_info['pur_time']-time()) <= 30*3600*24){
            return $this->json(-5,'该用户已被邀请');
        }

        $data['pur_mobile'] = $find_myself_info['mobile'];
        $data['pur_top_father'] = $find_myself_info['top_father'];
        $data['pur_time'] = time();

        $pur_res = M('user')->where("mobile = '".$mobile."'")->save($data);
        if($pur_res){
            return $this->json(0,'成功');
        }else{
            return $this->json(-6,'添加失败');
        }

    }



    /**
     * 合约人脉解约接口
     * */
    public function remove_contract_conn(){
        $my_mobile = I('my_mobile');//本人手机号
        $con_mobile = I('con_mobile');//合约人手机号

        //查询自己的信息
        $find_my_info = M('user')->where("mobile = '".$my_mobile."'")->find();
        if(!$find_my_info){
            return $this->json(-1,'用户信息不存在');
        }

        //查询合约人信息
        $find_con_info = M('user')->where("mobile = '".$con_mobile."'")->find();
        if(!$find_con_info){
            return $this->json(-2,'此合约人脉不存在');
        }

        //查询自己是否有此合约人脉
        $find_pur_info = M('user')->where("pur_mobile = '".$my_mobile."' and mobile = '".$con_mobile."'")->find();
        if(!$find_pur_info){
            return $this->json(-3,'此人脉不是您的合约人脉');
        }

        //开始解除合约
        $data['pur_time'] = "";
        $data['pur_mobile'] = "";
        $data['pur_top_father'] = "";
        $data['pur_contract_time'] = "";
        $remove_res = M('user')->where("mobile = '".$con_mobile."'")->save($data);
        if(!$remove_res){
            return $this->json(-4,'解约失败');
        }

        return $this->json(0,'解约成功');

    }

    /**
     *返回人脉设置信息
     * */
    public function return_conn_info(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        //查询用户信息
        $find_user_info = M('user')->where('id = '.$uid)->find();

        if(!$find_user_info){
            return $this->json(-2,'用户不存在');
        }

        if(($find_user_info['conn_type'] == 2 || $find_user_info['conn_type'] == 1) && !empty($find_user_info['pur_mobile'])){
            if($find_user_info['pur_contract_time'] > time()){
                $res['is_forbid'] = "0";
            }else{
                $res['is_forbid'] = "1";
            }
        }else{
            $res['is_forbid'] = "0";
        }

        $res['conn_type'] = $find_user_info['conn_type'];
        $res['pur_price'] = $find_user_info['pur_price'];
        $res['day_num'] = $find_user_info['day_num'];

        if($find_user_info['conn_type'] == 2 || $find_user_info['conn_type'] == 1){
            $res['operator'] = "1";
        }else{
            $res['operator'] = "0";
        }

        return $this->json(0,'成功',$res);

    }

    /**
     * 邀请开放人脉接口（邀请申请的时候使用）
     * */
    public function send_free_conn(){
        $my_mobile = I('my_mobile');//用户本人手机号
        $mobile = I('mobile');//被邀请人的手机号
        if(!$my_mobile || !$mobile){
            return $this->json(-1,'参数不完整！');
        }

        //查找本人信息
        $find_myself_info = M('user')->where("mobile = '".$my_mobile."'")->find();
        if(!$find_myself_info){
            return $this->json(-2,'用户信息不存在');
        }

        //查找邀请人信息
        $find_invite_info = M('user')->where("mobile = '".$mobile."' and conn_type = 1")->find();
        if(!$find_invite_info){
            return $this->json(-3,'合约人信息不存在，请刷新再试。');
        }

        //判断两人是否是好友
        $find_friends = M('user_friends')->where("uid = ".$find_myself_info['id']." and f_uid = ".$find_invite_info['id'])->find();
        if($find_friends){
            return $this->json(-4,'不能邀请好友');
        }

        //判断这个人是不是你自己
        if($my_mobile == $mobile){
            return $this->json(-5,'不能邀请自己');
        }

        //判断这个人是不是和你有人脉关系
        if($find_myself_info['top_father'] == $find_invite_info['top_father']){
            return $this->json(-6,'你们已经有人脉关系了');
        }
        if($find_myself_info['top_father'] == $find_invite_info['pur_top_father']){
            return $this->json(-6,'你们已经有人脉关系了');
        }

        //一天只能发送一次邀请
        $send_info = M('free_info_record')->where("mobile = '".$my_mobile."'")->find();
        if($send_info){
            if((time()-$send_info['time']) < 24*3600){
                return $this->json(-7,'24小时之内只能发送一次');
            }
        }

        $temp_mobile = $my_mobile;
        //给邀请的人发信息
        $ease = new Easemob();
        $mobile = explode(",",$mobile);
        if($find_myself_info['user_nickname']){
            $content = $find_myself_info['user_nickname']."的人脉邀请消息";
        }else{
            $content = $my_mobile."的人脉邀请消息";
        }

        $ext = array('type'=>'1','mobile'=>$my_mobile,'name'=>$find_myself_info['user_nickname'],'mobile'=>$find_myself_info['mobile'],'content'=>$content);
        $res = $ease->sendText('人脉邀请'.$my_mobile,'users',$mobile,$content,$ext);
        //var_dump($res);

        if($send_info){
            //$data['time'] = time();
            //M('free_info_record')->where("mobile = '".$mobile."'")->save($data);
            M('free_info_record')->where("mobile = '".$temp_mobile."'")->setField('time',time());
        }else{
            $data['time'] = time();
            $data['mobile'] = $temp_mobile;
            M('free_info_record')->add($data);
        }
        return $this->json(0,'邀请已发送');

    }

    /**
     * 获取人脉详情
     * */
    public function conn_particulars(){
        $my_mobile = I('my_mobile');//用户本人手机号
        $mobile = I('mobile');//要查找的人脉的手机号

        if(!$my_mobile || !$mobile){
            return $this->json(-1,'参数不完整！');
        }

        //查询自己的信息
        $find_my_info = M('user')->where("mobile = '".$my_mobile."'")->find();
        if(!$find_my_info){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        //查询被查找人的信息
        $field = 'id,mobile,user_nickname,avatar,sex,signature,create_time,qrcode,address,coins,province,city,area,top_father,pur_top_father';
        $find_conn_info = M('user')->where("mobile = '".$mobile."'")->field($field)->find();
        if(!$find_conn_info){
            return $this->json(-3,'您所查找的用户信息不存在');
        }

        //查找用户备注
        $find_conn_remark = M('connection_remark')->where("uid = ".$find_my_info['id']." and f_uid = ".$find_conn_info['id'])->find();
        if(!$find_conn_remark){
            $find_conn_info['remark'] = "";
            $find_conn_info['is_friend'] = "0";
        }else{
            $find_conn_info['remark'] = $find_conn_remark['remark'];
            $find_conn_info['is_friend'] = "1";
        }

        //判断是否是自己的人脉
        if($find_my_info['top_father'] == $find_conn_info['top_father'] || $find_my_info['top_father'] == $find_conn_info['pur_top_father'] || ($find_my_info['mobile'] == $find_conn_info['pur_mobile']) || ($find_my_info['pur_mobile'] == $find_conn_info['mobile'])){
            $find_conn_info['is_connection'] = "1";
        }else{
            $find_conn_info['is_connection'] = "0";
        }
        // 人脉圈权限
        $connection = M('connection_authority')->where("uid = ".$find_conn_info['id']." and c_uid = ".$find_my_info['id'])->find();
        $connection1 = M('connection_authority')->where("uid = ".$find_my_info['id']." and c_uid = ".$find_conn_info['id'])->find();
        $find_conn_info['blacklist'] = $connection1['blacklist'];
        $find_conn_info['circle_state1'] = $connection['circle_state1'];
        $find_conn_info['circle_state2'] = $connection1['circle_state2'];

        //查找人脉圈发布的动态
        $find_conn_moments = M('connection_circle')->where("cc_uid = ".$find_conn_info['id']." and cc_images != ''")->order('create_time desc')->select();
        if($find_conn_moments){
            $img_arr = array();
            foreach($find_conn_moments as $key => $val){
                $temp_img = explode(";",$val['cc_images']);
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
                $find_conn_info['moments_images'][] = $img_arr[$i];
            }
        }else{
            $find_conn_info['moments_images'] = array();
        }
        $find_conn_info['debo_code'] = substr(md5($find_conn_info['mobile']),0,8);
        $find_conn_info['avatar'] = $find_conn_info['avatar'] ? $find_conn_info['avatar'] : C('DEFAULT_AVATAR');
        return $this->json(0,'成功',$find_conn_info);

    }

    /**
     * 根据key删除数组中指定元素
     * @param  array  $arr  数组
     * @param  string/int  $key  键（key）
     */
    function array_remove_by_key($arr, $key){
        if(!array_key_exists($key, $arr)){
            return $arr;
        }
        $keys = array_keys($arr);
        $index = array_search($key, $keys);
        if($index !== FALSE){
            array_splice($arr, $index, 1);
        }
        return $arr;
    }

}

?>