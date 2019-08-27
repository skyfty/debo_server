<?php
/**
 * 粉丝
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19 0019
 * Time: 上午 9:18
 */
namespace Appapi\Controller\Debo1;

use Common\Controller\ApibaseController;
use Common\Common\Easemob;

class FansController extends ApibaseController{
    private $path = "http://debo.shangtongyuntian.com";
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *关注、取消关注（粉丝）
     * */
    public function fans(){
        $fans_uid = I('fans_uid');
        $follow_uid = I('follow_uid');
        $follow_mobile = I('follow_mobile');
        if($follow_mobile){
            $user_deta = M('user')->where('mobile = '.$follow_mobile)->find();
            $follow_uid = $user_deta['id'];
        }

        if(!$fans_uid || !$follow_uid){
            return $this->json(-1,"缺少参数");
        }


        if($fans_uid == $follow_uid){
            return $this->json(-2,"不能关注自己");
        }

        $is_fans = M('fans')->where('fans_uid = '.$fans_uid.' and follow_uid = '.$follow_uid)->find();
        if($is_fans){//取关注
            $del_zan = M('fans')->where('fans_uid = '.$fans_uid.' and follow_uid = '.$follow_uid)->delete();

            $re_num = M('user')->where("id = ".$follow_uid)->setDec('fans_num');

            if($del_zan && $re_num){
                return $this->json(0,"取消成功","1");
            }else{
                return $this->json(-3,"取消失败");
            }

        }else{//关注           
            $data = array(
                'fans_uid' => $fans_uid,
                'follow_uid' => $follow_uid,
                'follow_time' => time()
                );

            $add_show = M('fans')->add($data);

            $re_num = M('user')->where("id = ".$follow_uid)->setInc('fans_num');

            if($add_show && $re_num){
                return $this->json(0,"关注成功","2");
            }else{
                return $this->json(-4,"关注失败");
            }

        }
       
    }

    /**
     *我的粉丝列表
     * */
    public function my_fans(){
        $uid = I('uid');
        $type = I('type');
        if(!$uid || !$type){
            return $this->json(-1,"缺少参数");
        }
        if($type == 1){//关注我的粉丝
            $condition = 'follow_uid = '.$uid;
        }else if($type == 2){//我关注的
            $condition = 'fans_uid = '.$uid;
        }
        $page = I('page')?I('page'):1;
        $limit = (($page-1)*10).',10';
        $my_fans = M('fans')->where($condition)->limit($limit)->select();
        $fans_num = M('fans')->where($condition)->count();
        $return_info = array();
        $return_info['fans_num'] = $fans_num;
        if(!$my_fans){
            return $this->json(-2,"没有粉丝",$return_info);
        }
        $fans_uid = array();
        foreach($my_fans as $k => $fans){
            if($type == 1){
                $fans_uid[] = $fans['fans_uid'];               
            }else if($type == 2){
                $fans_uid[] = $fans['follow_uid'];
            }           
        }
        $uid_tmp = implode(',',$fans_uid);
        $fans = M('user')->field('id,user_nickname,signature,small_avatar')->where('id in ('.$uid_tmp.')')->select();
        if(!$fans){
            return $this->json(-3,"没有粉丝信息");
        }
        if($type == 1){//关注我的粉丝
            $my_concern_tmp = M('fans')->where('follow_uid = '.$uid)->select();
            $my_concern = array();
            foreach($my_concern_tmp as $k => $concern){
                $my_concern[] = $concern['fans_uid'];
            }
        }

        foreach($fans as $k => $v){
            //拼接头像路径
            if($v['small_avatar']){
                $v['small_avatar'] = $this->path.$v['small_avatar'];
            }else{
                $v['small_avatar'] = C('DEFAULT_AVATAR');
            }

            if($type == 2){//我关注的粉丝
                $v['is_concern'] = '1';
            }else if($type == 1){//关注我的
                $v['is_concern'] = in_array($v['id'],$my_concern)?'1':'0';
            }            
            $return_info['fans_list'][] = $v;
        }
        return $this->json(0,"成功",$return_info);


    }

    /**
     *粉丝首页
     * */
    public function fans_index(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,"缺少参数");
        }
        $page = I('page')?I('page'):1;
        $limit = (($page-1)*10).',10';
        $sql = '(select m_id,uid,content,add_time,img,type,tab_type from de_fans_message where uid = '.$uid.')union(select moments_id as m_id,moments_uid as uid,moments_content as content,create_time as add_time,moments_images as img,type,tab_type from de_fans_moments where moments_uid = '.$uid.') order by add_time desc limit '.$limit;
        $trends_tmp = M('fans_message')->query($sql);

        $user_info = M('user')->field('user_nickname,avatar,mobile,fans_num')->where('id = '.$uid)->find();
        
