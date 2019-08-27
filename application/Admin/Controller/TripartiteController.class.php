<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class TripartiteController extends AdminbaseController{
	
	//三方客记录表
	public function index()
	{
		$where = [];

		$count = M('Tripartite')->where($where)->count();
		$page = $this->page($count,20);

		$res = M('Tripartite')->where($where)->select();
		foreach ($res as $key => $val)
		{
		$res[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
		$res[$key]['con_name'] = M('user')->where('id = '.$val['uid'])->getfield('user_nickname');
		}
		$this->assign('res',$res);
		$this->assign('page',$page->show('Admin'));
		$this->display();
	}
	//通过
	public function finish()
	{
	$tri_id = I('tri_id');
	$con_uid = I('con_uid');
	$price = I('price');

	$res1 = M('tripartite')->where('tri_id ='.$tri_id)->setfield('tri_state',3);
	$res2 = M('user')->where('id ='.$con_uid)->setInc('coins',$price);
	if($res1 && $res2)
	{
	$this->success('成功，已完成');
	}else{
	$this->error('完成失败');
	}
	}
	//失败
	public function cancle()
	{
	$tri_id = I('tri_id');
	$uid = I('uid');
	$price = I('price');
    //var_dump($price);die;
	$res1 = M('tripartite')->where('tri_id ='.$tri_id)->setfield('tri_state',4);
	$res2 = M('user')->where('id ='.$uid)->setInc('coins',$price);
	if($res1 && $res2)
	{
	$this->success('成功，取消成功');
	}else{
	$this->error('取消失败');
	}
	}

}