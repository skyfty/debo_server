<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/5/10
 * Time: 8:57
 */

namespace Common\Controller;

use Common\Controller\AppframeController;

class ApibaseController extends AppframeController
{
    public function __construct() {
        parent::__construct();
    }

    public function _initialize(){
        parent::_initialize();
    }
    /**
     * 判断是否有此用户
     * @param int $uid
     * @return string
     */
    protected function isUser($uid)
    {
        $count = M('users')->where(array('id' => $uid, 'user_type' => ['neq', 1]))->count();

        if (!$count) {
            return $this->json(-1, '没有此用户！');
        }
        return $uid;
    }

    /**
     * 判断是传此城市
     * @param string $city
     * @return string
     */
    protected function hasCity($city)
    {
        if(!$city){
            return $this->json(-2,'请选择城市！');
        }
        return $city;
    }

    /*多图片的上传*/
    public function uploads($savepath='',$video=false){
        $path = SITE_PATH.UPLOADS.'/'.$savepath;
        if(!is_dir($path)){
            mkdir($path,0777);
        }
        //$now =$_SERVER['REQUEST_TIME'];
        $config = array(
            'rootPath' => SITE_PATH.UPLOADS,
            'savePath' => $savepath.'/',
            'saveName' => array('uniqid', array('', true)),
            'exts' => array('jpg', 'png', 'jpeg','gif'),
            'autoSub' => false,
        );
        if($video)
        {
            $config['exts']=array( 'flv' , 'wmv' , 'rmvb','mp4');
        }
        $driver_type = sp_is_sae() ? "Sae" : 'Local';//TODO 其它存储类型暂不考虑
        $upload = new \Think\Upload($config,$driver_type);
        $info = $upload->upload();

        if(!$info) {
            return $this->json(-1,$upload->getError());// 上传错误提示错误信息
        }

        $img = '';
        foreach($info as $key=>$val){
            $files[] = UPLOADS.$val['savepath'].$val['savename'];
        }
        $img = implode(';',$files);

        return $img;

    }
    


     public function upload($savepath=''){
        $path = SITE_PATH.UPLOADS.'/'.$savepath;
        if(!is_dir($path)){
            mkdir($path,0777);
        }
         //$now =$_SERVER['REQUEST_TIME'];
        $config = array(
             'rootPath' => SITE_PATH.UPLOADS,
             'savePath' => $savepath.'/',
             'saveName' => array('uniqid', array('', true)),
             'exts' => array('jpg', 'png', 'jpeg','gif','mp4','flv','wmv','rmvb'),
             'autoSub' => false,
         );
        $driver_type = sp_is_sae() ? "Sae" : 'Local';//TODO 其它存储类型暂不考虑
        // var_dump($config).exit();
        $upload = new \Think\Upload($config,$driver_type);
        $info = $upload->upload();
        // var_dump($info).exit();
        if(!$info) {
            // echo 111;exit();
            return $this->json(-1,$upload->getError());// 上传错误提示错误信息
        }

        return $info;
    }

    /*多图片的上传（用于视频图片同时上传，放在不同文件夹中）*/
    public function uploads_video_img($savepath=''){
        $path = SITE_PATH.UPLOADS.'/'.$savepath.'/'.date("Y-m-d",time());
        if(!is_dir($path)){
            mkdir($path,0777);
        }
        //$now =$_SERVER['REQUEST_TIME'];
        $config = array(
            'rootPath' => SITE_PATH.UPLOADS,
            'savePath' => $savepath.'/'.date("Y-m-d",time()).'/',
            'saveName' => array('uniqid', array('', true)),
            'exts' => array('jpg', 'png', 'jpeg','gif','flv' , 'wmv' , 'rmvb','mp4'),
            'autoSub' => false,
        );

        $driver_type = sp_is_sae() ? "Sae" : 'Local';//TODO 其它存储类型暂不考虑
        $upload = new \Think\Upload($config,$driver_type);
        $info = $upload->upload();

        if(!$info) {
            return $this->json(-1,$upload->getError());// 上传错误提示错误信息
        }

        $return_path = array();
        $video = array();
        $img_arr = array('jpg', 'png', 'jpeg','gif');
        $video_arr = array('flv' , 'wmv' , 'rmvb','mp4');
        foreach($info as $key=>$val){
            if(in_array($val['ext'],$img_arr)){

                $image=new \Think\Image();
                $srcimg=SITE_PATH.UPLOADS.$val['savepath'].$val['savename'];
                $image->open($srcimg);
                $image->thumb(1280,1280); //传递宽高  按比例缩放，如果图片本身比这个小，这就是最大值。
                $smallimg=SITE_PATH.UPLOADS.$val['savepath'].$val['savename'];
                $image->save($smallimg);

                $images[] = UPLOADS.$val['savepath'].$val['savename'];
            }
            if(in_array($val['ext'],$video_arr)){
                $video[] = UPLOADS.$val['savepath'].$val['savename'];
            }
        }
        if(!empty($video)){
            $return_path['img'] = implode(';',$images);
            $return_path['video'] = implode(';',$video);
        }else{
            $return_path['img'] = implode(';',$images);
            $return_path['video'] = "";
        }

        //制作缩略图的时候需要判断是否是视频文件
        return $return_path;

    }

