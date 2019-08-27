<?php
/**
 * Created by PhpStorm.
 * User: qcwl02
 * Date: 2017/11/20
 * Time: 17:06
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class CopartnerController extends AdminbaseController
{
    //合伙人列表
    public function index()
    {
        $where = [];

        $con = I('content');
        $field = 'de_copartner.id as c_id,de_copartner.uid,de_copartner.time,de_copartner.debo_coins,de_copartner.purchase_limit,de_user.id,de_user.user_nickname';
        if($con)
        {
            $where['de_user.id|de_user.user_nickname'] = $con;
            $count = M('copartner')->join('de_user on de_user.id = de_copartner.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('copartner')->join('de_user on de_user.id = de_copartner.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }else{
            $count = M('copartner')->join('de_user on de_user.id = de_copartner.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('copartner')->join('de_user on de_user.id = de_copartner.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }

        $this->assign('res',$res);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //修改用户余额/额度
    public function change()
    {
        $id = I('id');
        if($id)
        {
            $info = M('copartner')->where('id ='.$id)->find();
        }else{
            $this->error('参数错误！');
        }
        $this->assign('info',$info);
        $this->display();
    }
    public function change_edit()
    {
        $id = I('id');
        $debo_coins = I('debo_coins');
        $purchase_limit = I('purchase_limit');
        if($id && $debo_coins && $purchase_limit)
        {
            $where['id'] = $id;
            $data['debo_coins'] = $debo_coins;
            $data['purchase_limit'] = $purchase_limit;
            $res = M('copartner')->where($where)->save($data);
            if($res)
            {
                $this->success('修改成功！');
                exit;
            }else{
                $this->error('修改失败！');
                exit;
            }
        }else{
            $this->error('参数错误！');
            exit;
        }
    }
    //删除合伙人
    public function delete()
    {
        $id = I('id');
        if($id)
        {
            $res = M('copartner')->where('id ='.$id)->delete();
        }
        if($res)
        {
            $this->success('删除成功！');
            exit;
        }else{
            $this->error('删除失败！');
            exit;
        }
    }
    //嘚啵币购买记录表
    public function purchase_list()
    {
        $where = [];

        $con = I('content');
        $field = 'de_user.id as u_id,de_user.user_nickname,de_debo_coins_purchase.*';
        if($con)
        {
            $where['de_debo_coins_purchase.uid|de_user.user_nickname'] = $con;
            $count = M('debo_coins_purchase')->join('de_user on de_user.id = de_debo_coins_purchase.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('debo_coins_purchase')->join('de_user on de_user.id = de_debo_coins_purchase.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }else{
            $count = M('debo_coins_purchase')->join('de_user on de_user.id = de_debo_coins_purchase.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('debo_coins_purchase')->join('de_user on de_user.id = de_debo_coins_purchase.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }
        $this->assign('res',$res);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //嘚啵币每日收益记录表
    public function income()
    {
        $where = [];

        $con = I('content');
        $field = 'de_user.id as u_id,de_user.user_nickname,de_debo_coins_settle_accounts.*';
        if($con)
        {
            $where['de_debo_coins_settle_accounts.uid|de_user.user_nickname'] = $con;
            $count = M('debo_coins_settle_accounts')->join('de_user on de_user.id = de_debo_coins_settle_accounts.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('debo_coins_settle_accounts')->join('de_user on de_user.id = de_debo_coins_settle_accounts.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }else{
            $count = M('debo_coins_settle_accounts')->join('de_user on de_user.id = de_debo_coins_settle_accounts.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('debo_coins_settle_accounts')->join('de_user on de_user.id = de_debo_coins_settle_accounts.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }
        $this->assign('res',$res);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //嘚啵币商城表
    public function shop()
    {
        $where = [];

        $con = I('content');
        $field = 'de_user.id as u_id,de_user.user_nickname,de_debo_coins_shop.*';
        if($con)
        {
            $where['de_debo_coins_shop.uid|de_user.user_nickname'] = $con;
            $count = M('debo_debo_coins_shop')->join('de_user on de_user.id = de_debo_coins_shop.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('debo_debo_coins_shop')->join('de_user on de_user.id = de_debo_coins_shop.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }else{
            $count = M('debo_coins_shop')->join('de_user on de_user.id = de_debo_coins_shop.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('debo_coins_shop')->join('de_user on de_user.id = de_debo_coins_shop.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }
        $this->assign('res',$res);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
}