//        if(!$trends_tmp || !$user_info){
//            return $this->json(-2,"没有数据");
//        }
        krsort($trends_tmp);
        $trends_list = array();
        foreach ($trends_tmp as $k => $v){

            if($v['type'] == 1 && $v['tab_type'] == 1){
                $trends = M('fans_message')->field('issue,title')->where('m_id = '.$v['m_id'])->find();
                $v['issue'] = '【第'.$trends['issue'].'期】';
                $v['title'] = $trends['title'];  
            }
            if($v['type'] == 2 && $v['tab_type'] == 2){
                $trends = M('fans_moments')->field('video_path')->where('moments_id = '.$v['m_id'])->find();
                $v['video_path'] = "http://debo.shangtongyuntian.com".$trends['video_path'];
                $v['video_img'] = "http://debo.shangtongyuntian.com".$v['img'];
            }
            $v['add_time'] = date("m-d",$v['add_time']);
            if($v['tab_type'] == 2){
                $v['type'] = ($v['type'] == 1)?'3':'4';
                $zan = M('fans_upvote')->where('uid = '.$v['uid'].' and moments_id = '.$v['m_id'].' and upvote = 1')->find();
                $v['is_zan'] = $zan?'1':'0';             
            }
            if($v['img'] && $v['type'] != 4){
                $v['img'] = explode(';',$v['img']); 
                foreach($v['img'] as $k => $val){
                    $v['img'][$k] = "http://debo.shangtongyuntian.com".$val;
                } 
            }else{
                $v['img'] = array();
            }  
            $v['avatar'] = $user_info['avatar'] ? $user_info['avatar'] : C('DEFAULT_AVATAR');;
            $v['user_nickname'] = $user_info['user_nickname'];

            // $timetoday = strtotime(date("Y-m-d",$v['add_time']));
            // $trends_list[$timetoday][] = $v;

            $trends_list[] = $v;
            
        }
        
        $return_info = array(
            'user_nickname' => $user_info['user_nickname'],
            'mobile' => $user_info['mobile'],
            'fans_num' => $user_info['fans_num'],
            'trends_list' => $trends_list
            );

        return $this->json(0,"成功",$return_info);
    }

    /**
     *阅读全文
     * */
    public function read_more(){
        $m_id = I('m_id');
        if(!$m_id){
            return $this->json(-1,"缺少参数");
        }
        $trends_tmp = M('fans_message')->where('m_id = '.$m_id.' and type = 1')->find();
        if(!$trends_tmp){
            return $this->json(-2,"没有该文章");
        }
        if($trends_tmp['img']){
            $trends_tmp['img'] = explode(';',$trends_tmp['img']);  
            foreach($trends_tmp['img'] as $k => $val){
                $trends_tmp['img'][$k] = "http://debo.shangtongyuntian.com".$val;
            }           
        }
        $trends_tmp['add_time'] = date("Y-m-d",$trends_tmp['add_time']);
 
        $re_num = M('fans_message')->where('m_id = '.$m_id.' and type = 1')->setInc('read_num');

        return $this->json(0,"成功",$trends_tmp);

    }

    /**
     * 群发动态接口
     * */
    public function group_message(){
        $title = I('title');
        $content = I('content');
        $uid = I('uid');
        $type = I('type');
        if(!$uid){
            return $this->json(-1,"缺少参数");
        }

        if(empty($_FILES) && !$content){
            return $this->json(-2,"发布内容不能为空");
        }

        if(!empty($_FILES)){
            $image = $this->uploads('fans_moment/fans_'.$uid);
            $data['img'] = $image;
        }

        $data['title'] = $title;
        $data['content'] = $content;
        $data['uid'] = $uid;
        $data['type'] = $type;
        $data['add_time'] = time();

        $res = M('fans_message')->add($data);
        if($res){
            return $this->json(0,'发布成功！');
        }

    }

    /**
     *粉丝圈点赞
     * */
    public function fans_upvote(){
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
        $find_moments_upvote = M('fans_upvote')->where("uid = ".$uid." and moments_id = ".$moments_id)->find();
        if($find_moments_upvote){
            if($find_moments_upvote['upvote'] == $upvote){
                return $this->json(-3,'状态未发生改变');
            }
            $upvote_res = M('fans_upvote')->where("uid = ".$uid." and moments_id = ".$moments_id)->setField('upvote',$upvote);
            if($upvote_res){
                $find_res = M('fans_upvote')->where("uid = ".$uid." and moments_id = ".$moments_id)->find();
                $find_user_res = M('user')->where('id = '.$find_res['uid'])->field('small_avatar')->find();
                $find_res['small_avatar'] = $this->path.$find_user_res['small_avatar'];
                $find_res['upvote_num'] = M('fans_upvote')->where("moments_id = ".$moments_id." and upvote = 1")->count();
                if(!$find_res['upvote_num']){
                    $find_res['upvote_num'] = "";
                }
                return $this->json(0,'成功',$find_res);
            }else{
                return $this->json(-3,'状态未发生改变');
            }
        }else{
            $data['uid'] = $uid;
            $data['moments_id'] = $moments_id;
            $data['upvote'] = $upvote;
            $upvote_res = M('fans_upvote')->add($data);
            if($upvote_res){
                $find_res = M('fans_upvote')->where('upvote_id = '.$upvote_res)->find();
                $find_user_res = M('user')->where('id = '.$find_res['uid'])->field('small_avatar')->find();
                if(empty($find_res['small_avatar'])) {
                    $find_res['small_avatar'] = C('DEFAULT_AVATAR');
                } else {
                    $find_res['small_avatar'] = $this->path.$find_user_res['small_avatar'];
                }
                $find_res['upvote_num'] = M('fans_upvote')->where("moments_id = ".$moments_id." and upvote = 1")->count();
                if(!$find_res['upvote_num']){
                    $find_res['upvote_num'] = "";
                }
                return $this->json(0,'成功',$find_res);
            }else{
                return $this->json(-3,'状态未发生改变');
            }
        }

    }

    /**
     * 粉丝圈评论
     * */
    public function fans_comment(){
        $uid = I('uid');//发布评论者uid
        $moments_id = I('moments_id');//朋友圈的动态id
        $mc_content = I('mc_content');//朋友圈评论内容
        $reply_uid = I('reply_uid');//回复人uid
        $mc_id = I('mc_id');//回复人uid
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
            //查询给哪条评论回复
            $data['reply_uid'] = $reply_uid;
            $data['parent_id'] = $mc_id;
        }

        $com_res = M('fans_comment')->add($data);
        if($com_res){
            $find_res = M('fans_comment')->where('mc_id = '.$com_res)->find();
            $find_res['comment_time'] = date('Y-m-d H:i:s',$find_res['comment_time']);
            $find_user_info1 = M('user')->where('id = '.$find_res['uid'])->field('id,user_nickname,mobile')->find();
            $find_user_info2 = M('user')->where('id = '.$find_res['reply_uid'])->field('id,user_nickname,mobile')->find();
            if($find_user_info1['user_nickname']){
                $find_res['name'] = $find_user_info1['user_nickname'];
                $find_res['avatar'] = $find_user['avatar'];
                $find_res['mobile'] = $find_user['mobile'];
            }else{
                $find_res['name'] = $find_user_info1['mobile'];
                $find_res['mobile'] = $find_user_info1['mobile'];
            }
            if($find_user_info2['user_nickname']){
                $find_res['reply_name'] = $find_user_info2['user_nickname'];
                $find_res['reply_mobile'] = $find_user_info2['mobile'];
            }else{
                $find_res['reply_name'] = $find_user_info2['mobile'];
                $find_res['reply_mobile'] = $find_user_info2['mobile'];
            }

            return $this->json(0,'评论成功',$find_res);
        }else{
            return $this->json(-3,'评论失败');
        }
    }

    /**
      *粉丝动态
      */
        public function fans_trends()
        {
            //接收请求粉丝动态的uid
            $uid = I('uid');
           if(!$uid )
           {
               return $this->json(-1,'缺少参数！');
           }

            $page = I('page') ? I('page'):1;
            $row = 10;//每页显示的条数
            $limit = ($page-1)*$row.','.$row;

           $fans = M('fans')->where('fans_uid ='.$uid)->getfield('follow_uid',true); //获取所有关注的用户

//            if(!$fans)
//            {
//                return $this->json(-2,'您还没有关注粉丝，去关注一些吧~');
//            }

            $my_fans_uids_arr = array();
            if($fans){
                foreach ($fans as $key => $val)
                {
                    $my_fans_uids_arr[]=$val;

//                unset($fans[$key]);
//              $fans[$key]['avatar']= M('user')->where('id ='.$val)->getfield('avatar');
//              $fans[$key]['mobile']= M('user')->where('id ='.$val)->getfield('mobile');
                }
                $my_fans_uids_arr = array_unique($my_fans_uids_arr);
            }


            array_push($my_fans_uids_arr,$uid);


            //通过uid查询所有粉丝信息
            $where['id'] = array('in',$my_fans_uids_arr);
            $find_fans_info = M('user')->where($where)->select();

            //查询这些粉丝发布的粉丝圈
            $where2['moments_uid'] = array('in',$my_fans_uids_arr);
            $my_fans_moments = M('fans_moments')->where($where2)->limit($limit)->order('create_time desc')->select();
            if(!$my_fans_moments){
                return $this->json(-3,'没有更多动态了');
            }

            foreach ($my_fans_moments as $key => $val)
            {
                //查找粉丝动态评论数量
                $my_fans_moments[$key]['comment_num'] = M('fans_comment')->where("moments_id = ".$val['moments_id'])->group('parent_id')->count();
                //查找粉丝点赞数量
                $my_fans_moments[$key]['upvote_num'] = M('fans_upvote')->where("moments_id = ".$val['moments_id']." and upvote = 1")->count();

                //$my_fans_moments[$key]['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
                if((time()-$val['create_time']) < 60){
                    $my_fans_moments[$key]['create_time'] = "刚刚";
                }else if((time()-$val['create_time']) >= 60 && (time()-$val['create_time']) < 60*60){
                    $my_fans_moments[$key]['create_time'] = ceil(((time()-$val['create_time'])/60))."分钟前";
                }else if((time()-$val['create_time']) >= 60*60 && (time()-$val['create_time']) < 60*60*24){
                    $my_fans_moments[$key]['create_time'] = ceil(((time()-$val['create_time'])/3600))."小时前";
                }else if((time()-$val['create_time']) >= 60*60*24 && (time()-$val['create_time']) < 60*60*24*2){
                    $my_fans_moments[$key]['create_time'] = "1天前";
                }else if((time()-$val['create_time']) >= 60*60*24*2 && (time()-$val['create_time']) < 60*60*24*3){
                    $my_fans_moments[$key]['create_time'] = "2天前";
                }else{
                    $my_fans_moments[$key]['create_time'] = date('Y-m-d',$val['create_time']);
                }

                if($val['moments_images']){
                    $img = explode(';',$val['moments_images']);
                    $image = array();
                    for($i=0;$i<count($img);$i++){
                        $image[] = $this->path.$img[$i];
                    }
                }else{
                    $image = array();
                }

                $my_fans_moments[$key]['images'] = $image;
                if(!empty($my_fans_moments[$key]['moments_images'])){
                    $my_fans_moments[$key]['video_img'] = $this->path.$my_fans_moments[$key]['moments_images'];
                    unset($my_fans_moments[$key]['moments_images']);
                }
                if(!empty($my_fans_moments[$key]['video_path'])){
                    $my_fans_moments[$key]['video_path'] = $this->path.$my_fans_moments[$key]['video_path'];
                    $aaa = getimagesize($my_fans_moments[$key]['video_img']);
                    $my_fans_moments[$key]['video_img_width'] = "".$aaa[0];
                    $my_fans_moments[$key]['video_img_height'] = "".$aaa[1];
                }else{
                    $my_fans_moments[$key]['video_path'] = "";
                    $my_fans_moments[$key]['video_img'] = "";
                    $my_fans_moments[$key]['video_img_width'] = "";
                    $my_fans_moments[$key]['video_img_height'] = "";
                }

                foreach($find_fans_info as $k => $v)
                {
                    if($val['moments_uid'] == $v['id']){
                        $my_fans_moments[$key]['mobile'] = $v['mobile'];
                        $my_fans_moments[$key]['user_nickname'] = $v['user_nickname'];
                        $my_fans_moments[$key]['avatar'] = $v['avatar'] ? $v['avatar'] : C('DEFAULT_AVATAR');

                    }
                }


               $info = M('fans_upvote')->where(['uid' =>$uid,'moments_id'=>$val['moments_id']])->getfield('upvote');
               if($info){
                   $my_fans_moments[$key]['is_upvote'] = M('fans_upvote')->where(['uid' =>$uid,'moments_id'=>$val['moments_id']])->getfield('upvote');
               }else{
                   $my_fans_moments[$key]['is_upvote'] = 0;
               }

            }

                return $this->json(0,'查询成功',$my_fans_moments);

        }

    /**
     * 给评论点赞功能
     * */
    public function fans_comment_upvote(){
        $uid = I('uid');
        $mc_id = I('mc_id');//这条评论的id

        if(!$uid || !$mc_id){
            return $this->json(-1,'缺少参数！');
        }

        //查询表中是否有记录
        $find_upvote_info = M('fans_comment_upvote')->where("uid = ".$uid." and mc_id = ".$mc_id)->find();
        if($find_upvote_info){
            if($find_upvote_info['upvote'] == 1){
                $save_res = M('fans_comment_upvote')->where("uid = ".$uid." and mc_id = ".$mc_id)->setField('upvote',0);
                if(!$save_res){
                    return $this->json(-2,'失败！');
                }

                //取消点赞
                $save_res = M('fans_comment')->where("mc_id = ".$mc_id)->setDec('upvote_num',1);

                $find_upvote_num = M('fans_comment')->where("mc_id = ".$mc_id)->getField('upvote_num');
                return $this->json(0,'成功',['upvote_num'=>$find_upvote_num,'is_upvote'=>"0"]);
            }
            if($find_upvote_info['upvote'] == 0){
                $save_res = M('fans_comment_upvote')->where("uid = ".$uid." and mc_id = ".$mc_id)->setField('upvote',1);
                if(!$save_res){
                    return $this->json(-2,'失败！');
                }

                //取消点赞
                $save_res = M('fans_comment')->where("mc_id = ".$mc_id)->setInc('upvote_num',1);

                $find_upvote_num = M('fans_comment')->where("mc_id = ".$mc_id)->getField('upvote_num');
                return $this->json(0,'成功',['upvote_num'=>$find_upvote_num,'is_upvote'=>"1"]);
            }
        }else{
            $data['uid'] = $uid;
            $data['mc_id'] = $mc_id;
            $data['upvote'] = 1;
            $res = M('fans_comment_upvote')->add($data);
            if(!$res){
                return $this->json(-2,'失败！');
            }

            //修改评论的点赞数量
            $save_res = M('fans_comment')->where("mc_id = ".$mc_id)->setInc('upvote_num',1);

            $find_upvote_num = M('fans_comment')->where("mc_id = ".$mc_id)->getField('upvote_num');
            return $this->json(0,'成功',['upvote_num'=>$find_upvote_num,'is_upvote'=>"1"]);
        }

    }

    /**
     * 粉丝评论列表
     * */
    public function fans_comment_lists(){
        $moments_id = I('moments_id');
        $uid = I('uid');
        if(!$moments_id || !$uid){
            return $this->json(-1,'缺少参数！');
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查找没有回复的评论信息
        $find_comment_info = M('fans_comment')->where("moments_id = ".$moments_id." and reply_uid = 0")->limit($limit)->order('comment_time desc')->select();
        if(!$find_comment_info){
            return $this->json(-2,'没有更多评论了！');
        }

        //查询有回复的评论信息
        $find_comment_info2 = M('fans_comment')->where("moments_id = ".$moments_id." and reply_uid != 0")->select();

        //存放用户uid
        $user_ids_arr = array();
        foreach($find_comment_info as $key => $val){
            $user_ids_arr[] = $val['uid'];
        }
        foreach($find_comment_info2 as $key2 => $val2){
            $user_ids_arr[] = $val2['uid'];
        }
        $user_ids_arr = array_unique($user_ids_arr);

        $where['id'] = array('in',$user_ids_arr);
        $find_users_info = M('user')->where($where)->select();

        if($find_comment_info2){
            foreach($find_comment_info2 as $k1 => $v1){
                $find_comment_info2[$k1]['comment_time'] = date("Y-m-d H:i:s",$v1['comment_time']);
                foreach($find_users_info as $k2 => $v2){
                    if($v1['uid'] == $v2['id']){
                        if($v2['user_nickname']){
                            $find_comment_info2[$k1]['name'] = $v2['user_nickname'];
                            $find_comment_info2[$k1]['mobile'] = $v2['mobile'];
                        }else{
                            $find_comment_info2[$k1]['name'] = $v2['mobile'];
                            $find_comment_info2[$k1]['mobile'] = $v2['mobile'];
                        }
                    }
                    if($v1['reply_uid'] == $v2['id']){
                        if($v2['user_nickname']){
                            $find_comment_info2[$k1]['reply_name'] = $v2['user_nickname'];
                            $find_comment_info2[$k1]['reply_mobile'] = $v2['mobile'];
                        }else{
                            $find_comment_info2[$k1]['reply_name'] = $v2['mobile'];
                            $find_comment_info2[$k1]['reply_mobile'] = $v2['mobile'];
                        }
                    }
                }
            }
        }

        foreach($find_comment_info as $k1 => $v1){
            $find_comment_info[$k1]['comment_time'] = date("Y-m-d H:i:s",$v1['comment_time']);

            //判断自己是否给这条评论点赞
            $find_upvote_info = M('fans_comment_upvote')->where("uid = ".$uid." and mc_id = ".$v1['mc_id']." and upvote = 1")->find();
            if($find_upvote_info){
                $find_comment_info[$k1]['is_upvote'] = "1";
            }else{
                $find_comment_info[$k1]['is_upvote'] = "0";
            }

            foreach($find_users_info as $k2 => $v2){
                if($v1['uid'] == $v2['id']){
                    if($v2['user_nickname']){
                        $find_comment_info[$k1]['name'] = $v2['user_nickname'];
                        $find_comment_info[$k1]['mobile'] = $v2['mobile'];
                    }else{
                        $find_comment_info[$k1]['name'] = $v2['mobile'];
                        $find_comment_info[$k1]['mobile'] = $v2['mobile'];
                    }

                    $find_comment_info[$k1]['avatar'] = $v2['avatar'] ? $v2['avatar'] :  C('DEFAULT_AVATAR');
                }
                if($v1['reply_uid'] == $v2['id']){
                    if($v2['user_nickname']){
                        $find_comment_info[$k1]['reply_name'] = $v2['user_nickname'];
                        $find_comment_info[$k1]['reply_mobile'] = $v2['mobile'];
                    }else{
                        $find_comment_info[$k1]['reply_name'] = $v2['mobile'];
                        $find_comment_info[$k1]['reply_mobile'] = $v2['mobile'];
                    }
                }
            }

            if($find_comment_info2){
                foreach($find_comment_info2 as $k3 => $v3){
                    if($v1['mc_id'] == $v3['parent_id']){
                        $find_comment_info[$k1]['replay_list'][] = $find_comment_info2[$k3];
                    }
                }
            }

            if(empty($find_comment_info[$k1]['replay_list'])){
                $find_comment_info[$k1]['replay_list'] = array();
            }
        }


        return $this->json(0,'成功',$find_comment_info);

    }

    /**
     * 粉丝动态点赞列表
     * */
    public function fans_upvote_detail_list(){
        $moments_id = I('moments_id');

        if(!$moments_id){
            return $this->json(-1,'缺少参数！');
        }

        //查询给这条动态点赞的用户信息
        $find_upvote_info = M('fans_upvote')->where('moments_id = '.$moments_id)->select();
        if(!$find_upvote_info){
            return $this->json(-2,'没有更多信息');
        }

        //存放点赞用户的uid
        $uids_arr = array();
        foreach($find_upvote_info as $k => $v){
            $uids_arr[] = $v['uid'];
        }

        $page = I('page') ? I('page'):1;
        $row = 10;//每页显示的条数
        $limit = ($page-1)*$row.','.$row;

        //查找用户信息
        $where['id'] = array('in',$uids_arr);
        $field = 'id,user_nickname,avatar,mobile';
        $find_users_info = M('user')->where($where)->field($field)->limit($limit)->select();
        if(!$find_users_info){
            return $this->json(-2,'没有更多信息');
        }
        foreach ($find_users_info as $k => $v) {
            $find_users_info[$k]['avatar'] = $v['avatar'] ? $v['avatar'] : C('DEFAULT_AVATAR');
        }

        return $this->json(0,'成功',$find_users_info);

    }

    /**
     * 删除粉丝圈动态
     * */
    public function del_fans_moments(){
        $moments_id = I('moments_id');
        $moments_uid = I('uid');
        if(!$moments_id || !$moments_uid){
            return $this->json(-1,'缺少参数！');
        }

        $find_moments = M('fans_moments')->where("moments_id = ".$moments_id." and moments_uid = ".$moments_uid)->find();
        if(!$find_moments){
            return $this->json(-2,'未找到该动态信息');
        }

        //删除动态和相应图片、视频
        if($find_moments['moments_images']){
            //删除图片的时候要考虑群发给其他圈子的情况
            //查询朋友圈
            $find_moments_this_info = M('moments')->where("moments_uid = ".$moments_uid." and moments_images = '".$find_moments['moments_images']."'")->find();
            //查询人脉圈
            $find_con_circle_this_info = M('connection_circle')->where("cc_uid = ".$moments_uid." and cc_images = '".$find_moments['moments_images']."'")->find();
            if(!$find_moments_this_info && !$find_con_circle_this_info){
                $temp_img = explode(';',$find_moments['moments_images']);
                for($i=0;$i<count($temp_img);$i++){
                    if(file_exists(SITE_PATH.$temp_img[$i])){
                        unlink(SITE_PATH.$temp_img[$i]);
                    }
                }
            }
        }
        if($find_moments['video_path']){
            //删除视频的时候要考虑群发给其他圈子的情况
            //查询朋友圈
            $find_moments_this_info = M('moments')->where("moments_uid = ".$moments_uid." and video_path = '".$find_moments['video_path']."'")->find();
            //查询人脉圈
            $find_con_circle_this_info = M('connection_circle')->where("cc_uid = ".$moments_uid." and video_path = '".$find_moments['video_path']."'")->find();
            if(!$find_moments_this_info && !$find_con_circle_this_info){
                if(file_exists(SITE_PATH.$find_moments['video_path'])){
                    unlink(SITE_PATH.$find_moments['video_path']);
                }
            }
        }
        $del_res = M('fans_moments')->where("moments_id = ".$moments_id." and moments_uid = ".$moments_uid)->delete();
        if(!$del_res){
            return $this->json(-3,'删除失败');
        }

        //查找评论信息
        $find_comment_info = M('fans_comment')->where("moments_id = ".$moments_id)->select();
        if($find_comment_info){
            //存放所有的评论id
            $comment_ids_arr = array();
            foreach($find_comment_info as $k1 => $v1){
                $comment_ids_arr[] = $v1['mc_id'];
            }
            $where['mc_id'] = array('in',$comment_ids_arr);
            M('fans_comment_upvote')->where($where)->delete();
        }


        //删除成功后删除评论和点赞
        M('fans_comment')->where("moments_id = ".$moments_id)->delete();
        M('fans_upvote')->where("moments_id = ".$moments_id)->delete();

        return $this->json(0,'删除成功');

    }

    /**
     *删除粉丝圈评论
     * */
    public function del_fans_comment(){
        $mc_id = I('mc_id');
        $uid = I('uid');
        if(!$mc_id || !$uid){
            return $this->json(-1,'缺少参数！');
        }

        //查找该评论信息
        $find_comment_info = M('fans_comment')->where("mc_id = ".$mc_id." and uid = ".$uid)->find();

        if(!$find_comment_info){
            return $this->json(-2,'该评论不存在');
        }

        if($find_comment_info['parent_id']){//说明只删除自己的评论
            $del_res = M('fans_comment')->where("mc_id = ".$mc_id." and uid = ".$uid)->delete();
            if(!$del_res){
                return $this->json(-3,'删除失败');
            }else{
                return $this->json(0,'删除成功');
            }
        }else{//删除自己评论的同时删除给自己回复的评论
            $del_res = M('fans_comment')->where("mc_id = ".$mc_id." and uid = ".$uid)->delete();
            if(!$del_res){
                return $this->json(-3,'删除失败');
            }else{
                //删除和自己有关的评论
                M('fans_comment')->where("parent_id = ".$mc_id)->delete();
                return $this->json(0,'删除成功');
            }
        }



    }

    /**
     *粉丝群发消息
     * */
    public function fans_notice(){
        $uid = I('uid');
        $content = I('content');
        if(!$uid || !$content){
            return $this->json(-1,'缺少参数');
        }

        //查询关注自己的粉丝
        $find_my_fans = M('fans')->where('follow_uid = '.$uid)->select();
        if(!$find_my_fans){
            return $this->json(-2,'您还没有粉丝');
        }

        $uids_arr = array();
        foreach($find_my_fans as $k => $v){
            $uids_arr[]=$v['fans_uid'];
        }

        $where['id'] = array('in',$uids_arr);
        $find_users = M('user')->where($where)->select();
        $mobile_arr = array();
        foreach($find_users as $key => $val){
            $mobile_arr[]=$val['mobile'];
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
        $content = "您关注的人发来了消息：".$content;
        foreach($send_arr as $kk => $vv){
            foreach($send_arr[$kk] as $kk2 => $vv2) {
                $mobile = explode(",",$send_arr[$kk][$kk2]);
                //判断是好友还是人脉
                $find_res = M('user')->where('mobile = '.$send_arr[$kk][$kk2])->find();
                $find_friend_conn = M('user_friends')->where('uid = '.$uid.' and f_uid = '.$find_res['id'])->find();
                if($find_friend_conn){
                    $ext = array('type'=>'1','mobile'=>$send_arr[$kk][$kk2],'user_nickname'=>$find_myself_info['user_nickname'],'avatar'=>$find_myself_info['avatar'],'content'=>$content);
                }else{
                    $ext = array('type'=>'2','mobile'=>$send_arr[$kk][$kk2],'user_nickname'=>$find_myself_info['user_nickname'],'avatar'=>$find_myself_info['avatar'],'content'=>$content);
                }

                $res = $ease->sendText($find_myself_info['mobile'],'users',$mobile,$content,$ext);
            }
        }

        return $this->json(0,'成功');
    }

    /**
     *粉丝动态详情
     * */
    public function trends_show(){
        $moments_id = I('moments_id');
        $uid = I('uid');
        if(!$moments_id){
            return $this->json(-1,"缺少参数");
        }
        $trends_tmp = M('fans_moments')->where('moments_id = '.$moments_id)->find();
        if(!$trends_tmp){
            return $this->json(-2,"没有该文章");
        }
        if($trends_tmp['moments_images'] && !$trends_tmp['video_path']){
            $trends_tmp['moments_images'] = explode(';',$trends_tmp['moments_images']);  
            foreach($trends_tmp['moments_images'] as $k => $val){
                $trends_tmp['images'][$k] = "http://debo.shangtongyuntian.com".$val;
            }           
        }
        $trends_tmp['create_time'] = date("Y-m-d H:i:s",$trends_tmp['create_time']);
        // $create_time = $trends_tmp['create_time'];
        // if((time()-$create_time) < 60){
        //     $create_time_new = "刚刚";
        // }else if((time()-$create_time) >= 60 && (time()-$create_time) < 60*60){
        // }else if((time()-$create_time) >= 60 && (time()-$create_time) < 60*60){
        // }else if((time()-$create_time) >= 60 && (time()-$create_time) < 60*60){
        // }else if((time()-$create_time) >= 60 && (time()-$create_time) < 60*60){
        //     $create_time_new = ceil(((time()-$create_time)/60))."分钟前";
        // }else if((time()-$create_time) >= 60*60 && (time()-$create_time) < 60*60*24){
        //     $create_time_new = ceil(((time()-$create_time)/3600))."小时前";
        // }else if((time()-$create_time) >= 60*60*24 && (time()-$create_time) < 60*60*24*2){
        //     $create_time_new = "1天前";
        // }else if((time()-$create_time) >= 60*60*24*2 && (time()-$create_time) < 60*60*24*3){
        //     $create_time_new = "2天前";
        // }else{
        //     $create_time_new = date('Y-m-d',$create_time);
        // }
        if($trends_tmp['video_path']){
            $trends_tmp['video_path'] = "http://debo.shangtongyuntian.com".$trends_tmp['video_path'];
            $trends_tmp['video_img'] = "http://debo.shangtongyuntian.com".$trends_tmp['moments_images'];
            $trends_tmp['images'] = array();

            $aaa = getimagesize($this->path.$trends_tmp['moments_images']);
            $trends_tmp['video_img_width'] = "".$aaa[0];
            $trends_tmp['video_img_height'] = "".$aaa[1];
        }else{
            $trends_tmp['video_img'] = '';
            $trends_tmp['video_img_width'] = "";
            $trends_tmp['video_img_height'] = "";
        }
        $user_info = M('user')->field('id,user_nickname,small_avatar,mobile')->where('id = '.$trends_tmp['moments_uid'])->find();
        if(!$user_info){
            return $this->json(-3,"没有该用户");
        }
        if(empty($user_info['small_avatar'])) {
            $trends_tmp['avatar'] = C('DEFAULT_AVATAR');
        } else {
            $trends_tmp['avatar'] = "http://debo.shangtongyuntian.com".$user_info['small_avatar'];
        }
        $trends_tmp['user_nickname'] = $user_info['user_nickname'];
        $trends_tmp['mobile'] = $user_info['mobile'];
        //是否赞
        $info = M('fans_upvote')->where(['uid' =>$uid,'moments_id'=>$trends_tmp['moments_id']])->getfield('upvote');
        if($info){
            $trends_tmp['is_upvote'] = M('fans_upvote')->where(['uid' =>$uid,'moments_id'=>$trends_tmp['moments_id']])->getfield('upvote');
        }else{
            $trends_tmp['is_upvote'] = '0';
        }
        //查找粉丝动态评论数量
        $trends_tmp['comment_num'] = M('fans_comment')->where("moments_id = ".$trends_tmp['moments_id'])->group('parent_id')->count();
        if(!$trends_tmp['comment_num']){
            $trends_tmp['comment_num'] = '0';
        }
        //查找粉丝点赞数量
        $trends_tmp['upvote_num'] = M('fans_upvote')->where("moments_id = ".$trends_tmp['moments_id']." and upvote = 1")->count();
        unset($trends_tmp['moments_images']);
        $return_info[] = $trends_tmp;
        return $this->json(0,"成功",$return_info);
    }

    /**
     * 判断自己是否关注对方
     * */
    public function is_follow_with_interest(){
        $my_uid = I('my_uid');
        $follow_mobile = I('follow_mobile');
        if(!$my_uid || !$follow_mobile){
            return $this->json(-1,"缺少参数");
        }

        //查询用户信息
        $find_user_info = M('user')->where('mobile = '.$follow_mobile)->find();
        if(!$find_user_info){
            return $this->json(-2,"没找到该用户信息");
        }

        //查询粉丝关注情况
        $find_res = M('fans')->where('fans_uid = '.$my_uid.' and follow_uid = '.$find_user_info['id'])->find();
        if($find_res){
            $res['is_fans'] = '1';
        }else{
            $res['is_fans'] = '0';
        }

        return $this->json(0,"成功",$res);

    }

 }   

?>