    /**
     * 分页函数
     * @param int $total
     * @return array
     */
    function page($total) {
        $return         = array ();
        $count          = I('pageSize','0','intval') ? I('pageSize','0','intval') : 10;
        $page_count     = max ( 1, ceil ( $total / $count ) );
        $page           = I('page','0','intval') ? I('page','0','intval') :1;
        $page_next      = min ( $page + 1, $page_count );
        $page_previous  = max ( 1, $page - 1 );
        $offset         = max ( 0, ( int ) (($page - 1) * $count) );

        $return = array (
            'total'         => (int) $total,
            'count'         => $count,
            'pageCount'     => $page_count,
            'page'          => $page,
            'page_next'     => $page_next,
            'page_previous' => $page_previous,
            'offset'        => $offset,
            'limit'         => $count
        );
        return $return;
    }
    /**
     * 传入手机号码，检测用户是否存在
     * @param $phone 手机号码
     */
    public function check_user( $phone )
    {
        $info = M('users')->where(['mobile'=>$phone])->find();
        if ( !$info ){
            return 0;
        }
        return $info['id'];
    }

    /*
     *  通过用户ID获取用户基本信息
     *   @param int $uid
     *   @param int $type 1 用uid 2 用电话
     * */
    public function userInfo( $uid , $type ,$f_phone,$u_id){
        switch($type){
            case 1:
                $info = M('users')->where(['id'=>$uid])->find();
                break;
            case 2:
                $info = M('users')->where(['mobile'=>$uid])->find();
                break;
            case 3:
                //$info = M('users')->where(' mobile in ('.$uid.')')->select();
                //$info = M('users')->join('lc_user_friend on lc_users.id = lc_user_friend.uid')->where(' lc_users.mobile in ('.$uid.')')->field('lc_users.*,lc_user_friend.remark,lc_user_friend.label')->select();

                if(!empty($f_phone) && $u_id == null){
                    $sql_f = 'select id from lc_users where mobile ='.$f_phone;
                    $info_temp = M('users')->query($sql_f);
                    if(!empty($info_temp)){
                        $fid = $info_temp[0]['id'];
                        $sql = 'select lc_users.id,remark,label,fid from lc_users left join lc_user_friend on lc_users.id = lc_user_friend.uid where lc_users.mobile in ('.$uid.') and lc_user_friend.fid = '.$fid;
                        $sql2 = 'select * from lc_users where id ='.$fid;
                        $info1 = M('users')->query($sql);
                        $info2 = M('users')->query($sql2);
                        //$info1[0] = $info2[0];
                        if(!empty($info1)){
                            $info[0] = array_merge($info2[0],$info1[0]);
                            if(empty($info[0])){
                                $info[0] = "";
                            }
                        }else{
                            //如果这个手机号和你是陌生人，那么就没有fid，我们直接将陌生人信息返回。
                            $info2[0]['remark'] = "";
                            $info2[0]['label'] = "";
                            $info[0] = $info2[0];
                        }

                    }
                }else if(!empty($u_id) && $f_phone == null){
                    $sql = 'select * from lc_users where mobile in ('.$uid.')';
                    $info1 = M('users')->query($sql);
                    $arr_fids = array();
                    foreach($info1 as $key => $val){
                        $arr_fids[] = $info1[$key]['id'];
                    }
                    $fids = implode(",",$arr_fids);
                    $sql2 = 'select * from lc_user_friend where fid in ('.$fids.')';
                    $info2 = M('users')->query($sql2);
                    $info = array();
                    $arr_uids = array();
                    foreach($info2 as $k=>$v){
                        $arr_uids[] = $info2[$k]['fid'];
                    }

                    foreach($info1 as $k2=>$two){
                        if(in_array($info1[$k2]['id'],$arr_uids)){
                            foreach($info2 as $k3=>$v3){
                                if($info2[$k3]['fid'] == $info1[$k2]['id'] && $info2[$k3]['uid'] == $u_id){
                                    $info[$k2] = $info1[$k2];
                                    $info[$k2]['fid'] = $info2[$k3]['fid'];
                                    $info[$k2]['remark'] = $info2[$k3]['remark'];
                                    $info[$k2]['label'] = $info2[$k3]['label'];
                                }else if($info2[$k3]['fid'] == $info1[$k2]['id']){
                                    $info[$k2] = $info1[$k2];
                                    $info[$k2]['fid'] = $u_id;
                                    $info[$k2]['remark'] = "";
                                    $info[$k2]['label'] = "";
                                }
                            }
                        }else{
                            $info[$k2] = $info1[$k2];
                            $info[$k2]['fid'] = $u_id;
                            $info[$k2]['remark'] = "";
                            $info[$k2]['label'] = "";
                        }
                    }

                }else{
                    $sql = 'select lc_users.*,remark,label,fid from lc_users left join lc_user_friend on lc_users.id = lc_user_friend.uid where lc_users.mobile in ('.$uid.')';
                    $info = M('users')->query($sql);
                }

                break;
        }
        return $info;
    }


    /**
     * 上传视频
     */
    public function uploadinfo($savepath=''){
        $path = SITE_PATH.UPLOADS.'/'.$savepath;
        if(!is_dir($path)){
            mkdir($path,3145728000);
        }

        $config = array(
            'rootPath' => SITE_PATH.UPLOADS,
            'savePath' => $savepath.'/',
            'saveName' => array('uniqid', ''),
            'exts' => array('.flv' , '.wmv' , '.rmvb' , '.php' , '.php3' , '.php4' , '.exe' , '.msi' , '.htaccess' , '.gz'),
            'autoSub' => false,
        );
        $driver_type = sp_is_sae() ? "Sae" : 'Local';//TODO 其它存储类型暂不考虑
        $upload = new \Think\Upload($config,$driver_type);
        return $upload->uploads();
    }

    /**
     * 检测非法字符串
     * parem:$str 
     * 如果有非法字符返回true；没有返回false;
     */
    public function check_str($str){
       $filename=SITE_PATH.'public/key.txt';
       $key_content= explode('|',file_get_contents($filename));
       foreach ($key_content as $str_val){
           if(strstr($str,$str_val)){
               return true;
           }  
       }
       return FALSE;
    }
      /*
     *  通过经纬度获取省市县
     * */
   
}