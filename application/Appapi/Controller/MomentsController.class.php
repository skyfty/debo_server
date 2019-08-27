<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/10 0010
 * Time: 上午 10:17
 */
namespace Appapi\Controller;

use Common\Controller\ApibaseController;
use Common\Common\Easemob;

class MomentsController extends ApibaseController{

    private $path = 'http://debo.shangtongyuntian.com';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 修改朋友圈背景图
     * */
    public function modify_moments_bg(){
        $uid = I("uid");
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }
        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user){
            return $this->json(-2,'没有获取到该用户信息');
        }

        if(!empty($_FILES)){
            $image = $this->uploads('moments_bg/moments_bg_'.$uid);
            $data['moments_background_img'] = $image;

            $upload_res = M('user')->where('id = '.$uid)->save($data);
            if($upload_res){
                $find_bg = M('user')->where('id = '.$uid)->field('moments_background_img')->find();
                $find_bg['moments_background_img'] = $this->path.$find_bg['moments_background_img'];
                return $this->json(0,'上传成功',$find_bg);
            }else{
                return $this->json(-3,'上传失败');
            }
        }
    }

    /**
     *发布动态
     * */
    public function publish_talking(){
        $uid = I('uid');
        $moments_content = trim(I('moments_content'));
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

        if($moments_content){
            $data['moments_content'] = $moments_content;
        }
        if(!empty($_FILES)){
            $image = $this->uploads('moments_pub_talking/talking_'.$uid);
            $data['moments_images'] = $image;
        }
        if(empty($_FILES) && !$data['moments_content']){
            return $this->json(-3,'发布内容不能为空');
        }
        $data['moments_uid'] = $uid;
        $data['create_time'] = time();

        $publish_res = M('moments')->add($data);
        if($publish_res){
            return $this->json(0,'发布成功');
        }else{
            return $this->json(-4,'发布失败');
        }

    }

    /**
     *朋友圈评论
     * */
    public function moments_comment(){
        $uid = I('uid');//发布评论者uid
        $moments_id = I('moments_id');//朋友圈的动态id
        $mc_content = I('mc_content');//朋友圈评论内容
        $reply_uid = I('reply_uid');//回复人uid
        if(!$uid || !$moments_id || !$mc_content){
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
        $data['moments_id'] = $moments_id;
        $data['mc_content'] = $mc_content;
        $data['comment_time'] = time();//评论时间
        if($reply_uid){
            $data['reply_uid'] = $reply_uid;
        }

        $com_res = M('moments_comment')->add($data);
        if($com_res){
            $find_res = M('moments_comment')->where('mc_id = '.$com_res)->find();
            $find_res['comment_time'] = date('Y-m-d H:i:s',$find_res['comment_time']);
            $find_user_info1 = M('user')->where('id = '.$find_res['uid'])->field('id,user_nickname,mobile')->find();
            $find_user_info2 = M('user')->where('id = '.$find_res['reply_uid'])->field('id,user_nickname,mobile')->find();
            $find_res['name'] = $find_user_info1['user_nickname'];
            $find_res['mobile'] = $find_user_info1['mobile'];
            $find_res['reply_name'] = $find_user_info2['user_nickname'];
            $find_res['reply_mobile'] = $find_user_info2['mobile'];
            //查找朋友备注
            if($reply_uid){
                $find_fri = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$reply_uid)->find();
                if($find_fri['remark']){
                    $find_res['reply_name'] = $find_fri['remark'];
                }
            }

            return $this->json(0,'评论成功',$find_res);
        }else{
            return $this->json(-3,'评论失败');
        }

    }

    /**
     *朋友圈点赞
     * */
    public function moments_upvote(){
        $uid = I('uid');
        $moments_id = I('moments_id');//朋友圈的动态id
        $upvote = I('upvote');//点赞与否 1 已点赞 0 未点赞

        if(!$uid || !$moments_id){
            return $this->json(-1,'缺少参数');
        }

        //查找用户信息
        $find_user = M('user')->where('id = '.$uid)->find();
        if(!$find_user || $find_user['state'] == 1){
            return $this->json(-2,'用户信息不存在或已被封号');
        }

        //查找点赞表中是否有你的记录
        $find_moments_upvote = M('moments_upvote')->where("uid = ".$uid." and moments_id = ".$moments_id)->find();
        if($find_moments_upvote){
            if($find_moments_upvote['upvote'] == $upvote){
                return $this->json(-3,'状态未发生改变');
            }
            $upvote_res = M('moments_upvote')->where("uid = ".$uid." and moments_id = ".$moments_id)->setField('upvote',$upvote);
            $upvote_res = M('moments_upvote')->where("uid = ".$uid." and moments_id = ".$moments_id)->setField('upvote_time',time());
            if($upvote_res){
                $find_res = M('moments_upvote')->where("uid = ".$uid." and moments_id = ".$moments_id)->find();
                $find_user_res = M('user')->where('id = '.$find_res['uid'])->field('small_avatar')->find();
                $find_res['small_avatar'] = $this->path.$find_user_res['small_avatar'];

                //返回这条动态的点赞列表
//                $find_upvote_list = M('moments_upvote')->where("moments_id = ".$moments_id)->select();
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
            $data['moments_id'] = $moments_id;
            $data['upvote'] = $upvote;
            $data['upvote_time'] = time();
            $upvote_res = M('moments_upvote')->add($data);
            if($upvote_res){
                $find_res = M('moments_upvote')->where('upvote_id = '.$upvote_res)->find();
                $find_user_res = M('user')->where('id = '.$find_res['uid'])->field('small_avatar')->find();
                $find_res['small_avatar'] = $this->path.$find_user_res['small_avatar'];

                //返回这条动态的点赞列表
//                $find_upvote_list = M('moments_upvote')->where("moments_id = ".$moments_id)->select();
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
        }


    }

    /**
     *朋友圈列表（记住可能会屏蔽某些动态）
     * */
    public function moments_lists(){
        $uid = I('uid');//有分页 page
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

        //查询好友的uid
        $find_friends = M('user_friends')->where("uid = ".$uid." and circle_state1 = 1 and circle_state2 = 1 and blacklist = 0")->select();

        $find_my_pub = M('moments')->where('moments_uid = '.$uid)->count();//查询自己是否发布过动态
        if(!$find_friends && !$find_my_pub){
            return $this->json(-3,'没有动态');
        }

        //存放好友的uid
        if(!$find_friends && $find_my_pub){//如果好友不存在但是自己发布过动态
            $f_uids_str = $uid;
        }else{
            $f_uids_arr = array();
            foreach($find_friends as $k1 => $v1){
                $f_uids_arr[] = $v1['f_uid'];
            }
            $f_uids_str = implode(",",$f_uids_arr);
            $f_uids_str = $f_uids_str.",".$uid;//拼接上自己的uid，不然不显示自己发布的动态。
        }
        // 去除把你拉黑哪个人的id
        $find_blacklist = M('user_friends')->field('uid')->where('f_uid = '.$uid. ' and blacklist = 1')->select();
        if($find_blacklist) {
            $f_uids_str = implode(',' ,array_diff(explode(',', $f_uids_str),array_column($find_blacklist,'uid')));
        }

        //通过好友的uid查找该用户发布的朋友圈动态
        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;
      //  $find_moments = M('moments')->where('moments_uid in ('.$f_uids_str.')')->order('create_time desc')->limit($limit)->select();
        $where = 'moments_uid in ('.$f_uids_str.') and '.$uid.'= (case when moments_type=1 then '.$uid.' when moments_type=2 and moments_uid='.$uid.' then '.$uid.' when moments_type=3 and find_in_set('."$uid".',moments_visible_id)>0 then '.$uid.' when moments_type=4 and find_in_set('."$uid".',moments_visible_id)=0 then '.$uid.' else null end)';
        $find_moments = M('moments')->where($where)->order('create_time desc')->limit($limit)->select();

        if(!$find_moments){ return $this->json(-4,'没有更多动态了');}

//        $im = $this->path.$find_moments[1]['moments_images'];
//        $aaa = getimagesize($im);
//        print_r($aaa[0].$aaa[1]);exit;

        //查找好友信息
        $find_fri_info = M('user')->where('id in ('.$f_uids_str.')')->select();

        //存放动态id，方便获取用户评论和点赞信息。
        $moments_ids_arr = array();
        foreach($find_moments as $ky => $vl){
            $moments_ids_arr[] = $vl['moments_id'];
            //$find_moments[$ky]['create_time'] = date('Y-m-d H:i:s',$vl['create_time']);
            if((time()-$vl['create_time']) < 60){
                $find_moments[$ky]['create_time'] = "刚刚";
            }else if((time()-$vl['create_time']) >= 60 && (time()-$vl['create_time']) < 60*60){
                $find_moments[$ky]['create_time'] = ceil(((time()-$vl['create_time'])/60))."分钟前";
            }else if((time()-$vl['create_time']) >= 60*60 && (time()-$vl['create_time']) < 60*60*24){
                $find_moments[$ky]['create_time'] = ceil(((time()-$vl['create_time'])/3600))."小时前";
            }else if((time()-$vl['create_time']) >= 60*60*24 && (time()-$vl['create_time']) < 60*60*24*2){
                $find_moments[$ky]['create_time'] = "1天前";
            }else if((time()-$vl['create_time']) >= 60*60*24*2 && (time()-$vl['create_time']) < 60*60*24*3){
                $find_moments[$ky]['create_time'] = "2天前";
            }else{
                $find_moments[$ky]['create_time'] = date('Y-m-d',$vl['create_time']);
            }
        }
        $temp_moments_id['moments_id'] = array('in',$moments_ids_arr);
        //根据 moments_id 查询评论和点赞
        $find_comment = M('moments_comment')->where($temp_moments_id)->select();
        //存放用户id
        foreach($find_comment as $ky2 => $vl2){
            $comment_uids_arr[]=$vl2['uid'];
            $find_comment[$ky2]['comment_time'] = date("Y-m-d H:i:s",$vl2['comment_time']);
        }
        $temp_moments_id['upvote'] = 1;
        $find_upvote = M('moments_upvote')->where($temp_moments_id)->select();
        foreach($find_upvote as $ky3 => $vl3){
            $comment_uids_arr[]=$vl3['uid'];
        }
        $comment_uids_arr = array_unique($comment_uids_arr);
        //var_dump($comment_uids_arr);
        //找到评论和点赞的用户uid
        if(!empty($comment_uids_arr)){
            $temp_str_uids = implode(",",$comment_uids_arr);
            $find_user_info = M('user')->where('id in ('.$temp_str_uids.')')->field('id,user_nickname,avatar,small_avatar,mobile')->select();

            foreach($find_comment as $ky5 => $vl5){
                foreach($find_user_info as $ky6 => $vl6){
                    if($vl5['uid'] == $vl6['id']){
                        $find_comment[$ky5]['name'] = $vl6['user_nickname'];
                        $find_comment[$ky5]['mobile'] = $vl6['mobile'];
                        // 评论用户头像
                        if(empty($vl6['avatar'])) {
                            $find_comment[$ky5]['avatar'] = C('DEFAULT_AVATAR');
                        } else {
                            $find_comment[$ky5]['avatar'] = $vl6['avatar'];
                        }

                    }
                    if($vl5['reply_uid']){
                        if($vl5['reply_uid'] == $vl6['id']){
                            $find_comment[$ky5]['reply_name'] = $vl6['user_nickname'];
                            $find_comment[$ky5]['reply_mobile'] = $vl6['mobile'];
                            // 评论用户头像
                            if(empty($vl6['avatar'])) {
                                $find_comment[$ky5]['reply_avatar'] = C('DEFAULT_AVATAR');
                            } else {
                                $find_comment[$ky5]['reply_avatar'] = $vl6['avatar'];
                            }
                        }
                        //查找好友备注
                        $find_friend1 = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$vl5['reply_uid'])->find();
                        if($find_friend1['remark']){
                            $find_comment[$ky5]['reply_name'] = $find_friend1['remark'];
                        }
                    }else{
                        $find_comment[$ky5]['reply_name'] = "";
                    }
                }

                //查找好友备注
                $find_friend1 = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$vl5['uid'])->find();
                if($find_friend1['remark']){
                    $find_comment[$ky5]['name'] = $find_friend1['remark'];
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

        //存放发布动态的

        foreach($find_moments as $key => $val){
            if($val['moments_images']){
                if($val['video_path']){
                    $find_moments[$key]['video_img'] = $this->path.$val['moments_images'];
                    $find_moments[$key]['video_path'] = $this->path.$val['video_path'];
                }else{
                    $find_moments[$key]['video_path'] = "";
                    $find_moments[$key]['video_img'] = "";
                }
                $temp_arr = explode(";",$val['moments_images']);
                for($i=0;$i<count($temp_arr);$i++){
                    $find_moments[$key]['images'][] = $this->path.$temp_arr[$i];
                }
                if($val['video_path']){
                    $aaa = getimagesize($find_moments[$key]['video_img']);
                    $find_moments[$key]['video_img_width'] = "".$aaa[0];
                    $find_moments[$key]['video_img_height'] = "".$aaa[1];
                }else{
                    $find_moments[$key]['video_img_width'] = "";
                    $find_moments[$key]['video_img_height'] = "";
                }
            }else{
                $find_moments[$key]['images'] = array();
            }

            unset($find_moments[$key]['moments_images']);
            foreach($find_fri_info as $k2 => $v2){
                if($val['moments_uid'] == $v2['id']){
                    $find_moments[$key]['user_nickname'] = $v2['user_nickname'];
                    $find_moments[$key]['avatar'] = $v2['avatar'];
                    $find_moments[$key]['mobile'] = $v2['mobile'];
                }
            }

            //查找好友备注
            $find_friend2 = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$val['moments_uid'])->find();
            if($find_friend2['remark']){
                $find_moments[$key]['user_nickname'] = $find_friend2['remark'];
            }

            //对应存放评论信息
            foreach($find_comment as $k3 => $v3){
                if($val['moments_id'] == $v3['moments_id']){
                    $find_moments[$key]['comment_list'][] = $find_comment[$k3];
                }
            }

            if(empty($find_moments[$key]['comment_list'])){
                $find_moments[$key]['comment_list'] = array();
            }

            //对应存放点赞信息
            foreach($find_upvote as $k4 => $v4){
                if($val['moments_id'] == $v4['moments_id']){
                    $find_moments[$key]['upvote_list'][] = $find_upvote[$k4];
                    if($v4['uid'] == $uid && $v4['upvote'] == 1){
                        $find_moments[$key]['is_upvote'] = "1";
                    }
                }
                if(!$find_moments[$key]['is_upvote']){
                    $find_moments[$key]['is_upvote'] = "0";
                }
            }

            if(empty($find_moments[$key]['upvote_list'])){
                $find_moments[$key]['upvote_list'] = array();
                $find_moments[$key]['is_upvote'] = "0";
            }

        }

        return $this->json(0,'成功',$find_moments);


    }

    /**
     * 根据动态获取评论信息
     * */
    public function moments_comment_lists(){
        $moments_id = I('moments_id');
        if(!$moments_id){
            return $this->json(-1,'缺少参数');
        }

        $find_comment = M('moments_comment')->where("moments_id = ".$moments_id)->select();
        if(!$find_comment){
            return $this->json(-2,'还没有评论');
        }

        //存放评论者uid
        $uids_arr = array();
        foreach($find_comment as $key => $val){
            $uids_arr[] = $val['uid'];
        }
        $uids_str = implode(",",$uids_arr);

        //查找评论人信息
        $find_user = M('user')->where('id in ('.$uids_str.')')->select();
        //查找点赞信息
        //$find_upvote = M('moments_upvote')->where('uid in ('.$uids_str.')')->select();
        //查找被评论人的信息

        foreach($find_comment as $k1 => $v1){
            foreach($find_user as $k2 => $v2){//评论人名字
                if($v1['uid'] == $v2['id']){
                    $find_comment[$k1]['name']=$v2['user_nickname'];//评论者的名字
                }
            }

            foreach($find_user as $k4 => $v4){
                if($v1['reply_uid'] == $v4['id']){
                    $find_comment[$k1]['reply_name']=$v4['user_nickname'];//被评论人的名字
                }
                if(!$v1['reply_uid']){
                    $find_comment[$k1]['reply_name']="";
                }
            }

        }

        return $this->json(0,'获取成功',$find_comment);

    }

    /**
     * 我发布过的动态列表 或者 朋友发布过的动态
     * */
    public function my_moments_lists(){
        $uid = I('uid');//有分页 page
        $f_uid = I('f_uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        if($uid && !$f_uid){
            //查找用户信息
            $find_user = M('user')->where('id = '.$uid)->find();
            if(!$find_user || $find_user['state'] == 1){
                return $this->json(-2,'用户信息不存在或已被封号');
            }

            //查找朋友圈自己发布的信息
            $find_my_moments = M('moments')->where('moments_uid = '.$uid)->order("create_time desc")->limit($limit)->select();
            if(!$find_my_moments){
                return $this->json(-3,'没有更多的信息');
            }else{
                foreach($find_my_moments as $key => $val){
                    if($val['moments_images']){
                        $temp_arr = explode(";",$val['moments_images']);
                        for($i=0;$i<count($temp_arr);$i++){
                            $find_my_moments[$key]['images'][]=$this->path.$temp_arr[$i];
                        }
                    }else{
                        $find_my_moments[$key]['images'] = array();
                    }

                    unset($find_my_moments[$key]['moments_images']);
                }
                return $this->json(0,'成功',$find_my_moments);
            }
        }

        if($uid && $f_uid){

            //判断好友是否有屏蔽信息设置
            $find_friends_state1 = M('user_friends')->field('circle_state1,blacklist')->where('uid = '.$uid.' and f_uid = '.$f_uid)->find();
            if(!$find_friends_state1){
                return $this->json(-4,'您和对方不是好友关系');
            }
            if($find_friends_state1['circle_state1'] == 2){//说明设置了好友权限
                return $this->json(-5,'对方设置了好友权限');
            }

            if($find_friends_state1['circle_state1'] == 1){
                $where = 'moments_uid ='.$f_uid.' and '.$f_uid.'= (case when moments_type=1 then '.$f_uid.' when moments_type=2 then null when moments_type=3 and find_in_set('."$f_uid".',moments_visible_id)>0 then '.$f_uid.' when moments_type=4 and find_in_set('."$f_uid".',moments_visible_id)=0 then '.$f_uid.' else null end)';
                $find_my_moments = M('moments')->where($where)->order("create_time desc")->limit($limit)->select();
                if(!$find_my_moments){
                    return $this->json(-3,'没有更多的信息');
                }else{
                    foreach($find_my_moments as $key => $val){
                        if($val['moments_images']){
                            $temp_arr = explode(";",$val['moments_images']);
                            for($i=0;$i<count($temp_arr);$i++){
                                $find_my_moments[$key]['images'][]=$this->path.$temp_arr[$i];
                            }
                        }else{
                            $find_my_moments[$key]['images']= array();
                        }

                        unset($find_my_moments[$key]['moments_images']);
                    }
                    return $this->json(0,'成功',$find_my_moments);
                }
            }
        }

    }

    /**
     *设置朋友圈权限 不看该用户动态或不让别人看我的动态
     * */
    public function moments_authority(){
        $uid = I('uid');
        $f_uid = I('f_uid');
        $circle_state1 = I('circle_state1');//不让朋友f_uid看我圈子 1-看 2-不看
        $circle_state2 = I('circle_state2');//我不看朋友f_uid圈子 1-看 2-不看
        $blacklist = I('blacklist'); // 0-好友 1-黑名单

        if(!$uid || !$f_uid){
            return $this->json(-1,'缺少参数');
        }

        $user = M('user')->field('mobile')->find($uid);
        $f_user = M('user')->field('mobile')->find($f_uid);

        if($circle_state1){//不让朋友看我的圈子
            $data['circle_state1'] = $circle_state1;
            $state1_res = M('user_friends')->where("uid = ".$f_uid." and f_uid = ".$uid)->save($data);
            if(!$state1_res){
                return $this->json(-2,'没做任何修改');
            }else{
                return $this->json(0,'成功');
            }
        }

        if($circle_state2){//我不看朋友的圈子
            $map['circle_state2'] = $circle_state2;
            $state2_res = M('user_friends')->where("uid = ".$uid." and f_uid = ".$f_uid)->save($map);
            if(!$state2_res){
                return $this->json(-2,'没做任何修改');
            }else{
                return $this->json(0,'成功');
            }
        }

        if($blacklist != '') {
            // 设置黑名单
            if ($blacklist == 1) {
                $huanxin = new Easemob();
                $huanxin->addUserForBlacklist($user['mobile'], array('usernames' => array($f_user['mobile'])));
                $state1_res = M('user_friends')->where("uid = " . $uid . " and f_uid = " . $f_uid)->save(array('blacklist' => 1));
            } elseif ($blacklist == 0) {
                $huanxin = new Easemob();
                $huanxin->deleteUserFromBlacklist($user['mobile'], $f_user['mobile']);
                $state1_res = M('user_friends')->where("uid = " . $uid . " and f_uid = " . $f_uid)->save(array('blacklist' => 0));
            }
            if($state1_res) {
                return $this->json(0,'成功', $blacklist);
            } else {
                return $this->json(-3,'失败');
            }
        }
    }

    /**
     * 根据动态获取点赞信息
     * */
    public function moments_upvote_lists(){
        $moments_id = I('moments_id');
        if(!$moments_id){
            return $this->json(-1,'缺少参数');
        }

        //根据动态查找点赞信息
        $find_upvote = M('moments_upvote')->where('moments_id = '.$moments_id.' and upvote = 1')->select();
        if(!$find_upvote){
            return $this->json(-2,'没有点赞信息');
        }
        //存放点赞人的用户id
        $up_uids_arr = array();
        foreach($find_upvote as $key => $val){
            $up_uids_arr[] = $val['uid'];
        }

        $where['id'] = array('in',$up_uids_arr);
        //查找这些用户信息
        $find_user_info = M('user')->where($where)->field('id,mobile,small_avatar,user_nickname')->select();
        foreach($find_upvote as $k1 => $v1){
            foreach($find_user_info as $k2 => $v2){
                if($v1['uid'] == $v2['id']){
                    $find_upvote[$k1]['user_nickname'] = $v2['user_nickname'];
                    if($v2['small_avatar']){
                        $find_upvote[$k1]['avatar'] = $this->path.$v2['small_avatar'];
                    }else{
                        $find_upvote[$k1]['avatar'] = "";
                    }

                }
            }
        }

        return $this->json(0,'成功',$find_upvote);

    }

    /**
     * 获取朋友圈背景图（可以获取任何人朋友圈的背景图）
     * */
    public function get_moments_bg(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        $find_my_info = M('user')->where('id = '.$uid)->field('user_nickname,avatar,signature,moments_background_img,mobile')->find();
        if(!$find_my_info){
            return $this->json(-2,'用户不存在');
        }

        if($find_my_info['moments_background_img']){
            $find_my_info['moments_background_img'] = $this->path.$find_my_info['moments_background_img'];
        }

        return $this->json(0,'成功',$find_my_info);

    }

    /**
     * 合并发布朋友圈人脉圈接口
     * */
    public function talk_moments_connect(){
        $talk_type = I('talk_type');//发布类型 1朋友圈 2人脉圈 3朋友圈人脉圈同时发布
        if(!$talk_type){
            return $this->json(-1,'缺少参数');
        }
        if($talk_type == 1){
            $uid = I('uid');
            if(!$uid){
                return $this->json(-1,'缺少参数');
            }

            $moments_type = I('moments_type');  // 动态类型 1：公开 2：私密 3：部分可见 4：不给谁看'
            $moments_visible_id = I('moments_visible_id');     // 可见好友id
            $moments_remind_id = I('moments_remind_id');       // 提醒好友id
            $data = $this->moments_add($uid,$moments_type,$moments_visible_id,$moments_remind_id,1);

            $moments_content = trim(I('moments_content'));
            $position = I('position');
            $lat = I('lat');
            $lng = I('lng');
            if($position){$data['position'] = $position;}
            if($lat){$data['lat'] = $lat;}
            if($lng){$data['lng'] = $lng;}


            //查找用户信息
            $find_user = M('user')->where('id = '.$uid)->find();
            if(!$find_user || $find_user['state'] == 1){
                return $this->json(-2,'用户信息不存在或已被封号');
            }

            if($moments_content){
                $data['moments_content'] = $moments_content;
            }

            if(!empty($_FILES)){
                $info_res = $this->uploads_video_img('moments_pub_talking/talking_'.$uid);

                if($info_res['img']){
                    $data['moments_images'] = $info_res['img'];
                }
                if($info_res['video']){
                    $data['video_path'] = $info_res['video'];
                    $data['type'] = 2;
                }
            }


            if(empty($_FILES) && !$data['moments_content']){
                return $this->json(-3,'发布内容不能为空');
            }
            $data['moments_uid'] = $uid;
            $data['create_time'] = time();

            $publish_res = M('moments')->add($data);
            if($publish_res){
                return $this->json(0,'发布成功');
            }else{
                return $this->json(-4,'发布失败');
            }
        }

        if($talk_type == 2){
            $uid = I('uid');
            if(!$uid){
                return $this->json(-1,'缺少参数');
            }

            $connection_type = I('connection_type');  // 动态类型 1：公开 2：私密 3：部分可见 4：不给谁看'
            $connection_visible_id = I('connection_visible_id');     // 可见好友id
            $connection_remind_id = I('connection_remind_id');       // 提醒好友id
            $data = $this->moments_add($uid,$connection_type,$connection_visible_id,$connection_remind_id,2);

            $cc_content = trim(I('moments_content'));
            $position = I('position');
            $lat = I('lat');
            $lng = I('lng');
            if($position){$data['position'] = $position;}
            if($lat){$data['lat'] = $lat;}
            if($lng){$data['lng'] = $lng;}


            //查找用户信息
            $find_user = M('user')->where('id = '.$uid)->find();
            if(!$find_user || $find_user['state'] == 1){
                return $this->json(-2,'用户信息不存在或已被封号');
            }

            if($cc_content){
                $data['cc_content'] = $cc_content;
            }

            if(!empty($_FILES)){
                $info_res = $this->uploads_video_img('moments_pub_talking/talking_'.$uid);

                if($info_res['img']){
                    $data['cc_images'] = $info_res['img'];
                }
                if($info_res['video']){
                    $data['video_path'] = $info_res['video'];
                    $data['type'] = 2;
                }
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

        if($talk_type == 3){
            //上传朋友圈
            $uid = I('uid');
            if(!$uid){
                return $this->json(-1,'缺少参数');
            }
            $moments_type = I('moments_type');  // 动态类型 1：公开 2：私密 3：部分可见 4：不给谁看'
            $moments_visible_id = I('moments_visible_id');     // 可见好友id
            $moments_remind_id = I('moments_remind_id');       // 提醒好友id
            $data = $this->moments_add($uid,$moments_type,$moments_visible_id,$moments_remind_id,1);

            $moments_content = trim(I('moments_content'));
            $cc_content = trim(I('moments_content'));
            $position = I('position');
            $lat = I('lat');
            $lng = I('lng');
            if($position){$data['position'] = $position;}
            if($lat){$data['lat'] = $lat;}
            if($lng){$data['lng'] = $lng;}

            //查找用户信息
            $find_user = M('user')->where('id = '.$uid)->find();
            if(!$find_user || $find_user['state'] == 1){
                return $this->json(-2,'用户信息不存在或已被封号');
            }

            if($moments_content){
                $data['moments_content'] = $moments_content;
            }

            if(!empty($_FILES)){
                $info_res = $this->uploads_video_img('moments_pub_talking/talking_'.$uid);

                if($info_res['img']){
                    $data['moments_images'] = $info_res['img'];
                }
                if($info_res['video']){
                    $data['video_path'] = $info_res['video'];
                    $data['type'] = 2;
                }
            }


            if(empty($_FILES) && !$data['moments_content']){
                return $this->json(-3,'发布内容不能为空');
            }
            $data['moments_uid'] = $uid;
            $data['create_time'] = time();

            $publish_res = M('moments')->add($data);
            if(!$publish_res){
                return $this->json(-4,'朋友圈发布失败');
            }

            //上传人脉圈
            if($cc_content){
                $data['cc_content'] = $cc_content;
            }
            if($data['moments_images']){
                $data['cc_images'] = $data['moments_images'];
            }

            $data['cc_uid'] = $uid;
            //$data['create_time'] = time();
            // 动态类型 1：公开 2：私密 3：部分可见 4：不给谁看'
            // 可见好友id
            // 提醒好友id
            $connection = $this->moments_add($uid,I('connection_type'),I('connection_visible_id'),I('connection_remind_id'),2);
            if($connection) {
                $data['connection_type'] = $connection['connection_type'];
                $data['connection_visible_id'] = $connection['connection_visible_id'];
                $data['connection_remind_id'] = $connection['connection_remind_id'];
            }
            $publish_res = M('connection_circle')->add($data);

            if(!$publish_res){
                return $this->json(-5,'人脉圈发布失败');
            }

            return $this->json(0,'发布成功');

        }

        //粉丝圈
        if($talk_type == 4){
            $uid = I('uid');
            $moments_content = trim(I('moments_content'));
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

            if($moments_content){
                $data['moments_content'] = $moments_content;
            }


            if(!empty($_FILES)){
                $info_res = $this->uploads_video_img('moments_pub_talking/talking_'.$uid);

                if($info_res['img']){
                    $data['moments_images'] = $info_res['img'];
                }
                if($info_res['video']){
                    $data['video_path'] = $info_res['video'];
                    $data['type'] = 2;
                }
            }

            if(empty($_FILES) && !$data['moments_content']){
                return $this->json(-3,'发布内容不能为空');
            }
            $data['moments_uid'] = $uid;
            $data['create_time'] = time();

            $publish_res = M('fans_moments')->add($data);
            if($publish_res){
                return $this->json(0,'发布成功');
            }else{
                return $this->json(-4,'发布失败');
            }
        }

        //粉丝圈和朋友圈
        if($talk_type == 5){
            //上传朋友圈
            $uid = I('uid');
            $moments_content = trim(I('moments_content'));
            $cc_content = trim(I('moments_content'));
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

            if($moments_content){
                $data['moments_content'] = $moments_content;
            }
            if(!empty($_FILES)){
                $info_res = $this->uploads_video_img('moments_pub_talking/talking_'.$uid);

                if($info_res['img']){
                    $data['moments_images'] = $info_res['img'];
                }
                if($info_res['video']){
                    $data['video_path'] = $info_res['video'];
                    $data['type'] = 2;
                }
            }

            if(empty($_FILES) && !$data['moments_content']){
                return $this->json(-3,'发布内容不能为空');
            }
            $data['moments_uid'] = $uid;
            $data['create_time'] = time();

            $publish_res = M('moments')->add($data);
            if(!$publish_res){
                return $this->json(-4,'朋友圈发布失败');
            }

            //上传粉丝圈
            $publish_res = M('fans_moments')->add($data);
            if(!$publish_res){
                return $this->json(-5,'粉丝圈发布失败');
            }

            return $this->json(0,'发布成功');

        }

        //粉丝圈和人脉圈
        if($talk_type == 6){
            //上传粉丝圈
            $uid = I('uid');
            $moments_content = trim(I('moments_content'));
            $cc_content = trim(I('moments_content'));
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

            if($moments_content){
                $data['moments_content'] = $moments_content;
            }

            if(!empty($_FILES)){
                $info_res = $this->uploads_video_img('moments_pub_talking/talking_'.$uid);

                if($info_res['img']){
                    $data['moments_images'] = $info_res['img'];
                }
                if($info_res['video']){
                    $data['video_path'] = $info_res['video'];
                    $data['type'] = 2;
                }
            }


            if(empty($_FILES) && !$data['moments_content']){
                return $this->json(-3,'发布内容不能为空');
            }
            $data['moments_uid'] = $uid;
            $data['create_time'] = time();

            $publish_res = M('fans_moments')->add($data);
            if(!$publish_res){
                return $this->json(-4,'粉丝圈发布失败');
            }

            //上传人脉圈
            if($cc_content){
                $data['cc_content'] = $cc_content;
            }
            if($data['moments_images']){
                $data['cc_images'] = $data['moments_images'];
            }

            $data['cc_uid'] = $uid;
            //$data['create_time'] = time();

            $publish_res = M('connection_circle')->add($data);
            if(!$publish_res){
                return $this->json(-5,'人脉圈发布失败');
            }

            return $this->json(0,'发布成功');
        }

        //粉丝圈、人脉圈、朋友圈
        if($talk_type == 7){
            $uid = I('uid');
            $moments_content = trim(I('moments_content'));
            $cc_content = trim(I('moments_content'));
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

            if($moments_content){
                $data['moments_content'] = $moments_content;
            }

            if(!empty($_FILES)){
                $info_res = $this->uploads_video_img('moments_pub_talking/talking_'.$uid);

                if($info_res['img']){
                    $data['moments_images'] = $info_res['img'];
                }
                if($info_res['video']){
                    $data['video_path'] = $info_res['video'];
                    $data['type'] = 2;
                }
            }


            if(empty($_FILES) && !$data['moments_content']){
                return $this->json(-3,'发布内容不能为空');
            }
            $data['moments_uid'] = $uid;
            $data['create_time'] = time();

            $publish_res1 = M('moments')->add($data);
            if(!$publish_res1){
                return $this->json(-4,'朋友圈发布失败');
            }
            $publish_res2 = M('fans_moments')->add($data);
            if(!$publish_res2){
                return $this->json(-4,'粉丝圈发布失败');
            }

            //上传人脉圈
            if($cc_content){
                $data['cc_content'] = $cc_content;
            }
            if($data['moments_images']){
                $data['cc_images'] = $data['moments_images'];
            }

            $data['cc_uid'] = $uid;
            //$data['create_time'] = time();

            $publish_res = M('connection_circle')->add($data);
            if(!$publish_res){
                return $this->json(-5,'人脉圈发布失败');
            }

            return $this->json(0,'发布成功');
        }

    }

    /**
     *删除朋友圈人脉圈动态和评论统一接口
     * */
    public function del_moments_connect(){
        $cir_type = I('cir_type');//判断是人脉圈还是朋友圈 1、朋友圈；2、人脉圈；3、朋友圈和人脉圈
        $com_type = I('com_type');//判断是动态还是评论 1、动态；2、评论
        $uid = I('uid');//发布信息的用户uid
        $object_id = I('moments_id');//发布的动态id
        $mc_id = I('mc_id');//发表的评论id
        if(!$cir_type || !$com_type || !$object_id || !$uid){
            return $this->json(-1,'缺少参数');
        }

        if($cir_type == 1){
            //判断删除朋友圈动态还是评论
            if($com_type == 1){
                $find_info = M('moments')->where('moments_id = '.$object_id.' and moments_uid = '.$uid)->find();
                if($find_info['moments_images']){
                    //查询粉丝圈
                    $find_moments_this_info = M('fans_moments')->where("moments_uid = ".$uid." and moments_images = '".$find_info['moments_images']."'")->find();
                    //查询人脉圈
                    $find_con_circle_this_info = M('connection_circle')->where("cc_uid = ".$uid." and cc_images = '".$find_info['moments_images']."'")->find();

                    if(!$find_moments_this_info && !$find_con_circle_this_info){
                        $temp_img = explode(';',$find_info['moments_images']);
                        for($i=0;$i<count($temp_img);$i++){
                            if(file_exists(SITE_PATH.$temp_img[$i])){
                                unlink(SITE_PATH.$temp_img[$i]);
                            }
                        }
                    }
                }
                if($find_info['video_path']){
                    //查询粉丝圈
                    $find_moments_this_info = M('fans_moments')->where("moments_uid = ".$uid." and video_path = '".$find_info['video_path']."'")->find();
                    //查询人脉圈
                    $find_con_circle_this_info = M('connection_circle')->where("cc_uid = ".$uid." and video_path = '".$find_info['video_path']."'")->find();
                    if(!$find_moments_this_info && !$find_con_circle_this_info){
                        if(file_exists(SITE_PATH.$find_info['video_path'])){
                            unlink(SITE_PATH.$find_info['video_path']);
                        }
                    }
                }

                //删除动态
                $del_res = M('moments')->where('moments_id = '.$object_id.' and moments_uid = '.$uid)->delete();
                if($del_res){
                    $del_com = M('moments_comment')->where('moments_id = '.$object_id)->delete();//删除所有评论
                    $del_upvote = M('moments_upvote')->where('moments_id = '.$object_id)->delete();
                    return $this->json(0,'删除成功');
                }else{
                    return $this->json(-2,'删除失败');
                }
            }
            if($com_type == 2){
                //删除评论
                if(!$mc_id){
                    return $this->json(-1,'缺少参数');
                }
                $del_res = M('moments_comment')->where('mc_id = '.$mc_id.' and moments_id = '.$object_id.' and uid = '.$uid)->delete();
                if($del_res){
                    return $this->json(0,'删除成功');
                }else{
                    return $this->json(-2,'删除失败');
                }
            }
        }

        if($cir_type == 2){
            //判断删除朋友圈动态还是评论
            if($com_type == 1){
                $find_info = M('connection_circle')->where('cc_id = '.$object_id.' and cc_uid = '.$uid)->find();
                if($find_info['cc_images']){
                    //查询朋友圈
                    $find_moments_this_info = M('moments')->where("moments_uid = ".$uid." and moments_images = '".$find_info['cc_images']."'")->find();
                    //查询粉丝圈
                    $find_fans_this_info = M('fans_moments')->where("moments_uid = ".$uid." and moments_images = '".$find_info['cc_images']."'")->find();
                    if(!$find_moments_this_info && !$find_fans_this_info){
                        $temp_img = explode(";",$find_info['cc_images']);
                        for($i=0;$i<count($temp_img);$i++){
                            if(file_exists(SITE_PATH.$temp_img[$i])){
                                unlink(SITE_PATH.$temp_img[$i]);
                            }
                        }
                    }
                }
                if($find_info['video_path']){
                    //查询朋友圈
                    $find_moments_this_info = M('moments')->where("moments_uid = ".$uid." and video_path = '".$find_info['video_path']."'")->find();
                    //查询粉丝圈
                    $find_fans_this_info = M('fans_moments')->where("moments_uid = ".$uid." and video_path = '".$find_info['video_path']."'")->find();
                    if(!$find_moments_this_info && !$find_fans_this_info){
                        if(file_exists(SITE_PATH.$find_info['video_path'])){
                            unlink(SITE_PATH.$find_info['video_path']);
                        }
                    }
                }

                //删除动态
                $del_res = M('connection_circle')->where('cc_id = '.$object_id.' and cc_uid = '.$uid)->delete();
                if($del_res){
                    $del_com = M('connection_comment')->where('cc_id = '.$object_id)->delete();//删除所有评论
                    $del_upvote = M('connection_upvote')->where('cc_id = '.$object_id)->delete();
                    return $this->json(0,'删除成功');
                }else{
                    return $this->json(-2,'删除失败');
                }
            }
            if($com_type == 2){
                //删除评论
                if(!$mc_id){
                    return $this->json(-1,'缺少参数');
                }
                $del_res = M('connection_comment')->where('con_comment_id = '.$mc_id.' and cc_id = '.$object_id.' and uid = '.$uid)->delete();
                if($del_res){
                    return $this->json(0,'删除成功');
                }else{
                    return $this->json(-2,'删除失败');
                }
            }
        }

        if($cir_type == 12){
            if($com_type == 1){
                $find_info = M('moments')->where('moments_id = '.$object_id.' and moments_uid = '.$uid)->find();
                if($find_info['moments_images']){
                    //查询粉丝圈
                    $find_moments_this_info = M('fans_moments')->where("moments_uid = ".$uid." and moments_images = '".$find_info['moments_images']."'")->find();

                    if(!$find_moments_this_info){
                        $temp_img = explode(';',$find_info['moments_images']);
                        for($i=0;$i<count($temp_img);$i++){
                            if(file_exists(SITE_PATH.$temp_img[$i])){
                                unlink(SITE_PATH.$temp_img[$i]);
                            }
                        }
                    }
                }
                if($find_info['video_path']){
                    //查询粉丝圈
                    $find_moments_this_info = M('fans_moments')->where("moments_uid = ".$uid." and video_path = '".$find_info['video_path']."'")->find();

                    if(!$find_moments_this_info){
                        if(file_exists(SITE_PATH.$find_info['video_path'])){
                            unlink(SITE_PATH.$find_info['video_path']);
                        }
                    }
                }

                //删除动态
                $find_detail = M('moments')->where('moments_id = '.$object_id.' and moments_uid = '.$uid)->find();
                M('moments')->where('moments_id = '.$object_id.' and moments_uid = '.$uid)->delete();
                $find_detail2 = M('connection_circle')->where('create_time = '.$find_detail['create_time'].' and cc_uid = '.$uid)->find();
                M('connection_circle')->where('create_time = '.$find_detail['create_time'].' and cc_uid = '.$uid)->delete();
                $del_com = M('moments_comment')->where('moments_id = '.$object_id)->delete();//删除所有评论
                $del_upvote = M('moments_upvote')->where('moments_id = '.$object_id)->delete();
                $del_com = M('connection_comment')->where('cc_id = '.$find_detail2['cc_id'])->delete();//删除所有评论
                $del_upvote = M('connection_upvote')->where('cc_id = '.$find_detail2['cc_id'])->delete();
                return $this->json(0,'删除成功');
            }
        }

    }

    /**
     *朋友圈中我的消息接口
     * */
    public function moments_my_info(){

    }

    /**
     * 不让所有人查看我发布的动态（这个考虑要不要和人脉圈的做成一个接口）
     * */


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


    /**
     * 朋友圈人脉圈发表动态
     * @param  $uid int  会员id
     * @param   $moments_type int  动态类型 1：公开 2：私密 3：部分可见 4：不给谁看'
     * @param   $visible_id array 可见好友id
     * @param   $remind_id array  提醒好友id
     * @param   $type int  1 朋友圈 2 人脉圈
     * */
    public function moments_add($uid,$moments_type,$visible_id,$remind_id,$type) {
        if($type == 1) {
            if($moments_type){$data['moments_type'] = $moments_type;}
            if($moments_type == 3) {
                if($visible_id){$data['moments_visible_id'] = $uid.','.implode(',',$visible_id);}else{$data['moments_visible_id'] = $uid;}
            } else {
                if($visible_id){$data['moments_visible_id'] = implode(',',$visible_id);}
            }

            if($remind_id){
                $data['moments_remind_id'] = implode(',',$remind_id);
                // 根据环信发送消息
                // 查找用户信息
                $find_myself_info = M('user')->field('user_nickname,mobile')->where('id in ('.$data['moments_remind_id'].')')->select();
                $user_name = M('user')->field('user_nickname')->where('id='.$uid)->find();
                $ease = new Easemob();
                $content = "您的好友".$user_name['user_nickname']."朋友圈发布了新动态";

                foreach ($find_myself_info as $k => $v) {
                    $ext = array('type'=>'1','name'=>$v['user_nickname'],'mobile'=>$v['mobile'],'content'=>$content);
                    $mobile = $v['mobile'];
                    $mobile = explode(',',$mobile);
                    $res = $ease->sendText('系统消息','users',$mobile,$content,$ext);
                }
            }
        } elseif($type == 2) {
            if($moments_type){$data['connection_type'] = $moments_type;}
            if($moments_type == 3) {
                if($visible_id){$data['connection_visible_id'] = $uid.','.implode(',',$visible_id);}
            } else {
                if($visible_id){$data['connection_visible_id'] = implode(',',$visible_id);}
            }
            if($remind_id){
                $data['connection_remind_id'] = implode(',',$remind_id);
                // 根据环信发送消息
                // 查找用户信息
                $find_myself_info = M('user')->field('user_nickname,mobile')->where('id in ('.$data['connection_remind_id'].')')->select();
                $user_name = M('user')->field('user_nickname')->where('id='.$uid)->find();
                $ease = new Easemob();
                $content = "您的人脉".$user_name['user_nickname']."人脉圈发布了新动态";

                foreach ($find_myself_info as $k => $v) {
                    $ext = array('type'=>'1','name'=>$v['user_nickname'],'mobile'=>$v['mobile'],'content'=>$content);
                    $mobile = $v['mobile'];
                    $mobile = explode(',',$mobile);
                    $res = $ease->sendText('系统消息','users',$mobile,$content,$ext);
                }
            }
        }
        return $data;
    }



}


?>