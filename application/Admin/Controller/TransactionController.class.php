<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/7
 * Time: 11:46
 */

namespace Admin\Controller;

use Common\Controller\AdminbaseController;
class TransactionController extends AdminbaseController
{
    //订单列表
    public function index()
    {
        $where = [];
        $pay_status = I('trans');
        $order_sn = I('content');
        if($pay_status){
            $where['pay_status'] = $pay_status;
        }
        if($order_sn)
        {
            $where['order_sn'] = array('like','%'.$order_sn.'%');
        }
        $count = M('transaction')->where($where)->count();
        $page = $this->page($count,20);
        $transaction = M('transaction')->where($where)->limit($page->firstRow.','.$page->listRows)->order('create_time desc')->select();
        foreach ($transaction as $key => $val)
        {
            $transaction[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
        }
        $type = M('transaction')->group('pay_type')->select();
        $this->assign('transaction',$transaction);
        $this->assign('type',$type);
        $this->assign('page', $page->show('Admin'));
        $this->display();
    }
    //订单反馈
    public function feedback()
    {
    $count = M('feedback')->where($where)->count();
    $page = $this->page($count,20);
    $res = M('feedback')->where($where)->limit($page->firstRow.','.$page->listRows)->order('create_time desc')->select();
    foreach ($res as $key => $val)
    {
        $res[$key]['order_price'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('order_sn');
        $res[$key]['pay_status'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('pay_status');
        $res[$key]['pay_type'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('pay_type');
        $res[$key]['pay_time'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('pay_time');
        $res[$key]['indent'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('indent');
        $res[$key]['object_id'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('object_id');
        $res[$key]['con_uid'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('con_uid');
        $res[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
    }
        
        $this->assign('page', $page->show('Admin'));
        $this->assign('res',$res);
        $this->display();
    }
    //接收feedback页面ajax传递的id，并查出信息
    public function information()
    {
    $fb_id = I('fb_id');
    if($fb_id)
    {
    $res = M('feedback')->where('fb_id ='.$fb_id)->find();
    $order_info = M('transaction')->where('order_sn = '.$res['order_sn'])->find();
    /*foreach ($res as $key => $val)
    {
        $res[$key]['order_price'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('order_sn');
        $res[$key]['pay_status'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('pay_status');
        $res[$key]['pay_type'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('pay_type');
        $res[$key]['pay_time'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('pay_time');
        $res[$key]['indent'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('indent');
        $res[$key]['object_id'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('object_id');
        $res[$key]['con_uid'] = M('transaction')->where('order_sn ='.$val['order_sn'])->getfield('con_uid');
        $res[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
    }*/
    //var_dump($res[$key]['indent']);
    }
    //$this->assign('res',$res);
    $this->assign('res',$order_info);
    $this->display();
    }
}