<?php
/**
 * Created by PhpStorm.
 * User: zhangchaoxun
 * Date: 2017/11/7
 * Time: 15:41
 */
namespace Appapi\Controller;

use Common\Controller\ApibaseController;
use Org\Net\IpLocation;
use Common\Common\Easemob;


class VideoController extends ApibaseController{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 上传视频
     */
    public function video_upload()
    {
        $uid = I('uid');
        $lat = I('lat');//纬度
        $lng = I('lng');//经度
        $city = I('city');
        $content = I('content');//视频描述或是标题
        $time = time();
        $data['uid'] = $uid;
        $data['time'] = $time;
        $data['city'] = $city;
        $data['lat'] = $lat;
        $data['lng'] = $lng;

//        if (!$uid || !$lat || !$lng || !$city)
        if (!$uid )
        {
            $this->json(-1,'参数不全');
        }

        if (!empty($_FILES)) {

            /*
             * 暂时关闭类型判断
            //判断上传文件的合法性,视频和图片检测
            $video_type = explode("/",$_FILES['video']['type']);
            $img_type = explode("/",$_FILES['img']['type']);

            if ($video_type[0] !='video' || $img_type[0] !='image' )
            {
                $this->json(-2,'数据格式不正确');
            }
            */
            $info = $this->upload("joke_video/".$uid);
            foreach ($info as $key => $val) {

                switch ($val['key']){
                    case  'video':
                        $data['video_url'] = UPLOADS . $val['savepath'] . $val['savename'];
                        break;
                    case  'img':
                        $data['img_url'] = UPLOADS . $val['savepath'] . $val['savename'];
                        break;
                }
            }

        }else{
            return $this->json(-2,'数据为空');
        }
        
        //如果没有图片就使用一张默认
        $res = array(
            'video_url' => 'http://'.$_SERVER['SERVER_NAME'].$data['video_url'],
            'img_url'   => 'http://'.$_SERVER['SERVER_NAME'].$data['img_url'],
        );

        if ($res['img_url'] =='http://'.$_SERVER['SERVER_NAME'] ){
            $res['img_url'] = 'http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg';
        }
        $video_id = M('joke_video')->add($data);
        $res['video_id'] = $video_id;

        //将用户的头像和昵称返回
        $userinfo = M('user')->where('id = ' .$uid)->field('avatar,user_nickname')->find();

        if(empty($userinfo['avatar'])) {
            $userinfo['avatar'] = C('DEFAULT_AVATAR');
        }
        $res['avatar'] = $userinfo['avatar'];
        $res['user_nickname'] = $userinfo['user_nickname'];
        $res['uid'] = $uid;

        //将获取的数据写入到video_posi表中
        $data1['video_id'] = $video_id;
        $data1['lat'] = $lat;
        $data1['lng'] = $lng;
        $data1['city'] = $city;
        $data1['time'] = $time;
        $data1['CoverURL'] = $res['img_url'];
        $data1['uid'] = $uid;
        $data1['Title'] = $content;
        $data1['Size'] = $_FILES['video']['size'];

        $result = M('joke_video_posi')->add($data1);

        //将段子存入到段子点赞表中
//        $data2['uid'] = $uid;
//        $data2['time'] = $time;
//        $data2['video_id'] = $video_id;
//        M('joke_video_upvote')->add($data2);

        if ($video_id && $result) {
            return $this->json(0, '成功',$res);
        } else {
            return $this->json(-2, '上传失败');
        }
    }


