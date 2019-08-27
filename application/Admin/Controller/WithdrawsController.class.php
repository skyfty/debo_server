<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
class WithdrawsController extends AdminbaseController
{
    //提现列表
    public function index()
    {
        $where = [];
        $state = I('state');
        $order_sn = I('content');
         if($state){
            $where['state'] = $pay_status;
        }
        if($order_sn)
        {
            $where['order_sn | uid'] = array('like','%'.$order_sn.'%');
        }
         $count = M('withdraw_cash')->where($where)->count();
        $page = $this->page($count,20);
        $withdraw = M('withdraw_cash')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
        foreach ($withdraw as $key => $val)
        {
            $withdraw[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
        }
        $state = M('withdraw_cash')->group('state')->select();
        $this->assign('state',$state);
        $this->assign('withdraw',$withdraw);
        $this->assign('page', $page->show('Admin'));
        $this->display();
    }
    //通过
    public function pass()
    {
        $w_id = I('w_id');
        if($w_id)
        {
            $data['reason'] = '';
            $data['state'] = 1;
            $data['success_time'] = time();
            $res = M('withdraw_cash')->where('w_id ='.$w_id)->save($data);
            $order_sn = M('withdraw_cash')->field('order_sn')->where('w_id ='.$w_id)->find();
            $transaction = M('transaction')->where('order_sn='.$order_sn['order_sn'])->setField('pay_status',2);
           
            if($res && $transaction){
                $this->success('通过成功！');
            }else{
                $this->error('通过失败！');
            }
        }else{
            $this->error('未收到id');
        }
    }
    //未通过
    public function nopass()
    {
        $w_id = I('w_id');
        $reason = I('texta');
        //var_dump($w_id);
        if($w_id && $reason)
        {
            $data['w_id'] = $w_id;
            $data['reason'] = $reason;
            $data['state'] = 2;
            $res = M('withdraw_cash')->where('w_id ='.$w_id)->save($data);
            if($res){
                echo 1;
            }else{
                echo 2;
            }
        }else{
            echo 3;
        }
    }
}