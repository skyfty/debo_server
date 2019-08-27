<?php
/**
 * Created by PhpStorm.
 * User: qcwl02
 * Date: 2017/11/18
 * Time: 14:14
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class PennybankController extends AdminbaseController
{
    //存储罐用户
    public function index()
    {
        $where = [];
        $con = I('content');
        if($con){

            $where['de_storage.uid|de_user.user_nickname'] = I('content');
            $count = M('storage')->join('de_user on de_user.id = de_storage.uid')->where($where)->count();
            $page = $this->page($count,20);
            $pennybank = M('storage')->join('de_user on de_user.id = de_storage.uid')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
        }else{
            $count = M('storage')->join('de_user on de_user.id = de_storage.uid')->count();
            $page = $this->page($count,20);
            $pennybank = M('storage')->join('de_user on de_user.id = de_storage.uid')->limit($page->firstRow.','.$page->listRows)->select();
        }


        $this->assign('pennybank',$pennybank);
        $this->assign('page',$page->show('Admin'));
        $this->display();

    }
    //更改余额
    public function change()
    {
        $id = I('id');
        $info = M('storage')->where('uid ='.$id)->find();

        if(IS_POST) {
            $data['money'] = I('money');

            $res = M('storage')->where('id ='.$id)->save($data);
            if ($res) {
                $this->success('修改成功');
                exit;
            }else{

                $this->error('修改失败');
                exit;
            }
        }

        $this->assign('info',$info);
        $this->display();
    }
    //存储罐每日利率
    public function income()
    {
        $where = [];
        $con = I('content');
        if($con){

            $where['de_storage_settle_account.uid|de_user.user_nickname|de_user.mobile'] = I('content');
            $count = M('storage_settle_account')->join('de_user on de_user.id = de_storage_settle_account.uid')->where($where)->count();
            $page = $this->page($count,20);
            $income = M('storage_settle_account')->join('de_user on de_user.id = de_storage_settle_account.uid')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
        }else{
            $count = M('storage_settle_account')->join('de_user on de_user.id = de_storage_settle_account.uid')->count();
            $page = $this->page($count,20);
            $income = M('storage_settle_account')->join('de_user on de_user.id = de_storage_settle_account.uid')->limit($page->firstRow.','.$page->listRows)->select();
        }
        $this->assign('income',$income);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
}