<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class ComplainController extends AdminbaseController{
	protected $img = "http://123.57.148.47/debo";
	//投诉列表
	public function index(){
		$where = [];
		$where['state'] = 0;
		$circle_type = I('type');
		if($circle_type){
			$where['circle_type'] = $circle_type;
		}

		$count = M('circle_complain')->where($where)->count();
		$page = $this->page($count,20);
		$complain= M('circle_complain')->where($where)->limit($page->firstRow. ',' .$page->listRows)->select();
		foreach ($complain as $key=>$val){
			$complain[$key]['name'] = M('user')->where('id='.$val['uid'])->getfield('user_nickname');
			if($val['circle_type'] == 1){
				$complain[$key]['contenta'] = M('moments')->where('moments_id ='.$val['moments_id'])->getfield('moments_content');
				$complain[$key]['img'] = M('moments')->where('moments_id ='.$val['moments_id'])->getfield('moments_images');
				$complain[$key]['images'] = $this->img.explode(';',$val['img'])[0];
			}elseif($val['circle_type'] == 2){
				$complain[$key]['contenta'] = M('connection_circle')->where('cc_id ='.$val['moments_id'])->getfield('cc_content');
				$complain[$key]['img'] = M('connection_circle')->where('cc_id ='.$val['moments_id'])->getfield('cc_images');
				$complain[$key]['images'] = $this->img.explode(';',$val['img'])[0];
			}elseif($val['circle_type'] == 3){
				$complain[$key]['contenta'] = M('fans_moments')->where('moments_id ='.$val['moments_id'])->getfield('moments_content');
				$complain[$key]['img'] = M('fans_moments')->where('moments_id ='.$val['moments_id'])->getfield('moments_images');
				$complain[$key]['images'] = $this->img.explode(';',$val['img'])[0];
			}
		}

		$complaina= M('circle_complain')->group('circle_type')->select();
		$this->assign('complaina',$complaina);
		$this->assign('complain',$complain);
		$this->assign('page',$page->show('Admin'));
		$this->display();
	}
	public function delete(){
		$circle_type = I('circle_type');
		$moments_id = I('moments_id');
		$id = I('id');
		$res = M('circle_complain')->where('id=' .$id)->setfield('state',1);
		if($circle_type == 1){
			$res1 = M('moments')->where('moments_id='.$moments_id)->delete();
		}elseif($circle_type == 2){
			$res1 = M('connection_circle')->where('cc_id='.$moments_id)->delete();
		}elseif($circle_type ==3){
			$res1 = M('fans_moments')->where('moments_id='.$moments_id)->delete();
		}


		if($res && $res1){
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！');
		}

	}
	public function saved()
	{
		$id = I('id');
		$res = M('circle_complain')->where('id=' .$id)->setfield('state',1);
		if($res){
			$this->success('保留成功!');
		}else{
			$this->error('保留失败！');
		}
	}
}