    /**
     * 段子-同城-新
     */
    public function joke_video_mycity()
    {
        $uid = I('uid');
        $city = I('city');
        $page = I('page');
        if (!$uid)
        {
            $this->json(-1,'参数错误');
        }

        if (!$city){
            $city = M('user')->where('id='.$uid)->getField('city');
            if (!$city){
                $this->json(-3,'请填写自己的城市信息方可查看同城段子');
            }
        }
        $page = I('page') ? I('page'):1;
        $row = 20;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        $where['city'] = array('like','%'.$city.'%');

        $joke_list = M('joke_video')->where($where)->limit($limit)->order('rand()')->select();

        if (!$joke_list)
        {
            $this->json('-2','没有数据');
        }

        foreach ($joke_list as $jj => $kk){
            //是否点赞
            $where1['uid'] = array('eq',$uid) ;
            $where1['upvote'] = array('eq',1);
            $where1['video_id'] = $kk['id'];
            $result1 = M('joke_video_upvote')->where($where1)->find();
            if ($result1){
                //点赞了
                $joke_list[$jj]['is_upvote'] =1;
            }else{
                //没有点赞
                $joke_list[$jj]['is_upvote'] =0;
            }

            $where2['uid'] = array('eq',$uid) ;
            $where2['follow'] = array('eq',1);
            $where2['follow_uid'] = array('eq',$kk['uid']);
            $result2 = M('joke_video_follow')->where($where2)->find();
            if ($result2){
                //点赞了
                $joke_list[$jj]['is_follow'] = 1;
            }else{
                //没有点赞
                $joke_list[$jj]['is_follow'] = 0;
            }

           //评论数
            $where3['video_id'] = $kk['id'];
            $comment_num = M('joke_video_comment')->where($where3)->count();
            $joke_list[$jj]['comment_num'] = $comment_num;

            //点赞数
            $where4['video_id']=array('eq', $kk['id']);
            $where4['upvote'] = array('eq',1);
            $upvote_num = M('joke_video_upvote')->where($where4)->count();
            $joke_list[$jj]['upvote_num'] = $upvote_num;

            unset($joke_list[$jj]['time']);
            unset($joke_list[$jj]['lat']);
            unset($joke_list[$jj]['lng']);
            unset($joke_list[$jj]['city']);
        }

        $uid_arr = array();
        foreach ($joke_list as $key => $value)
        {
            $uid_arr[] = $value['uid'];
        }
        $uids_arr = array_unique($uid_arr);

        //查询用户信息
        $user_where['id'] = array('in',$uids_arr);
        $find_user_info = M('user')->field('id,user_nickname,avatar')->where($user_where)->select();

        foreach($joke_list as $k2 => $v2){
            foreach($find_user_info as $k3 => $v3){
                if($v2['uid'] == $v3['id']){
                    $joke_list[$k2]['user_nickname'] = $v3['user_nickname'];
                    $joke_list[$k2]['avatar'] = $v3['avatar'];
                }
            }

            if(empty($joke_list[$k2]['user_nickname'])){
                $joke_list[$k2]['user_nickname'] = "";
            }
            if(empty($joke_list[$k2]['avatar'])){
                $joke_list[$k2]['avatar'] = C('DEFAULT_AVATAR');
            }

        }

        $this->json(0,'成功',$joke_list);

    }

    /**
     * 上传视频位置信息
     * */
    public function upload_video_posi(){

        $video_id = I('video_id');
        $uid = I('uid');
        $lat = I('lat');
        $lng = I('lng');
        if(!$video_id || !$uid || !$lat || !$lng){
            $this->json(-1,'参数错误');
        }

        $data = array(
            'video_id' => $video_id,
            'uid' => $uid,
            'lat' => $lat,
            'lng' => $lng,
            'time' => time(),
        );
        $res = M('video_posi')->add($data);

        if(!$res){
            $this->json(-2,'上传失败');
        }

        $this->json(0,'成功');

    }

    /**
     * 段子点赞接口
     * */
    public function upvote(){
        $uid = I('uid');
        $video_id = I('video_id');

        if(!$uid || !$video_id){
            $this->json(-1,'参数错误');
        }

        //查询点赞记录
        $find_upvote_record = M('joke_video_upvote')->where("uid = ".$uid." and video_id = '".$video_id."'")->find();
        if($find_upvote_record){
            if($find_upvote_record['upvote'] == 1){
                $data['upvote'] = 2;
                $data['time'] = time();
                $res = M('joke_video_upvote')->where("uid = ".$uid." and video_id = '".$video_id."'")->save($data);
                if($res){
                    $this->json(0,'取消点赞成功');
                }else{
                    $this->json(-2,'取消点赞失败');
                }
            }else if($find_upvote_record['upvote'] == 2){
                $data['upvote'] = 1;
                $data['time'] = time();
                $res = M('joke_video_upvote')->where("uid = ".$uid." and video_id = '".$video_id."'")->save($data);
                if($res){
                    $this->json(0,'点赞成功');
                }else{
                    $this->json(-3,'点赞失败');
                }
            }else{
                $data['upvote'] = 1;
                $data['time'] = time();
                $res = M('joke_video_upvote')->where("uid = ".$uid." and video_id = '".$video_id."'")->save($data);
                if($res){
                    $this->json(0,'点赞成功');
                }else{
                    $this->json(-3,'点赞失败');
                }
            }
        }else{
            $data['upvote'] = 1;
            $data['time'] = time();
            $data['uid'] = $uid;
            $data['video_id'] = $video_id;
            $res = M('joke_video_upvote')->add($data);
            if($res){
                $this->json(0,'点赞成功');
            }else{
                $this->json(-3,'点赞失败');
            }
        }

    }

