<?php
/**
 * Created by PhpStorm.
 * User: qcwl02
 * Date: 2018/1/18
 * Time: 13:51
 */

namespace Admin\Controller;


use Common\Common\Easemob;
use Common\Controller\AdminbaseController;

class ChatroomController extends AdminbaseController
{
        //展示页面
    public function index()
    {
        $where = [];
        $content = I('content');

        if($content)
        {
            $where['city'] = array('like',$content);
        }
        $_GET['content'] = $content;
        $count = M('chatroom_record')->where($where)->count();
        $page = $this->page($count,20);
        $res = M('chatroom_record')->where($where)->limit($page->firstRow. ',' .$page->listRows)->select();
        $this->assign('res',$res);
        $this->assign('page', $page->show('Admin'));
        $this->display();
    }
    //添加聊天室
    public function add()
    {
        $this->display();
    }
    public function add_post()
    {
        $chatroom_name = I('chatroom_name');
        $city = I('city');

        if(!$chatroom_name || !$city)
        {
          $this->error('信息输入不全');
          exit;
        }
        //查询城市是否已经开通

        $res = M('chatroom_record')->where('city = "'.$city.'"')->find();

        if($res)
        {
            $this->error('已经添加过该城市');
            exit;
        }
        $ease = new Easemob();
        $options = array(
          'name' => $chatroom_name.'聊天室',
            'maxusers' => 2000,
            'owner' => '18788888888'
        );
        $create_chatroom = $ease->createChatRoom($options);
       if($create_chatroom['data']['id'])
       {
           $chatroom_id = $create_chatroom['data']['id'];
       }else{
           $this->error('创建聊天室失败');
           exit;
       }
       $data['chatroom_name'] = $chatroom_name;
       $data['city'] = $city;
       $data['chatroom_id'] = $chatroom_id;
       $data['room_avatar'] = "http://debo.shangtongyuntian.com/data/upload/chatroom/small_59c25f4ef10002.55030992.jpg";
       $data['time'] = time();
       $data['status'] = 0;

       $add_chatroom = M('chatroom_record')->add($data);
       if($add_chatroom)
       {
           $this->success('创建聊天室成功');
           exit;
       }else{
           $this->error('创建聊天室失败');
           exit;
       }
    }
        //关闭聊天室
        public function stop()
    {
            $chatroom_id = I('chatroom_id');

            if($chatroom_id)
            {
                $res = M('chatroom_record')->where('chatroom_id='.$chatroom_id)->setField('status',1);
                $this->success('关闭成功');
                exit;
            }else{
                $this->error('关闭失败');
            }

    }
    //开启聊天室
    public function start()
    {
        $chatroom_id = I('chatroom_id');

        if($chatroom_id)
        {
            $res = M('chatroom_record')->where('chatroom_id='.$chatroom_id)->setField('status',0);
            $this->success('开启成功');
            exit;
        }else{
            $this->error('开启失败');
        }

    }
    //解散聊天室
    public function delete()
    {
        $chatroom_id = I('chatroom_id');

        if($chatroom_id)
        {
            $ease = new Easemob();
            $res = $ease->deleteChatRoom($chatroom_id);
            if($res['data']['success'])
            {
                $del = M('chatroom_record')->where('chatroom_id='.$chatroom_id)->delete();
                if($del)
                {
                    $this->success('解散聊天室成功');
                    exit;
                }else{
                    $this->error('解散聊天室失败');
                    exit;
                }
            }else{
                $this->error('删除聊天室失败');
                exit;
            }
        }
    }


}