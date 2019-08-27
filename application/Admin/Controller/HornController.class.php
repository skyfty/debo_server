<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/4
 * Time: 10:29
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class HornController extends AdminbaseController
{
    //喇叭数据列表
    public function index()
    {
        $where = [];
        $content = I('content');
        if($content){
            $where['horn_distance'] = $content;
        }

        $count = M('horn_data')->where($where)->count();
        $page = $this->page($count,20);
        $horn = M('horn_data')->where($where)->limit($page->firstRow.','.$page->listRows)->select();

        $this->assign('horn',$horn);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //修改喇叭数据
    public function edit()
    {
        $horn_id = I('horn_id');
        if($horn_id)
        {
            $where['horn_id'] = $horn_id;
        }else{
            $this->error('未收到id！');
            exit;
        }

        $horn = M('horn_data')->where($where)->find();
        $free = M('horn_data')->group('is_free')->select();
        $this->assign('horn',$horn);
        $this->assign('free',$free);

        $this->display();
    }
    //接收修改数据
    public function edit_post()
    {
        $data = I('post.');
        if($data){
            $res = M('horn_data')->where('horn_id ='.$data['horn_id'])->save($data);


        }else{
            $this->error('未收到数据！');
        }
        if($res){
            $this->success('修改成功！');
        }else{
            $this->error('修改失败！');
        }

    }
    //删除数据
    public function delete()
    {
        $horn_id = I('horn_id');
        if($horn_id)
        {
            $where['horn_id'] = $horn_id;
        }else{
            $this->error('未收到id！');
            exit;
        }

        $res = M('horn_data')->where($where)->delete();
        if(!$res){
            $this->error('删除失败！');
            exit;
        }
        $this->success('删除成功！');
    }
//喇叭购买记录
    public function purchase()
    {
        $where = [];
        $count = M('horn')->where($where)->count();
        $page = $this->page($count,20);
        $horn = M('horn')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
        foreach ($horn as $key => $val)
        {
            $horn[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
        }
        $this->assign('horn',$horn);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
}