    /**
     * 段子关注接口
     * */
    public function video_follow(){
        $video_id = I('video_id');
        $uid = I('uid');
        $follow_uid = I('follow_uid');
        if(!$video_id || !$uid || !$follow_uid){
            $this->json(-1,'参数错误');
        }

        //查询是否有关注信息
        $find_info = M('joke_video_follow')->where("follow_uid = '".$follow_uid."' and uid = ".$uid)->find();
        if($find_info){
            if($find_info['follow'] == 1){
                $data['follow'] = 2;
                $data['time'] = time();
                $res = M('joke_video_follow')->where("follow_uid = '".$follow_uid."' and uid = ".$uid)->save($data);
                if($res){
                    $this->json(0,'取消关注成功');
                }else{
                    $this->json(-2,'取消关注失败');
                }
            }else if($find_info['follow'] == 2){
                $data['follow'] = 1;
                $data['time'] = time();
                $res = M('joke_video_follow')->where("follow_uid = '".$follow_uid."' and uid = ".$uid)->save($data);
                if($res){
                    $this->json(0,'关注成功');
                }else{
                    $this->json(-3,'关注失败');
                }
            }else{
                $data['follow'] = 1;
                $data['time'] = time();
                $res = M('joke_video_follow')->where("follow_uid = '".$follow_uid."' and uid = ".$uid)->save($data);
                if($res){
                    $this->json(0,'关注成功');
                }else{
                    $this->json(-3,'关注失败');
                }
            }
        }else{
            $data['follow'] = 1;
            $data['video_id'] = $video_id;
            $data['uid'] = $uid;
            $data['time'] = time();
            $data['follow_uid'] = $follow_uid;
            $res = M('joke_video_follow')->add($data);
            if($res){
                $this->json(0,'关注成功');
            }else{
                $this->json(-3,'关注失败');
            }
        }

    }

    /**
     *段子评论接口
     * */
    public function video_comment(){
        $video_id = I('video_id');
        $uid = I('uid');
        $content = I('content');
        $reply_id = I('reply_id');
        if(!$video_id || !$uid || !$content){
            $this->json(-1,'参数错误');
        }
        $data = array(
            'video_id' => $video_id,
            'uid' => $uid,
            'content' => $content,
            'time' => time(),
            'reply_id' => $reply_id ? $reply_id : 0,
        );

        $res = M('joke_video_comment')->add($data);
        if(!$res){
            $this->json(-2,'评论失败');
        }

        $this->json(0,'评论成功');

    }

