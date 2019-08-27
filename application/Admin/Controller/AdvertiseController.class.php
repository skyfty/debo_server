<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/31
 * Time: 10:46
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class AdvertiseController extends AdminbaseController
{
    protected $img = "http://debo.shangtongyuntian.com";
//广告列表
    public function index()
    {
        $where = [];
        $ad_type = I('adver1');
        $content = I('content');
        if($ad_type){
            $where['ad_type'] = $ad_type;
        }
        if($content){
            $where['ad_content|title'] = $content;
        }

        $count = M('touch')->where($where)->count();
        $page = $this->page($count,20);
        $advertise = M('touch')->where($where)->order('t_id desc')->limit($page->firstRow.','.$page->listRows)->select();
        foreach ($advertise as $key => $val)
        {
            $advertise[$key]['name'] = M('user')->where('id = '.$val['uid'])->getfield('user_nickname');
            $advertise[$key]['images'] = $this->img.explode(';',$val['ad_images'])[0];
        }
        $adver = M('touch')->group('ad_type')->select();
        $this->assign('advertise',$advertise);
        $this->assign('adver',$adver);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
//广告删除
    public function delete()
    {
        $t_id = I('t_id');
        if($t_id){
            $res = M('touch')->where('t_id = '.$t_id)->delete();
        }else{
            $this->error('参数不存在');
        }
        if($res){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败!');
        }

    }
//广告审核
    public function veri()
    {
        $where = [];
        $ad_type = I('adver1');
        $content = I('content');
        if($ad_type){
            $where['ad_type'] = $ad_type;
        }
        if($content){
            $where['ad_content|title'] = array('like','%'.$content.'%');

        }

        $where['p_status'] = 1;
        $count = M('touch')->where($where)->count();
        $page = $this->page($count,20);
        $advertise = M('touch')->where($where)->order('t_id desc')->limit($page->firstRow.','.$page->listRows)->select();
        foreach ($advertise as $key => $val)
        {
            $advertise[$key]['name'] = M('user')->where('id = '.$val['uid'])->getfield('user_nickname');
            $advertise[$key]['images'] = $this->img.explode(';',$val['ad_images'])[0];
        }
        $adver = M('touch')->group('ad_type')->select();
        $this->assign('advertise',$advertise);
        $this->assign('adver',$adver);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
//通过审核
    public function pass()
    {
        $t_id = I('t_id');
        if(!$t_id)
        {
            $this->error('未收到参数！');
            exit;
        }
        $res = M('touch')->where('t_id = ' .$t_id)->setfield('p_status',2);
        if(!$res)
        {
            $this->error('通过失败！');
            exit;
        }
        $this->success('通过成功！');
    }
//审核不通过
    public function nopass()
    {
        $t_id = I('t_id');
        if(!$t_id)
        {
            $this->error('未收到参数！');
            exit;
        }
        $res = M('touch')->where('t_id = ' .$t_id)->setfield('p_status',4);
        if(!$res)
        {
            $this->error('不通过失败！');
            exit;
        }
        $this->success('不通过成功！');
    }
//添加广告
    public function add()
    {

        $this->display();

    }
    public function add_post()
    {
        $data['uid'] = I('uid');
        $data['title'] = I('title');
        $data['ad_link'] = I('ad_link');
        $data['price'] = I('price');
        $data['p_num'] = I('p_num');
        $data['ad_content'] = I('ad_content');
        $data['ad_images'] = $_FILES;

        $data['ad_type'] = I('get.ad_type');
        $data['remaining_sum'] =I('price');
        $data['remain_num'] = I('p_num');
        $data['start_time'] = strtotime(I('start_time'));
        $data['time_range'] = strtotime(I('time_range'));
        $data['p_time'] = time();

        $res = M('touch')->add($data);
        if(!$res)
        {
        $this->error('添加失败！');
        exit;
        }
        $this->success('添加成功！');
    }


}