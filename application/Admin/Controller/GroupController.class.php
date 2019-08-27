<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/4
 * Time: 16:36
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;
use Common\Common\Easemob;

class GroupController extends AdminbaseController
{
    protected $img = "http://debo.shangtongyuntian.com";
    //群组管理
    public function index()
    {
        $where = [];
        $content = I('content');
        if($content){
            $where['group_id|g_mobile'] = $content;
        }

        $count = M('chatgroup')->where($where)->count();
        $page = $this->page($count,20);
        $group = M('chatgroup')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
        foreach ($group as $key => $val){
            $group[$key]['g_avatar'] = $this->img .$val['g_avatar'];
        }

        $this->assign('group',$group);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //解散群组
    public function delete()
    {
        $group_id = I('group_id');
        if($group_id)
        {
            $ease = new Easemob();
            $delete_result = $ease->deleteGroup($group_id);
            if(!$delete_result['data']['success'])
            {
                $this->error('环信解散失败');
            }
            $res = M('chatgroup_member')->where('group_id ='.$group_id)->delete();
        }else{
            $this->error('清除组员失败！');
            exit;
        }
        if($res){
            $info = M('chatgroup')->where('group_id ='.$group_id)->delete();
        }else{
            $this->error('清除群组失败！');
        }
        if($res && $info){
            $this->success('解散群组成功！');
        }
    }

    //群成员管理
    public function member()
    {
        $where = [];
        $content = I('content');
        $type = I('type');

        $_GET['type'] = $type;
        $_GET['content'] = $content;

        if($content){
            $where['group_id|mobile'] = array('like','%'.$content.'%');

        }
        if($type)
        {
            $where['member_type'] = $type;
        }

        $count = M('chatgroup_member')->where($where)->count();
        $page = $this->page($count,20);
        $member = M('chatgroup_member')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
        foreach ($member as $key => $val){
            $member[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
        }

        $type1 = M('chatgroup_member')->group('member_type')->select();
        $this->assign('member',$member);
        $this->assign('type1',$type1);
        $this->assign('page',$page->show('Admin'));
        $this->assign('type',$type);
        $this->display();
    }
    //将成员踢出群组
    public function m_delete()
    {
        $uid = I('uid');//此uid为群成员的环信id，即用户手机号
        $group_id = I('group_id');

//        $uid = '18301504724';
//        $group_id = '38568630222851';

        if($uid && $group_id )
        {
            $where['mobile'] = $uid;
            $where['group_id'] = $group_id;
        }else{
            $this->error('参数有误！');
            exit;
        }
        $ease = new Easemob();
        $delete_res = $ease->deleteGroupMember($group_id,$uid);
        if(!$delete_res['data']['result'])
        {
            $this->error('环信删除失败');
        }

        $res = M('chatgroup_member')->where($where)->delete();
        if($res)
        {
            $this->success('剔出成功！');
        }else{
            $this->error('剔出失败！');
        }
    }
    //解除管理员权限
    public function m_manage()
    {
        $uid = I('uid');
        $group_id = I('group_id');
        if($uid && $group_id )
        {
            $where['uid'] = $uid;
            $where['group_id'] = $group_id;
        }else{
            $this->error('参数有误！');
            exit;
        }
        $res = M('chatgroup_member')->where($where)->setfield('member_type',3);
        if($res)
        {
            $this->success('解除成功！');
        }else{
            $this->error('解除失败！');
        }
    }
}