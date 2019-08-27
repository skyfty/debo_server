<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/25
 * Time: 10:57
 */

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class CircleController extends AdminbaseController
{
    protected $img = "http://debo.shangtongyuntian.com";

    //人脉圈管理
    public function index()
    {
        $where = [];
        $content = I('content');
        if($content)
        {
            $where['cc_content'] = array('like','%'.$content.'%');
        }
        $count = M('connection_circle')->where($where)->count();
        $page = $this->page($count, 20);
        $circle = M('connection_circle')->where($where)->order('cc_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($circle as $key => $val) {
            $circle[$key]['cc_images'] = $this->img . explode(';', $val['cc_images'])[0];
            $circle[$key]['name'] = M('user')->where('id='.$val['cc_uid'])->getfield('user_nickname');
        }
        $this->assign('circle', $circle);
        $this->assign('page', $page->show('Admin'));
        $this->display();
    }

    //删除人脉圈的同时删除评论和点赞
    public function delete()
    {
        $id = I('cc_id');
        if ($id) {
            $find_info = M('connection_circle')->where('cc_id = ' . $id)->find();
            $temp_img = explode(';', $find_info['cc_images']);
            for ($i = 0; $i < count($temp_img); $i++) {
                if (file_exists(SITE_PATH . $temp_img[$i])) {
                    unlink(SITE_PATH . $temp_img[$i]);
                }
            }
            $res = M('connection_circle')->where('cc_id = ' . $id)->delete();

        } else {
            $this->error('数据不存在');
            exit;
        }
        if (!$res) {
            $this->error('删除失败');
            exit;
        } else {
            $res1 = M('connection_comment')->where('cc_id = ' . $id)->delete();
            $res2 = M('connection_upvote')->where('cc_id = ' . $id)->delete();
            $this->success('删除成功');
        }
    }

    //朋友圈管理
    public function moments()
    {
        $where = [];
        $content = I('content');
        if($content)
        {
            $where['moments_content'] = array('like','%'.$content.'%');
        }
        $count = M('moments')->where($where)->count();
        $page = $this->page($count, 20);
        $moments = M('moments')->where($where)->order('moments_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($moments as $key => $val) {
            $moments[$key]['moments_images'] = $this->img . explode(';', $val['moments_images'])[0];
            $moments[$key]['name'] = M('user')->where('id='.$val['moments_uid'])->getfield('user_nickname');
        }
        $this->assign('moments', $moments);
        $this->assign('page', $page->show('Admin'));
        $this->display();

    }

    //删除朋友圈
    public function m_delete()
    {
        $id = I('moments_id');
        if($id){
            $find_info = M('moments')->where('moments_id = ' . $id)->find();
            $temp_img = explode(';', $find_info['moments_images']);
            for ($i = 0; $i < count($temp_img); $i++) {
                if (file_exists(SITE_PATH . $temp_img[$i])) {
                    unlink(SITE_PATH . $temp_img[$i]);
                }
            }
            $res = M('moments')->where('moments_id = ' . $id)->delete();
    }else{
        $this->error('数据不存在');
        exit;
        }
        if(!$res){
            $this->error('删除失败');
            exit;
        }else{
            $res1 = M('moments_comment')->where('moments_id = ' . $id)->delete();
            $res2 = M('moments_upvote')->where('moments_id = ' . $id)->delete();
            $this->success('删除成功');
        }
    }
    //粉丝圈管理
    public function fans()
    {
     $where = [];
        $content = I('content');
        if($content)
        {
            $where['moments_content'] = array('like','%'.$content.'%');
        }
        $count = M('fans_moments')->where($where)->count();
        $page = $this->page($count, 20);
        $moments = M('fans_moments')->where($where)->order('moments_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($moments as $key => $val) {
            $moments[$key]['moments_images'] = $this->img . explode(';', $val['moments_images'])[0];
            $moments[$key]['name'] = M('user')->where('id='.$val['moments_uid'])->getfield('user_nickname');
        }
        $this->assign('moments', $moments);
        $this->assign('page', $page->show('Admin'));
        $this->display();
    }
    public function f_delete()
    {
     $id = I('moments_id');
        if ($id) {
            $find_info = M('fans_moments')->where('moments_id = ' . $id)->find();
            $temp_img = explode(';', $find_info['moments_images']);
            for ($i = 0; $i < count($temp_img); $i++) {
                if (file_exists(SITE_PATH . $temp_img[$i])) {
                    unlink(SITE_PATH . $temp_img[$i]);
                }
            }
            $res = M('fans_moments')->where('moments_id = ' . $id)->delete();

        } else {
            $this->error('数据不存在');
            exit;
        }
        if (!$res) {
            $this->error('删除失败');
            exit;
        } else {
            $res1 = M('fans_comment')->where('moments_id = ' . $id)->delete();
            $res2 = M('fans_upvote')->where('moments_id = ' . $id)->delete();
            $this->success('删除成功');
        }
    }
}