<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/24
 * Time: 16:52
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class BriberyController extends AdminbaseController{

    //ºì°üÁÐ±í
    public function index()
    {
        $where = [];
        $type = I('type');
        if($type){
            $where['ad_type'] = $type;
        }
        $count = M('bribery_money_record')->where($where)->count();
        $page = $this->page($count,20);
        $bribery = M('bribery_money_record')->where($where)->order('bmr_id desc')->limit($page->firstRow. ',' .$page->listRows)->select();

        foreach($bribery as $key => $val)
        {
            $bribery[$key]['name'] = M('user')->where('id = ' .$val['t_id'])->getfield('user_nickname');
            $bribery[$key]['user'] = M('user')->where('id = ' .$val['uid'])->getfield('user_nickname');
        }
        




        $briberya = M('bribery_money_record')->group('ad_type')->select();
        $this->assign('briberya',$briberya);
        $this->assign('bribery',$bribery);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //红包撤销
    public function cancel()
    {
        $where = [];
        $where['p_status'] = 3;
        $ad_type = I('adver1');
        $content = I('content');
        if($ad_type){
            $where['ad_type'] = $ad_type;
            //$where['p_status'] = 3;
        }
        if($content){
            $where['ad_content|title'] = $content;
           //ss $where['p_status'] = 3;
        }
        $count = M('touch')->where($where)->count();
        $page = $this->page($count,2);

        $c_bribery = M('touch')->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
        //var_dump($page);exit;
        foreach ($c_bribery as $key=>$val)
        {
        $c_bribery[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
        } 
        $adver = M('touch')->where($where)->group('ad_type')->select();
        

        $this->assign('c_bribery',$c_bribery);
        $this->assign('page',$page->show('Admin'));
        $this->assign('adver',$adver);
        $this->display();
    }
    //红包撤销通过
    public function pass()
    {
      $t_id = I('t_id');
      $uid = I('uid');
      $remaining_sum = I('remaining_sum');
      $result = M('touch')->where('t_id =' .$t_id)->setfield('p_status',5);
      $result1 = M('user')->where('id ='.$uid)->setInc('coins',$remaining_sum);
      if($result && $result1)
      {
      $this->success('通过成功');
      }else{
      $this->error('通过失败');
      }
      }
      //红包撤销不通过
      public function nopass()
      {
      $t_id = I('t_id');
      $result = M('touch')->where('t_id =' .$t_id)->setfield('p_status',6);
      if($result)
      {
      $this->success('不通过成功');
      }else{
      $this->error('不通过失败');
      }
      }



    }
