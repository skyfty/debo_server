<?php
namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class RateController extends AdminbaseController
{
	//手续费列表
	public function index()
	{
	$res = M('rate')->select();
	$this->assign('res',$res);
	$this->display();
	}
	//修改利率
  public function modify()
    {
        $id = I('r_id');
        if(!$id)
        {
            $this->error('参数错误,未接收到r_id');
            exit;
        }
        if(IS_POST) {
            $data['rate'] = I('rate');

            $res = M('rate')->where(['r_id' => $id])->save($data);
            if ($res) {
                $this->success('修改成功');
            }else{
            
            $this->error('修改失败');
            }
        }
        $info=M('rate')->where('r_id='.$id)->find();
        $this->assign('info',$info);
        $this->display();
    }
}