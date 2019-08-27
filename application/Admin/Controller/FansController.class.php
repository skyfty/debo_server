<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/3
 * Time: 16:42
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class FansController extends AdminbaseController
{
    public $path = "http://debo.shangtongyuntian.com";
//粉丝列表
    public function index()
    {
        $where = [];
        $content = I('content');
        if($content)
        {
            $where['fans_uid|follow_uid'] = $content;
        }
        $count = M('fans')->where($where)->count();
        $page = $this->page($count,20);
        $fans = M('fans')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
        foreach ($fans as $key => $val)
        {
            $fans[$key]['name'] = M('user')->where('id ='.$val['fans_uid'])->getfield('user_nickname');
            $fans[$key]['followname'] = M('user')->where('id ='.$val['follow_uid'])->getfield('user_nickname');
        }
        $this->assign('fans',$fans);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //解除关系
    public function delete()
    {
        $fans_uid = I('fans_uid');
        if($fans_uid){
            $res = M('fans')->where('fans_uid ='.$fans_uid)->delete();
        }else{
            $this->error('参数错误！');
        }
        if($res)
        {
            $this->success('删除成功！');

        }else{
            $this->error('删除失败！');
        }
    }
    //粉丝文章列表
    public function message()
    {
        $where = [];
        $content = I('content');
        $mess = I('mess');

        if($mess){
            $where['type'] = $mess;
        }
        if($content){
            $where['title|content'] = array('like','%'.$content.'%');
        }
        $count = M('fans_message')->where($where)->count();
        $page = $this->page($count,20);
        $message = M('fans_message')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
        foreach ($message as $key => $val)
        {
            $message[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
            $message[$key]['img'] = $this->path.explode(';',$val['img'])[0];
        }
        $mess1 = M('fans_message')->group('type')->select();
        $this->assign('message',$message);
        $this->assign('mess1',$mess1);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //粉丝文章删除
    public function m_delete()
    {
        $m_id = I('m_id');
        if($m_id){
            $res = M('fans_message')->where('m_id ='.$m_id)->delete();
        }else{
            $this->error('参数错误！');
        }
        if($res){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }





}