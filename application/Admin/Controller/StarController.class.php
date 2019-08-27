<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/3
 * Time: 10:11
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class StarController extends AdminbaseController
{
 //许愿星列表
    public function index()
    {
        $where = [];
        $content = I('content');
        $star1 = I('star1');

        if($star1){
            $where['status'] = $star1;
        }
        if($content){
            $where['wishing_content'] = array('like','%'.$content.'%');
        }
        $count = M('wishing_star')->where($where)->count();
        $page = $this->page($count,20);
        $star = M('wishing_star')->where($where)->select();
            foreach ($star as $key => $val)
            {
                $star[$key]['name'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');
                $star[$key]['user'] = M('user')->where('id ='.$val['uid'])->getfield('user_nickname');

            }
        $star2 = M('wishing_star')->group('status')->select();
        $this->assign('star',$star);
        $this->assign('star2',$star2);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }
    //删除许愿星
    public function delete()
    {
        $star_id = I('star_id');
        if($star_id)
        {
            $res = M('wishing_star')->where('star_id='.$star_id)->delete();
        }else{
            $this->error('参数错误！');
            exit;
        }
        if($res){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
}