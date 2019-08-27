<?php
/**
 * Created by PhpStorm.
 * User: qcwl02
 * Date: 2017/11/20
 * Time: 10:30
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class VeriController extends AdminbaseController
{
    //实人认证管理
    public function index()
    {
        $where = [];

        $con = I('content');
        $field = 'de_identity_certification.id as ic_id,de_identity_certification.uid,de_identity_certification.real_name,de_identity_certification.id_number,de_identity_certification.cre_time,de_identity_certification.pass_time,de_identity_certification.state,de_user.id,de_user.user_nickname';
        if($con) {
            $where['de_user.id|de_user.user_nickname|de_identity_certification.real_name'] = $con;
            $count = M('identity_certification')->join('de_user on de_user.id = de_identity_certification.uid')->where($where)->count();
            $page = $this->page($count,20);
            $res = M('identity_certification')->join('de_user on de_user.id = de_identity_certification.uid')->where($where)->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }else{
            $where['de_user.uid|de_user.user_nickname|de_identity_certification.real_name'] = $con;
            $count = M('identity_certification')->join('de_user on de_user.id = de_identity_certification.uid')->count();
            $page = $this->page($count,20);
            $res = M('identity_certification')->join('de_user on de_user.id = de_identity_certification.uid')->field($field)->limit($page->firstRow.','.$page->listRows)->select();
        }

        $this->assign('res',$res);
        $this->assign('page',$page->show('Admin'));
        $this->display();

    }

    //审核通过
    public function pass()
    {
        $id = I('id');
        $uid = I('uid');
        $time = time();
        if($id)
        {
            $data['pass_time'] = time();
            $data['state'] = 2;
            $res = M('identity_certification')->where('id ='.$id)->save($data);
            if($uid)
            {
                $data1['uid'] = $uid;
                $data1['time'] = $time;
                $res1 = M('copartner')->add($data1);
            }else{
                $this->error('未收到uid！');
                exit;
            }
            if($res && $res1)
            {
                $this->success('审核成功！');
                exit;
            }else
            {
                $this->error('审核失败！');
                exit;
            }
        }else
        {
            $this->error('未收到参数！');
            exit;
        }
    }
    //不通过审核
    public function nopass()
    {
        $id = I('id');
        if($id)
        {
            $res = M('identity_certification')->where('id ='.$id)->delete();
            if($res)
            {
                $this->success('不通过成功！');
                exit;
            }else
            {
                $this->error('不通过失败！');
                exit;
            }
        }else
        {
            $this->error('未收到参数！');
            exit;
        }
    }
}