<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/7
 * Time: 9:35
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class CommentController extends AdminbaseController
{
    //人脉圈评论管理
    public function index()
    {
        $where = [];
        $comm = I('comm');
        $content = I('content');
        if($comm)
        {
            $where['state'] = $comm;
        }
        if($content)
        {
            $where['comment_content'] = $content;
        }
        $count = M('connection_comment')->where($where)->count();
        $page = $this->page($count, 20);

        $comment = M('connection_comment')->where($where)->order('con_comment_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($comment as $key => $val) {
            $comment[$key]['name'] = M('user')->where('id =' . $val['uid'])->getfield('user_nickname');
            $comment[$key]['reply_name'] = M('user')->where('id =' . $val['reply_uid'])->getfield('user_nickname');
        }
        $commen = M('connection_comment')->group('state')->select();
        $this->assign('comment', $comment);
        $this->assign('commen',$commen);
        $this->assign('page', $page->show('Admin'));
        $this->display();

    }

    //删除此条评论
    public function delete()
    {
        $con_comment_id = I('con_comment_id');
        if ($con_comment_id) {
            $res = M('connection_comment')->where('con_comment_id =' . $con_comment_id)->delete();
        } else {
            $this->error('未收到参数！');
            exit;
        }
        if (!$res) {
            $this->error('删除失败！');
            exit;
        } else {
            $this->success('删除成功！');
        }
    }

    //隐藏此条人脉圈
    public function hide()
    {
        $con_comment_id = I('con_comment_id');
        if ($con_comment_id) {
            $res = M('connection_comment')->where('con_comment_id =' . $con_comment_id)->setfield('state', 1);
        } else {
            $this->error('未收到参数！');
            exit;
        }
        if (!$res) {
            $this->error('隐藏失败！');
            exit;
        } else {
            $this->success('隐藏成功！');
        }
    }

    //隐藏此条人脉圈
    public function show()
    {
        $con_comment_id = I('con_comment_id');
        if ($con_comment_id) {
            $res = M('connection_comment')->where('con_comment_id =' . $con_comment_id)->setfield('state',0);
        } else {
            $this->error('未收到参数！');
            exit;
        }
        if (!$res) {
            $this->error('显示失败！');
            exit;
        } else {
            $this->success('显示成功！');
        }

    }

    //朋友圈评论管理
    public function moments()
    {
        $where = [];
        $comm = I('comm');
        $content = I('content');
        if($comm)
        {
            $where['state'] = $comm;
        }
        if($content)
        {
            $where['mc_content'] = $content;
        }
        $count = M('moments_comment')->where($where)->count();
        $page = $this->page($count, 20);

        $comment = M('moments_comment')->where($where)->order('mc_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($comment as $key => $val) {
            $comment[$key]['name'] = M('user')->where('id =' . $val['uid'])->getfield('user_nickname');
            $comment[$key]['reply_name'] = M('user')->where('id =' . $val['reply_uid'])->getfield('user_nickname');
        }
        $commen = M('moments_comment')->group('state')->select();
        $this->assign('comment', $comment);
        $this->assign('commen',$commen);
        $this->assign('page', $page->show('Admin'));
        $this->display();

    }

    //删除此条评论
    public function m_delete()
    {
        $mc_id = I('mc_id');
        if ($mc_id) {
            $res = M('moments_comment')->where('mc_id =' . $mc_id)->delete();
        } else {
            $this->error('未收到参数！');
            exit;
        }
        if (!$res) {
            $this->error('删除失败！');
            exit;
        } else {
            $this->success('删除成功！');
        }
    }

    //隐藏此条人脉圈
    public function m_hide()
    {
        $mc_id = I('mc_id');
        if ($mc_id) {
            $res = M('moments_comment')->where('mc_id =' . $mc_id)->setfield('state', 1);
        } else {
            $this->error('未收到参数！');
            exit;
        }
        if (!$res) {
            $this->error('隐藏失败！');
            exit;
        } else {
            $this->success('隐藏成功！');
        }
    }

    //隐藏此条人脉圈
    public function m_show()
    {
        $mc_id = I('mc_id');
        if ($mc_id) {
            $res = M('moments_comment')->where('mc_id =' . $mc_id)->setfield('state',0);
        } else {
            $this->error('未收到参数！');
            exit;
        }
        if (!$res) {
            $this->error('显示失败！');
            exit;
        } else {
            $this->success('显示成功！');
        }

    }
}