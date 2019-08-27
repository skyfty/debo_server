<?php

/**
 * 会员
 */
namespace User\Controller;
use Common\Controller\AdminbaseController;
class IndexadminController extends AdminbaseController {
    function index(){
    	$users_model=M("User");
    	$count=$users_model->where(array("user_type"=>2))->count();
    	$page = $this->page($count, 20);
    	$lists = $users_model
    	->where(array("user_type"=>2))
    	->order("create_time DESC")
    	->limit($page->firstRow . ',' . $page->listRows)
    	->select();
    	$this->assign('lists', $lists);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display(":index");
    }
    
    function ban(){
    	$id=intval($_GET['id']);
    	if ($id) {
    		$rst = M("User")->where(array("id"=>$id))->setField('state','1');
    		if ($rst) {
    			$this->success("会员拉黑成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员拉黑失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
    
    function cancelban(){
    	$id=intval($_GET['id']);
    	if ($id) {
    		$rst = M("User")->where(array("id"=>$id))->setField('state','0');
    		if ($rst) {
    			$this->success("会员启用成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
     function coins(){
    $id = I('id');
    $con_id = M('user')->where('id ='.$id)->find();
    $this->assign('con_id',$con_id);
    $this->display();

    }
    function coins_post()
    {
     $id = I('con_id');
     $coins = I('coins');

        $user_coins = M('user')->field('coins')->find($id);
    if($id && ($coins || $coins == 0 || $user_coins['coins'] == $coins)){

        $indent = $coins > $user_coins['coins'] ? 24 : 23;  // 23 是减少 24是增加
        $order_price = $user_coins['coins'] > $coins ? $user_coins['coins'] - $coins : $coins - $user_coins['coins'];

        $res = M('user')->where('id ='.$id)->setfield('coins',$coins);
        if($res){
            // 增加系统修改记录
            $transaction = array(
                'order_sn' => date('YmdHis').rand(1000,9999),
                'uid' => $id,
                'order_price' => $order_price,
                'pay_status' => 2,
                'create_time' => time(),
                'indent' => $indent, // 系统修改
            );
            M('transaction')->add($transaction);
        $this->success('修改成功！');
        }else{
        $this->error('修改失败！');
        exit;
        }
    }else{
    $this->error('未收到参数');
    exit;
    }
    
    }
}