    /**
     * 评论列表接口
     * */
    public function video_content_list(){
        $video_id = I('video_id');
        $page = I('page');
        if(!$video_id){
            $this->json(-1,'参数错误');
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查询评论信息
        $return_info = M('joke_video_comment')->where("video_id = '".$video_id."'")->order('time desc')->limit($limit)->select();
        if(!$return_info){
            $this->json(-2,'没有更多数据了');
        }
        $uids = array();
        foreach($return_info as $k => $v){
            $uids[]=$v['uid'];
        }

        //查询用户信息
        $where['id'] = array('in',$uids);
        $find_users = M('user')->where($where)->select();

        foreach($return_info as $key => $val){
            if((time()-$val['time']) < 60){
                $return_info[$key]['time'] = "刚刚";
            }else if((time()-$val['time']) >= 60 && (time()-$val['time']) < 60*60){
                $return_info[$key]['time'] = ceil(((time()-$val['time'])/60))."分钟前";
            }else if((time()-$val['time']) >= 60*60 && (time()-$val['time']) < 60*60*24){
                $return_info[$key]['time'] = ceil(((time()-$val['time'])/3600))."小时前";
            }else if((time()-$val['time']) >= 60*60*24 && (time()-$val['time']) < 60*60*24*2){
                $return_info[$key]['time'] = "1天前";
            }else if((time()-$val['time']) >= 60*60*24*2 && (time()-$val['time']) < 60*60*24*3){
                $return_info[$key]['time'] = "2天前";
            }else{
                $return_info[$key]['time'] = date('Y-m-d',$val['time']);
            }

            if($val['reply_id']) {
                // 被回复人信息
                $replay_users = M('user')->field('user_nickname,avatar')->find($val['reply_id']);
                $return_info[$key]['reply_user_nickname'] = $replay_users['user_nickname'];
                if(empty($replay_users['avatar'])) {
                    $return_info[$key]['reply_avatar'] = C('DEFAULT_AVATAR');
                } else {
                    $return_info[$key]['reply_avatar'] = $replay_users['avatar'];
                }
            }

            foreach($find_users as $key2 => $val2){
                if($val['uid'] == $val2['id']){
                    $return_info[$key]['user_nickname'] = $val2['user_nickname'];
                    if(empty($val2['avatar'])) {
                        $return_info[$key]['avatar'] = C('DEFAULT_AVATAR');
                    } else {
                        $return_info[$key]['avatar'] = $val2['avatar'];
                    }
                }
            }
        }

        $this->json(0,'成功',$return_info);

    }

    /**
     *视频上传成功回调
     * */
    public function video_success_notyfy(){
        $video_id = I('video_id');
        $lat = I('lat');
        $lng = I('lng');
        $city = I('city');
        $uid = I('uid');
        $title = I('title');
        $description = I('description');
        if(!$video_id || !$lat || !$lng || !$city || !$uid || !$title || !$description){
            $this->json(-1,'参数错误');
        }

        $data = array(
            'video_id' => $video_id,
            'lat' => $lat,
            'lng' => $lng,
            'city' => $city,
            'uid' => $uid,
            'Title' => $uid,
            'Description' => $description,
            'time' => time(),
        );

        if(!empty($_FILES)){
            $info = $this->upload('joke_video/user_'.$uid);
            if($info){
                $first = array_shift($info);
                $data['CoverURL'] = "http://".$_SERVER['SERVER_NAME'].UPLOADS.$first['savepath'].$first['savename'];
            }
        }

        $res = M('joke_video_posi')->add($data);

        if(!$res){
            $this->json(-2,'数据获取失败');
        }

        $this->json(0,'成功');

    }

    /**
     * 发现视频列表
     * */
    public function video_list(){
        $uid = I('uid');
        $page = I('page') ? I('page'):1;
        $row = 20;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        if(!$uid){
            $this->json(-1,'参数错误');
        }

        //查询视频列表
        $video_lists = M('joke_video_posi')->order('rand()')->limit($limit)->select();
        if(!$video_lists){
            $this->json(-2,'没有更多视频了');
        }

        $uids_arr = array();
        foreach($video_lists as $k1 => $v1){
            $video_lists[$k1]['VideoId'] = $v1['video_id'];
            $uids_arr[]=$v1['uid'];
            $temp_image_info = getimagesize($v1['CoverURL']);
            $video_lists[$k1]['width'] = $temp_image_info[0];
            $video_lists[$k1]['height'] = $temp_image_info[1];
        }
        $uids_arr = array_unique($uids_arr);

        //查询用户信息
        $user_where['id'] = array('in',$uids_arr);
        $find_user_info = M('user')->field('id,user_nickname,avatar')->where($user_where)->select();

        foreach($video_lists as $k2 => $v2){
            foreach($find_user_info as $k3 => $v3){
                if($v2['uid'] == $v3['id']){
                    $video_lists[$k2]['user_nickname'] = $v3['user_nickname'];
                    $video_lists[$k2]['avatar'] = $v3['avatar'];
                }
            }

            if(empty($video_lists[$k2]['user_nickname'])){
                $video_lists[$k2]['user_nickname'] = "";
            }
            if(empty($video_lists[$k2]['avatar'])){
                $video_lists[$k2]['avatar'] = C('DEFAULT_AVATAR');
            }

        }

        $this->json(0,'成功',$video_lists);

    }

    /**
     * new_find_video
     * 新增段子  ----发现
     */
    public function new_find_video(){
        $uid = I('uid');
        $page = I('page');
        if (!$uid)
        {
            $this->json(-1,'参数错误');
        }

        $page = I('page') ? I('page'):1;
        $row = 20;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;


        $joke_list = M('joke_video')->where($where)->limit($limit)->order('rand()')->select();

        if (!$joke_list)
        {
            $this->json('-2','没有数据');
        }

        foreach ($joke_list as $jj => $kk){
            //是否点赞
            $where1['uid'] = array('eq',$uid) ;
            $where1['upvote'] = array('eq',1);
            $where1['video_id'] = $kk['id'];
            $result1 = M('joke_video_upvote')->where($where1)->find();
            if ($result1){
                //点赞了
                $joke_list[$jj]['is_upvote'] =1;
            }else{
                //没有点赞
                $joke_list[$jj]['is_upvote'] =0;
            }

            $where2['uid'] = array('eq',$uid) ;
            $where2['follow'] = array('eq',1);
            $where2['follow_uid'] = array('eq',$kk['uid']);
            $result2 = M('joke_video_follow')->where($where2)->find();
            if ($result2){
                //点赞了
                $joke_list[$jj]['is_follow'] = 1;
            }else{
                //没有点赞
                $joke_list[$jj]['is_follow'] = 0;
            }

            //评论数
            $where3['video_id'] = $kk['id'];
            $comment_num = M('joke_video_comment')->where($where3)->count();
            $joke_list[$jj]['comment_num'] = $comment_num;

            //点赞数
            $where4['video_id']=array('eq', $kk['id']);
            $where4['upvote'] = array('eq',1);
            $upvote_num = M('joke_video_upvote')->where($where4)->count();
            $joke_list[$jj]['upvote_num'] = $upvote_num;

            unset($joke_list[$jj]['time']);
            unset($joke_list[$jj]['lat']);
            unset($joke_list[$jj]['lng']);
            unset($joke_list[$jj]['city']);
        }

        $uid_arr = array();
        foreach ($joke_list as $key => $value)
        {
            $uid_arr[] = $value['uid'];
        }
        $uids_arr = array_unique($uid_arr);

        //查询用户信息
        $user_where['id'] = array('in',$uids_arr);
        $find_user_info = M('user')->field('id,user_nickname,avatar')->where($user_where)->select();

        foreach($joke_list as $k2 => $v2){
            foreach($find_user_info as $k3 => $v3){
                if($v2['uid'] == $v3['id']){
                    $joke_list[$k2]['user_nickname'] = $v3['user_nickname'];
                    $joke_list[$k2]['avatar'] = $v3['avatar'];
                }
            }

            if(empty($joke_list[$k2]['user_nickname'])){
                $joke_list[$k2]['user_nickname'] = "";
            }
            if(empty($joke_list[$k2]['avatar'])){
                $joke_list[$k2]['avatar'] = C('DEFAULT_AVATAR');
            }

        }

        $this->json(0,'成功',$joke_list);
    }


    /**
     * follow_video
     * 段子 --- 我关注的
     */
    public function follow_video(){
        $uid = I('uid');
        $page = I('page');
        if (!$uid)
        {
            $this->json(-1,'参数错误');
        }

        $page = I('page') ? I('page'):1;
        $row = 20;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //根据uid去查被关注人的f_uid
        $f_uids = M('joke_video_follow')->field('follow_uid')->where('uid='.$uid)->select();
         if(!$f_uids){
             return $this->json(-3,'没有关注的人');
         }
        //查询被关注人的用户id
        $fuids = array();
        foreach ($f_uids as $key => $value)
        {
            $fuids[] = $value['follow_uid'];
        }
        $fuids = array_unique($fuids);

        $where['uid']= array('in',$fuids);
        $joke_list = M('joke_video')->where($where)->limit($limit)->order('rand()')->select();

        if (!$joke_list)
        {
            $this->json('-2','没有数据');
        }

        foreach ($joke_list as $jj => $kk){
            //是否点赞
            $where1['uid'] = array('eq',$uid) ;
            $where1['upvote'] = array('eq',1);
            $where1['video_id'] = $kk['id'];
            $result1 = M('joke_video_upvote')->where($where1)->find();
            if ($result1){
                //点赞了
                $joke_list[$jj]['is_upvote'] =1;
            }else{
                //没有点赞
                $joke_list[$jj]['is_upvote'] =0;
            }

//            $where2['uid'] = array('eq',$uid) ;
//            $where2['follow'] = array('eq',1);
//            $where2['follow_uid'] = array('eq',$kk['uid']);
//            $result2 = M('joke_video_follow')->where($where2)->find();
//            if ($result2){
//                //点赞了
//                $joke_list[$jj]['is_follow'] = 1;
//            }else{
//                //没有点赞
//                $joke_list[$jj]['is_follow'] = 0;
//            }

            $joke_list[$jj]['is_follow'] = 1;
            //评论数
            $where3['video_id'] = $kk['id'];
            $comment_num = M('joke_video_comment')->where($where3)->count();
            $joke_list[$jj]['comment_num'] = $comment_num;

            //点赞数
            $where4['video_id']=array('eq', $kk['id']);
            $where4['upvote'] = array('eq',1);
            $upvote_num = M('joke_video_upvote')->where($where4)->count();
            $joke_list[$jj]['upvote_num'] = $upvote_num;

            unset($joke_list[$jj]['time']);
            unset($joke_list[$jj]['lat']);
            unset($joke_list[$jj]['lng']);
            unset($joke_list[$jj]['city']);
        }

        $uid_arr = array();
        foreach ($joke_list as $key => $value)
        {
            $uid_arr[] = $value['uid'];
        }
        $uids_arr = array_unique($uid_arr);

        //查询用户信息
        $user_where['id'] = array('in',$uids_arr);
        $find_user_info = M('user')->field('id,user_nickname,avatar')->where($user_where)->select();

        foreach($joke_list as $k2 => $v2){
            foreach($find_user_info as $k3 => $v3){
                if($v2['uid'] == $v3['id']){
                    $joke_list[$k2]['user_nickname'] = $v3['user_nickname'];
                    $joke_list[$k2]['avatar'] = $v3['avatar'];
                }
            }

            if(empty($joke_list[$k2]['user_nickname'])){
                $joke_list[$k2]['user_nickname'] = "";
            }
            if(empty($joke_list[$k2]['avatar'])){
                $joke_list[$k2]['avatar'] = C('DEFAULT_AVATAR');
            }

        }

        $this->json(0,'成功',$joke_list);
    }

    /**
     * get_user_videos
     * 新增 -用户他人页详情
     */
    public function get_user_videos() {
        $f_uid = I('f_uid'); // 查看用户的id
        $uid = I('uid');    // 自己的id
        if(!$uid || !$f_uid) {$this->json(-1, '参数错误');}

        // 用户信息
        $user_info = M('user')->field('id,user_nickname,avatar,sex,signature,mobile,province,city')->find($f_uid);
        if(!$user_info) {
            $this->json(-1, '用户信息不存在');
        }
        if(empty($user_info['avatar'])) {
            $user_info['avatar'] = C('DEFAULT_AVATAR');
        }

        //查询粉丝关注情况
        $find_res = M('fans')->where('fans_uid = '.$uid.' and follow_uid = '.$f_uid)->find();
        if($find_res){
            $res['is_fans'] = '1';
        }else{
            $res['is_fans'] = '0';
        }
        // 粉丝总数
        $fans_count = M('fans')->field('count(*) fans_count')->where('follow_uid = '.$f_uid)->select();
        // 段子列表
        $joke_list = M('joke_video')->where(array('uid'=>$f_uid))->order('time desc')->select();
        if($joke_list) {
            foreach ($joke_list as $k => $v) {
                $video_id[] = $v['id'];
            }
            $video_upvote_count = M('joke_video_upvote')->field('count(*) upvote_count')->where('video_id in ('.implode(",",$video_id).') and upvote = 1')->select();
            $video_follow_count = M('joke_video_follow')->field('count(*) follow_count')->where('follow_uid = '.$f_uid.' and follow = 1')->select();
        }

        // 喜欢视频信息
        $video_like_id = M('joke_video_upvote')->field('video_id')->where('uid = '.$f_uid.' and upvote = 1')->select();
        if($video_like_id) {
            $joke_like_list = M('joke_video')->where(array('id'=>array('in',array_column($video_like_id,'video_id'))))->order('time desc')->select();
        }

        $data = array(
            'is_fans' => $res['is_fans'],                                                                                // 是否关注 1 关注 0 没关注
            'joke_count' => count($joke_list),                                                                           // 作品总数
            'upvote_count' => $video_upvote_count[0]['upvote_count'] ? $video_upvote_count[0]['upvote_count'] : "0",     // 获赞总数
            'like_count' =>count($joke_like_list),                                                                       // 喜欢总数
            'follow_count' => $video_follow_count[0]['follow_count'] ? $video_upvote_count[0]['upvote_count'] : "0",     // 关注总数
            'fans_count' => $fans_count[0]['fans_count'],                                                                // 粉丝总数
            'user_info' => $user_info,                                                                                   // 用户信息
            'joke_list' => $joke_list,                                                                                   // 视频信息
            'joke_like_list' => $joke_like_list                                                                          // 喜欢视频信息
        );

        $this->json(0,'成功',$data);
    }


    /**
     * play_video
     * 新增 单个段子信息
     */
    public function play_video() {
        $video_id = I('video_id');
        $uid = I('uid');

        if(!$uid || !$video_id) {
            $this->json(-1, '参数错误');
        }

        // 增加播放数量
        M('joke_video')->where(array('id'=>$video_id))->setInc('play_num',1);

        // 分享数量
        $share_video_record_num = M('joke_video')->field('share_video_record_num,play_num')->where(array('id'=>$video_id))->find();
        $data['share_video_record_num'] = $share_video_record_num['share_video_record_num'];
        // 播放数量
        $data['play_num'] = $share_video_record_num['play_num'];

        //评论数
        $comment_num = M('joke_video_comment')->where(array('video_id'=>$video_id))->count();
        $data['comment_num'] = $comment_num;

        //点赞数
        $upvote_num = M('joke_video_upvote')->where(array('video_id'=>$video_id,'upvote'=>1))->count();
        $data['upvote_num'] = $upvote_num;

        //是否点赞
        $result1 = M('joke_video_upvote')->where(array('uid'=>$uid,'upvote'=>1,'video_id'=>$video_id))->find();
        if ($result1){
            $data['is_upvote'] =1;  //点赞了
        }else{
            $data['is_upvote'] =0;   //没有点赞
        }
        //是否关注
        $follow_uid = M('joke_video')->field('uid')->find($video_id);
        $where2['uid'] = array('eq',$uid) ;
        $where2['follow'] = array('eq',1);
        $where2['follow_uid'] = array('eq',$follow_uid['uid']);
        $result2 = M('joke_video_follow')->where($where2)->find();
        if ($result2){
            //关注了
            $data['is_follow'] = 1;
        }else{
            //未关注
            $data['is_follow'] = 0;
        }

        $user = M('user')->field('user_nickname,avatar')->find($follow_uid['uid']);
        $data['user_nickname'] = $user['user_nickname'];
        $data['avatar'] = $user['avatar'];

        $this->json(0,'成功',$data);
    }


    //新增-段子-我的发布
    public function get_my_videos(){
        $uid = I('uid');
        $page = I('page');
        if (!$uid)
        {
            $this->json(-1,'参数错误');
        }

        $page = I('page') ? I('page'):1;
        $row = 20;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        $where['uid']= array('eq',$uid);
        $joke_list = M('joke_video')->where($where)->limit($limit)->order('time desc')->select();

        if (!$joke_list)
        {
            $this->json('-2','没有数据');
        }

        foreach ($joke_list as $jj => $kk){
            //是否点赞
            $where1['uid'] = array('eq',$uid) ;
            $where1['upvote'] = array('eq',1);
            $where1['video_id'] = $kk['id'];
            $result1 = M('joke_video_upvote')->where($where1)->find();
            if ($result1){
                //点赞了
                $joke_list[$jj]['is_upvote'] =1;
            }else{
                //没有点赞
                $joke_list[$jj]['is_upvote'] =0;
            }

            $where2['uid'] = array('eq',$uid) ;
            $where2['follow'] = array('eq',1);
            $where2['follow_uid'] = array('eq',$kk['uid']);
            $result2 = M('joke_video_follow')->where($where2)->find();
            if ($result2){
                //点赞了
                $joke_list[$jj]['is_follow'] = 1;
            }else{
                //没有点赞
                $joke_list[$jj]['is_follow'] = 0;
            }

            //评论数
            $where3['video_id'] = $kk['id'];
            $comment_num = M('joke_video_comment')->where($where3)->count();
            $joke_list[$jj]['comment_num'] = $comment_num;

            //点赞数
            $where4['video_id']=array('eq', $kk['id']);
            $where4['upvote'] = array('eq',1);
            $upvote_num = M('joke_video_upvote')->where($where4)->count();
            $joke_list[$jj]['upvote_num'] = $upvote_num;

//            unset($joke_list[$jj]['time']);
            unset($joke_list[$jj]['lat']);
            unset($joke_list[$jj]['lng']);
            unset($joke_list[$jj]['city']);
        }

        $uid_arr = array();
        foreach ($joke_list as $key => $value)
        {
            $uid_arr[] = $value['uid'];
        }
        $uids_arr = array_unique($uid_arr);

        //查询用户信息
        $user_where['id'] = array('in',$uids_arr);
        $find_user_info = M('user')->field('id,user_nickname,avatar')->where($user_where)->select();

        foreach($joke_list as $k2 => $v2){
            foreach($find_user_info as $k3 => $v3){
                if($v2['uid'] == $v3['id']){
                    $joke_list[$k2]['user_nickname'] = $v3['user_nickname'];
                    $joke_list[$k2]['avatar'] = $v3['avatar'];
                }
            }

            if(empty($joke_list[$k2]['user_nickname'])){
                $joke_list[$k2]['user_nickname'] = "";
            }
            if(empty($joke_list[$k2]['avatar'])){
                $joke_list[$k2]['avatar'] = C('DEFAULT_AVATAR');
            }

        }

        $this->json(0,'成功',$joke_list);
    }


    /**
     * 删除段子
     */
    public function delete_joke_video(){
        $uid = I('uid');
        $video_id = I('video_id');
        //根据段子id找到归属人
        $is_my = M('joke_video')->field('uid,video_url,img_url')->where('id='.$video_id)->find();
        if ($is_my['uid'] != $uid){
            $this->json(-1,'段子不属于你');
        }

        //删除磁盘文件
        //  /data/upload/joke_video/102/5b061d15b37fd9.19510595.jpg
        $img_url = substr($is_my['img_url'],1);
        $video_url = substr($is_my['video_url'],1);

        $img_url =SPAPP ."../". $img_url;
        $video_url = SPAPP."../".$video_url;

        $is_del1 = delDirAndFile($video_url);
        $is_del = delDirAndFile($img_url);

        $res5 = M('joke_video')->where('id='.$video_id)->delete();
        if (!$res5){
            $this->json(-3,'删除失败');
        }
        if (!$is_del1){
            $this->json(-2,'删除段子文件失败');
        }

        //删除所有该段子的相关信息

        $res1 = M('joke_video_comment')->where('video_id='.$video_id)->delete();
        $res2 = M('joke_video_follow')->where('video_id='.$video_id)->delete();
        $res3 = M('joke_video_posi')->where('video_id='.$video_id)->delete();
        $res4 = M('joke_video_upvote')->where('video_id='.$video_id)->delete();


        $this->json(0,'删除成功');

    }

    /**
     *段子视频播放页-视频换成自己服务器
     * */
    public function video_play_web(){
        $video_id = I('video_id');
        $uid = I('humsg');
        $platform_type = I('platform_type');//1、微信；2、微信朋友圈；3、QQ；4、微博
        if(!$video_id){
            return false;
        }
      /*  $data = array(
            'video_id' => $video_id,
        );

        //$url = 'http://localhost/debo/application/Common/Common/share_video_getinfo.php';
        //$url = 'http://debo.shangtongyuntian.com/application/Common/Common/share_video_getinfo.php';
        //$video_info = $this->curl_post($url,$data);

        // $video_info = json_decode($video_info,true);
       // if($video_info['code'] != 0){
         //   return false;
      //  }else{
            //$this->json(0,'',$video_info);
            //$video_res = M('joke_video_posi')->where("video_id = '".$video_id."'")->find();*/
            $video_res = M('joke_video')->where("id = '".$video_id."'")->find();
            if($video_res){
                $find_user = M('user')->where('id = '.$video_res['uid'])->field('id,user_nickname,avatar,mobile')->find();
                if(!$find_user){
                    $find_user['user_nickname'] = '';
                    $find_user['avatar'] = C('DEFAULT_AVATAR');
                    $find_user['debo_code'] = '';
                }else{
                    $find_user['debo_code'] = substr(md5($find_user['mobile']),0,8);
                    if(empty($find_user['avatar'])) {
                        $find_user['avatar'] = C('DEFAULT_AVATAR');
                    }
                }
            }else{
                $video_res['CoverURL'] = '';
                $find_user['user_nickname'] = '';
                $find_user['avatar'] = C('DEFAULT_AVATAR');
                $find_user['debo_code'] = '';
            }
           // $play_url = $video_info['data']['PlayURL'];
            $play_url = $video_res['video_url'];
            $this->assign('play_url',$play_url);
            $this->assign('video_cover_url',$video_res['CoverURL']);
            $this->assign('user_msg',$find_user);
            $this->display();
       // }
    }

    public function video_share_callback(){
        $uid = I('uid');
        $video_id = I('video_id');
        $platform_type = I('platform_type');//1、微信；2、微信朋友圈；3、QQ；4、微博；5、QQ空间
        if(!$uid || !$video_id || !$platform_type){
            $this->json(-1,'参数错误');
        }
        // 增加分享数量
        M('joke_video')->where(array('id'=>$video_id))->setInc('share_video_record_num',1);

        //获取当前系统时间
        $time = time();
        $temp_time = date('Y-m-d',$time);
        $share_date = strtotime($temp_time);

        //查询是否有分享记录
        $find_share_record = M('share_video_record')->where("uid = ".$uid." and video_id = '".$video_id."' and platform_type = ".$platform_type." and time = '".$share_date."'")->find();
        if(!$find_share_record){
            $data = array(
                'uid' => $uid,
                'video_id' => $video_id,
                'platform_type' => $platform_type,
                'time' => $share_date,
                'get_points' => 0.1,
            );
            $share_res = M('share_video_record')->add($data);
            if(!$share_res){
                $this->json(-2,'分享失败');
            }
            // 增加用户积分
            $user_info = M('user')->field('points')->find($uid);
            M('user')->where(array('id'=>$uid))->setInc('points',$data['get_points']);
            // 插入日志
            points_log($uid, 1, $data['get_points'], $user_info['points'],'分享链接');
            $this->json(0,'分享成功');
        }else{
            $this->json(-3,'此视频今天在该平台分享过，不会得积分哦。');
        }

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

}


?>