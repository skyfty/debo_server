<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19 0019
 * Time: 上午 9:18
 */
namespace Appapi\Controller;

use Common\Controller\ApibaseController;

class OneselfController extends ApibaseController{

    private $path = 'http://debo.shangtongyuntian.com';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *发布碰一碰 （发布时间不能少于3天）
     * */
    public function touch_touch(){
        $ad_type = I('ad_type');//1 碰一碰 ；2 撞一幢 ； 3 许愿星
        $uid = I('uid');//发布广告的用户uid
        $title = I('title');//发布的广告标题
        $ad_link = I('ad_link');//发布的广告链接
        $price = I('price');//发布的红包金额
        $p_num = I('p_num');//红包要分配的份数
        $ad_content = I('ad_content');//广告发布的文字内容
        $start_time = I('start_time');//广告发布的开始时间
        $time_range = I('time_range');//广告要投放的截止时间
        if(!$ad_type || !$uid || !$title || !$time_range){
            return $this->json(-1,"缺少参数");
        }


        if(!empty($_FILES)){//上传图片
            $image = $this->uploads('advertisement/advertisement_'.$uid);
            $data['ad_images'] = $image;//回答图片
        }

        //$now_day = date("Y-m-d",$start_time);
        $num1 = strtotime($start_time)+3600*24;//从第二天开始算起
        $num2 = strtotime($time_range)+3600*24;

        if($num1 > $num2){
            return $this->json(-4,"时间错误");
        }

        $range_res = $num2 - $num1;
        if($range_res < 3600*24*3){
            return $this->json(-2,"发布时间不能小于3天");
        }

        if(($price/$p_num) < 0.01){
            return $this->json(-5,"单个红包金额不可低于0.01元");
        }

        $data['ad_type'] = $ad_type;
        $data['uid'] = $uid;
        $data['title'] = $title;
        $data['ad_link'] = $ad_link;
        $data['price'] = $price;
        $data['p_num'] = $p_num;
        $data['ad_content'] = $ad_content;
        $data['start_time'] = $num1;
        $data['time_range'] = $num2;
        $data['remaining_sum'] = $price;
        $data['remain_num'] = $p_num;
        $data['p_time'] = time();

        $add_res = M('touch')->add($data);

        if($add_res){
            return $this->json(0,"发布成功",$add_res);
        }else{
            return $this->json(-3,"发布失败");
        }

    }


    /**
    抽取广告
    */
    public function get_touch(){
        $type = I('type');
        $uid = I('uid');
        if(!$type && !$uid){
            return $this->json(-5,"缺少参数");
        }
        if(I('scan_uid')){
            $scan_uid = I('scan_uid');
        }
        $time = time();
        if($type == 1){//碰一碰
            if(!$scan_uid){
                return $this->json(-5,"缺少参数");
            }
            //判断是否24小时内碰同一个人
            $bribery_money_record = M('bribery_money_record')->where('uid = '.$uid.' and scan_uid = '.$scan_uid.' and time >= '.($time - 60*60*24))->find();
            if($bribery_money_record){
                return $this->json(-4,"不能重复扫描，请更换其他人");
            }
            //对方发布的广告
            $touch_tmp = M('touch')->where('uid = '.$scan_uid.' and p_status = 2 and remaining_sum != 0 and ad_type = 1 and start_time <= '.$time.' and time_range >= '.$time)->order('price desc')->limit(5)->select();
            $touch_type = '对方广告';
            $ad_type = 1;
            //没有对方发布的广告，取碰一碰广告
            if(!$touch_tmp){
                $touch_tmp = M('touch')->where('p_status = 2 and remaining_sum != 0 and ad_type = 1 and start_time <= '.$time.' and time_range >= '.$time)->order('price desc')->limit(5)->select();
                $touch_type = '碰一碰广告';
            }
            //都没有，取平台广告
            if(!$touch_tmp){
                $touch_tmp = M('touch')->where('p_status = 2 and remaining_sum != 0 and ad_type = 4 and start_time <= '.$time.' and time_range >= '.$time)->order('price desc')->limit(5)->select();
                $touch_type = '平台广告';
                $ad_type = 4;
            }

        }else if($type == 2){//撞一撞

            //可抽红包的撞一撞用户广告总数
            
            $red_count = M('touch')->where('p_status = 2 and remaining_sum != 0 and ad_type = 2 and start_time <= '.$time.' and time_range >= '.$time)->count();

            $j = $this->get_praise($red_count);
            
            if($j == 0){
                return $this->json(-1,"没有中广告");                
            }else if($j == 1){
                $ad_type = 4;
                $touch_type = "平台广告";
            }else if($j == 2){
                $ad_type = 2;                            
                $touch_type = "用户广告";
            }
            //广告范围（以200为例，可调）
            $touch_tmp = M('touch')->where('p_status = 2 and remaining_sum != 0 and ad_type = '.$ad_type.' and start_time <= '.$time.' and time_range >= '.$time)->order('price desc')->limit(200)->select();            
            
        }else if($type == 3){//许愿星
            //可抽红包的许愿星用户广告总数
            
            $red_count = M('touch')->where('p_status = 2 and remaining_sum != 0 and ad_type = 3 and start_time <= '.$time.' and time_range >= '.$time)->count();
            $star_count = M('wishing_star')->where('status = 1 and uid != '.$uid)->count();

            $j = $this->get_praise($red_count,3,$star_count);
            
            if($j == 0){                
                return $this->json(-1,"什么都没有");
            }else if($j == 1){
                $ad_type = 4;
                $touch_type = "平台广告";
            }else if($j == 2){
                $ad_type = 3;                            
                $touch_type = "许愿星广告";
            }else if($j == 3){
                //文字许愿星
                $wishing_star_tmp = M('wishing_star')->where('status = 1 and uid != '.$uid)->order('add_time desc')->limit(200)->select();
                if(!$wishing_star_tmp){
                    return $this->json(-7,"没有文字许愿星");
                }
                //取许愿星
                $star_id = array_rand($wishing_star_tmp);
                $wishing_star = $wishing_star_tmp[$star_id];
                //记录摘取许愿星信息
                $star_data = array(
                    'status' => 2,
                    'pick_uid' => $uid,
                    'pick_time' => $time
                    );
                $re_star = M('wishing_star')->where('star_id = '.$wishing_star['star_id'])->save($star_data);
                if($re_star){
                    //获取自己的信息
                    $my_info = M('user')->where('id = '.$uid)->find();

                    //发布许愿星用户信息
                    $star_user_info = M('user')->field('user_nickname,avatar,signature,mobile,top_father,pur_mobile,pur_top_father')->where('id = '.$wishing_star['uid'] )->find();
                    $return_info = $wishing_star;
                    $return_info['user_nickname'] = $star_user_info['user_nickname']?$star_user_info['user_nickname']:'';
                    $return_info['avatar'] = $star_user_info['avatar']?$star_user_info['avatar']:'';
                    $return_info['signature'] = $star_user_info['signature']?$star_user_info['signature']:'';
                    $return_info['mobile'] = $star_user_info['mobile'];
                    $return_info['star_type'] = '1';

                    //判断是否是自己的人脉
                    if(($my_info['top_father'] == $star_user_info['top_father']) || ($my_info['top_father'] == $star_user_info['pur_top_father']) || ($my_info['mobile'] == $star_user_info['pur_mobile']) || ($my_info['pur_mobile'] == $star_user_info['mobile'])){
                        $return_info['j_type'] = "2";//人脉
                    }else{
                        $return_info['j_type'] = "1";//好友或陌生人
                    }

                    return $this->json(0,"文字许愿星",$return_info);
                }else{
                    return $this->json(-2,"失败");
                }
            }
            //平台广告，用户广告范围（以200为例，可调）
            $touch_tmp = M('touch')->where('p_status = 2 and remaining_sum != 0 and ad_type = '.$ad_type.' and start_time <= '.$time.' and time_range >= '.$time)->order('price desc')->limit(200)->select();

        }
        if(!$touch_tmp){
            return $this->json(-3,"没有广告");
        }

        //取抽中广告
        $id = array_rand($touch_tmp);
        $touch = $touch_tmp[$id];
        
        //判断是否24小时内抽到过
        $bribery_money_record = M('bribery_money_record')->where('t_id = '.$touch['t_id'].' and uid = '.$uid.' and time >= '.($time - 60*60*24))->find();
        if($bribery_money_record){
            return $this->json(-6,"红包抽过了");
        }

        //计算红包金额
        $money = $this -> get_red_packerts($touch['remaining_sum'],$touch['remain_num']);

        //修改广告余额及剩余红包个数
        $data['remaining_sum'] = $money['remaining_sum'];
        $data['remain_num'] = $money['remain_num'];
        $re_touch = M('touch')->where('t_id = '.$touch['t_id'])->save($data);

        //记录抽红包记录（记录表bribery_money_record，订单表transaction）
        $bribery_money_record = array(
            't_id' => $touch['t_id'],
            'bm_price' => $money['money'],
            'time' => $time,
            'ad_type' => $ad_type,
            'uid' => $uid,
            );
        if($scan_uid){
            $bribery_money_record['scan_uid'] = $scan_uid;
        }
        $bribery_money_record = M('bribery_money_record')->add($bribery_money_record);

        $order_sn = date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $transaction = array(
            'order_sn' => $order_sn,
            'uid' => $uid,
            'order_price' => $money['money'],
            'create_time' => $time,
            'indent' => 4,
            'pay_status' => 2,
            );       
        $transaction = M('transaction')->add($transaction);

        //修改用户余额
        $update_coins = M('user')->where('id = '.$uid )->setInc('coins',$money['money']);

        if($re_touch && $bribery_money_record && $transaction && $update_coins){
            //发布广告用户信息
            $red_user_info = M('user')->field('user_nickname,avatar')->where('id = '.$touch['uid'] )->find();
            if($touch['ad_images']){
                $touch['ad_images'] = explode(';',$touch['ad_images']);
                foreach($touch['ad_images'] as $k => $im){
                    $touch['ad_images'][$k] = 'http://debo.shangtongyuntian.com'.$im;
                }
            }else{
                $touch['ad_images'] = array();
            }    
            $return_info = $touch;
            $return_info['user_nickname'] = $red_user_info['user_nickname']?$red_user_info['user_nickname']:'';
            $return_info['avatar'] = $red_user_info['avatar']?$red_user_info['avatar']:C('DEFAULT_AVATAR');
            $return_info['money'] = (string) $money['money'];
            if($type == 3){//许愿星
                $return_info['star_type'] = '2';
            }
            return $this->json(0,$touch_type,$return_info);
        }else{
            return $this->json(-2,"失败");
        }       

    }


    /**
    抽取广告*新
    */
    public function get_ad(){
        $type = I('type');
        $uid = I('uid');
        if(!$type && !$uid){
            return $this->json(-5,"缺少参数");
        }
        $time = time();
        $today_time = strtotime(date("Y-m-d"));
        if(I('scan_uid')){
            $scan_mobile = I('scan_uid');
            $scan_user = M('user')->field('id')->where('mobile = "'.$scan_mobile.'"')->find();
            $scan_uid = $scan_user['id'];
            if(!$scan_uid){
                return $this->json(-5,"对方不是嘚啵用户！");
            }
            //判断今天获取红包总数
            $bribery_money_record_count = M('bribery_money_record')->where('uid = '.$scan_uid.' and time >= '.$today_time.' and ad_type = '.$type)->count();
            if($bribery_money_record_count > 20){
                return $this->json(-8,"对方已经碰过太多次了！");
            }
        }
        
        //判断今天获取红包总数
        $bribery_money_record_count = M('bribery_money_record')->where('uid = '.$uid.' and time >= '.$today_time.' and ad_type = '.$type)->count();
        if($bribery_money_record_count > 20){
            return $this->json(-6,"今天已收获太多红包，请明天再来！");
        }
        $condition = ' and p_status = 2 and remaining_sum != 0 and start_time <= '.$time.' and time_range >= '.$time;
        //判断24小时内抽到过的广告
        // $bribery_money_record = M('bribery_money_record')->field('t_id')->where('uid = '.$uid.' and ad_type = '.$type.' and time >= '.($time - 60*60*24))->find();
        // $record_str = implode(',',$bribery_money_record);
        // if($record_str){
        //     $condition .= ' and t_id not in('.$record_str.')';
        // }
        
        if($type == 1){//碰一碰

            //判断是否24小时内碰同一个人
            $bribery_money_record = M('bribery_money_record')->where('uid = '.$uid.' and scan_uid = '.$scan_uid.' and time >= '.($time - 60*60*24))->find();
            if($bribery_money_record){
                return $this->json(-4,"不能重复扫描，请更换其他人");
            }
            //对方发布的广告
            $touch_tmp = M('touch')->field('t_id,uid')->where('uid = '.$scan_uid.' and ad_type = 1 '.$condition)->order('price desc')->limit(5)->select();
            $touch_type = '对方广告';
            $ad_type = 1;
            //没有对方发布的广告，取碰一碰广告
            if(!$touch_tmp){
                $touch_tmp = M('touch')->field('t_id,uid')->where('ad_type = 1 '.$condition)->order('price desc')->limit(5)->select();
                $touch_type = '碰一碰广告';
            }
            //都没有，取平台广告
            if(!$touch_tmp){
                $touch_tmp = M('touch')->field('t_id,uid')->where('ad_type = 4 '.$condition)->order('price desc')->limit(5)->select();
                $touch_type = '平台广告';
                $ad_type = 4;
            }

        }else if($type == 2){//撞一撞

            //可抽红包的撞一撞用户广告总数
            
            $red_count = M('touch')->where('ad_type = 2'.$condition)->count();

            $j = $this->get_praise($red_count);
            
            if($j == 0){
                return $this->json(-1,"没有中广告");                
            }else if($j == 1){
                $ad_type = 4;
                $touch_type = "平台广告";
            }else if($j == 2){
                $ad_type = 2;                            
                $touch_type = "用户广告";
            }
            //广告范围（以200为例，可调）
            $touch_tmp = M('touch')->field('t_id,uid')->where('ad_type = '.$ad_type.$condition)->order('price desc')->limit(200)->select();            
            
        }else if($type == 3){//许愿星
            //可抽红包的许愿星用户广告总数
            
            $red_count = M('touch')->where('ad_type = 3 '.$condition)->count();
            $star_count = M('wishing_star')->where('status = 1 and uid != '.$uid)->count();

            $j = $this->get_praise($red_count,3,$star_count);
            
            if($j == 0){                
                return $this->json(-1,"什么都没有");
            }else if($j == 1){
                $ad_type = 4;
                $touch_type = "平台广告";
            }else if($j == 2){
                $ad_type = 3;                            
                $touch_type = "许愿星广告";
            }else if($j == 3){
                //文字许愿星
                $wishing_star_tmp = M('wishing_star')->where('status = 1 and uid != '.$uid)->order('add_time desc')->limit(200)->select();
                if(!$wishing_star_tmp){
                    return $this->json(-7,"没有文字许愿星");
                }
                //取许愿星
                $star_id = array_rand($wishing_star_tmp);
                $wishing_star = $wishing_star_tmp[$star_id];
                //记录摘取许愿星信息
                $star_data = array(
                    'status' => 2,
                    'pick_uid' => $uid,
                    'pick_time' => $time
                    );
                $re_star = M('wishing_star')->where('star_id = '.$wishing_star['star_id'])->save($star_data);
                if($re_star){
                    //发布许愿星用户信息
                    $star_user_info = M('user')->field('user_nickname,avatar,signature,mobile')->where('id = '.$wishing_star['uid'] )->find();
                    $return_info = $wishing_star;
                    $return_info['user_nickname'] = $star_user_info['user_nickname']?$star_user_info['user_nickname']:'';
                    $return_info['avatar'] = $star_user_info['avatar']?$star_user_info['avatar']:'';
                    $return_info['signature'] = $star_user_info['signature']?$star_user_info['signature']:'';
                    $return_info['mobile'] = $star_user_info['mobile'];
                    $return_info['star_type'] = '1';
                    return $this->json(0,"文字许愿星",$return_info);
                }else{
                    return $this->json(-2,"失败");
                }
            }
            //平台广告，用户广告范围（以200为例，可调）
            $touch_tmp = M('touch')->field('t_id,uid')->where('ad_type = '.$ad_type.$condition)->order('price desc')->limit(200)->select();

        }
        if(!$touch_tmp){
            return $this->json(-3,"没有广告");
        }

        //取抽中广告
        $id = array_rand($touch_tmp);
        $touch = $touch_tmp[$id];

        //发布广告用户信息
        $red_user_info = M('user')->field('user_nickname,avatar')->where('id = '.$touch['uid'] )->find();
        $return_info = array();
        $return_info['user_nickname'] = $red_user_info['user_nickname']?$red_user_info['user_nickname']:'';
        $return_info['avatar'] = $red_user_info['avatar']?$red_user_info['avatar']:C('DEFAULT_AVATAR');
        $return_info['t_id'] = $touch['t_id'];
        if($type == 3){//许愿星
                $return_info['star_type'] = '2';
        }
        return $this->json(0,$touch_type,$return_info);
    }

    /** 
    *   确认红包
    */
    public function confirm_touch(){
        $t_id = I('t_id');
        $uid = I('uid');
        if(!$t_id && !$uid){
            return $this->json(-1,"缺少参数");
        }
        if(I('scan_uid')){
            $scan_mobile = I('scan_uid');
            $scan_user = M('user')->field('id')->where('mobile = "'.$scan_mobile.'"')->find();
            if(!$scan_user){
                return $this->json(-4,"对方用户不存在！");
            }
            $scan_uid = $scan_user['id'];
        }
        $time = time();
        //获取广告
        $touch = M('touch')->field('uid as t_uid,t_id,title,ad_link,ad_content,ad_images,remaining_sum,remain_num,ad_type')->where('t_id ='.$t_id)->find();
        if($touch['remain_num'] == '0'){
            return $this->json(-3,"来晚了，红包已抢完！");
        }
        $ad_type = $touch['ad_type'];
        //计算红包金额
        $money = $this -> get_red_packerts($touch['remaining_sum'],$touch['remain_num']);
        //var_dump($money);die;
        //修改广告余额及剩余红包个数
        $data['remaining_sum'] = $money['remaining_sum'];
        $data['remain_num'] = $money['remain_num'];
        $re_touch = M('touch')->where('t_id = '.$touch['t_id'])->save($data);

        //记录抽红包记录（记录表bribery_money_record，订单表transaction）
        $bribery_money_record = array(
            't_id' => $touch['t_id'],
            'bm_price' => $money['money'],
            'time' => $time,
            'ad_type' => $ad_type,
            'uid' => $uid,
            );
        if($scan_uid){
            $bribery_money_record['scan_uid'] = $scan_uid;
        }
        $bribery_money_record = M('bribery_money_record')->add($bribery_money_record);

        //$order_sn = date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $order_sn = date('YmdHis').rand(1000,9999);
        $transaction = array(
            'order_sn' => $order_sn,
            'uid' => $uid,
            'order_price' => $money['money'],
            'create_time' => $time,
            'indent' => 4,
            'pay_status' => 2,
            );       
        $transaction = M('transaction')->add($transaction);

        //修改用户余额
        $update_coins = M('user')->where('id = '.$uid )->setInc('coins',$money['money']);

        if($re_touch && $bribery_money_record && $transaction && $update_coins){
            //发布广告用户信息
            $red_user_info = M('user')->field('user_nickname,avatar,mobile')->where('id = '.$touch['t_uid'] )->find();
            if($touch['ad_images']){
                $touch['ad_images'] = explode(';',$touch['ad_images']);
                foreach($touch['ad_images'] as $k => $im){
                    $touch['ad_images'][$k] = 'http://debo.shangtongyuntian.com'.$im;
                }
            }else{
                $touch['ad_images'] = array();
            }    
            $return_info = $touch;
            $return_info['user_nickname'] = $red_user_info['user_nickname']?$red_user_info['user_nickname']:'';
            $return_info['avatar'] = $red_user_info['avatar']?$red_user_info['avatar']:C('DEFAULT_AVATAR');
            $return_info['mobile'] = $red_user_info['mobile'];
            $return_info['money'] = (string) $money['money'];
            if($type == 3){//许愿星
                $return_info['star_type'] = '2';
            }
            return $this->json(0,'成功',$return_info);
        }else{
            return $this->json(-2,"来晚了，红包没有啦！");
        }  

    }


    /** 
    *   发表许愿星
    */
    public function wishing_star(){
        $uid = I('uid');
        $wishing_content = I('content');
        if(!$uid || !$wishing_content){
            return $this->json(-1,"缺少参数");
        }
        $time = time();
        $wishing_star = array(
            'uid' => $uid,
            'wishing_content' => $wishing_content,
            'add_time' => $time,
            );
        $add_star = M('wishing_star')->add($wishing_star);
        if($add_star){
            return $this->json(0,"成功");
        }else{
            return $this->json(-2,"失败");
        }

    }


    /** 
    *   广告列表
    */
    public function touch_list(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,"请先登录");
        }
        $ad_type = I('ad_type');
        if(!$ad_type){
            return $this->json(-2,"无广告类型");
        }
        $page = I('page')?I('page'):1;
        $limit = (($page-1)*20).',20';

        if(!is_numeric($uid) || !is_numeric($ad_type) || !is_numeric($page)){
            return $this->json(-3,'请求参数格式错误','');
        }

        $touch_tmp = M('touch')->field('t_id,title,p_time')->where('p_status = 2 and uid = '.$uid.' and ad_type = '.$ad_type)->order('time_range desc')->limit($limit)->select();
        if(!$touch_tmp){
            return $this->json(-4,'没有广告','');
        }
        foreach($touch_tmp as $kk => $vv){
            $touch_tmp[$kk]['p_time'] = date('Y-m-d',$vv['p_time']);
        }
        
        $touch_num = M('touch')->where('p_status = 2 and uid = '.$uid.' and ad_type = '.$ad_type)->count();
        $return_info = array('touch_num' => $touch_num,'touch' => $touch_tmp);
        return $this->json(0,'成功',$return_info);
    }

    /** 
    *   红包撤回
    */
    public function touch_recall(){
        $tid_str = I('t_id');
        $uid = I('uid');
        if(!$tid_str || !$uid){
            return $this->json(-1,'缺少参数');
        }
        // $tid_arr = explode(',',$tid_str); 
        $data['p_status'] = 3;
        $re = M('touch')->where('uid = '.$uid.' and t_id in ('.$tid_str.')')->save($data);
        if($re){
            return $this->json(0,'成功');
        }else{
            return $this->json(-2,'修改失败');
        }       

    }

    /** 
    *   文字许愿星记录
    */
    public function wishing_list(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,'缺少参数');
        }

        $page = I('page')?I('page'):1;
        $limit = (($page-1)*20).',20';
        //我发出的记录
        // $wishing_tmp = M('wishing_star')->field('star_id,wishing_content,add_time,uid,pick_uid')->where('uid = '.$uid)->order('add_time desc')->limit($limit)->select();
        // $user_info = M('user')->field('id,user_nickname,avatar')->where('id = '.$uid)->find();
        //我摘到的记录
        $wishing_tmp = M('wishing_star')->field('uid,star_id,wishing_content,add_time,uid,pick_uid')->where('pick_uid = '.$uid)->order('add_time desc')->limit($limit)->select();
        if(!$wishing_tmp){
            return $this->json(-2,'没有记录');
        }
        // if(!$user_info){
        //     return $this->json(-3,'用户不存在');
        // }
        $wishing = array();
        $my_info = M('user')->where('id = '.$uid)->find();
        foreach($wishing_tmp as $k => $wish){
            $user_info = M('user')->field('id,user_nickname,avatar,mobile,top_father,pur_mobile,pur_top_father')->where('id = '.$wish['uid'])->find();
            $wish['user_nickname'] = $user_info['user_nickname'];
            $wish['avatar'] = $user_info['avatar'] ? $user_info['avatar'] : C('DEFAULT_AVATAR');
            $wish['mobile'] = $user_info['mobile'];
            //判断是否是自己的人脉
            if(($my_info['top_father'] == $user_info['top_father']) || ($my_info['top_father'] == $user_info['pur_top_father']) || ($my_info['mobile'] == $user_info['pur_mobile']) || ($my_info['pur_mobile'] == $user_info['mobile'])){
                $wish['j_type'] = "2";//人脉
            }else{
                $wish['j_type'] = "1";//好友或陌生人
            }
            $wishing[] = $wish;
        }
        return $this->json(0,'成功',$wishing);

    }

    /** 
    *   广告查看详细
    */
    public function touch_show(){
        $t_id = I('t_id');
        if(!$t_id){
            return $this->json(-1,'缺少参数');
        }
        $touch = M('touch')->where('t_id = '.$t_id)->find();
        if(!$touch){
            return $this->json(-2,'没有广告');
        }
        $touch['time_range'] = date('Y-m-d',$touch['time_range']);
        $touch['start_time'] = date('Y-m-d',$touch['start_time']);
        $touch['p_time'] = date('Y-m-d',$touch['p_time']);
        if($touch['ad_images']){
            $touch['ad_images'] = explode(';',$touch['ad_images']);
            foreach($touch['ad_images'] as $k => $im){
                $touch['ad_images'][$k] = 'http://debo.shangtongyuntian.com'.$im;
            }
        }else{
            $touch['ad_images'] = array();
        }  

        return $this->json(0,'成功',$touch);

    }
    
    /** 
    *   文字许愿星放回
    */
    public function wishing_back(){
        $star_id = I('star_id');
        if(!$star_id){
            return $this->json(-1,'缺少参数');
        }
        $data['status'] = '1';
        $data['pick_uid'] = 0;
        $data['pick_time'] = 0;

        $re = M('wishing_star')->where('star_id = '.$star_id)->save($data);
        if($re){
            return $this->json(0,'成功');            
        }else{
            return $this->json(-2,'放回失败');
        }

    }

     /** 
    *   红包概率
    *   @param int $red_count  符合条件的红包个数
    *   @param int $type  2 撞一撞；3 许愿星
    *   @param int $star_count  文字许愿星数量
    */
    public function get_praise($red_count,$type=2,$star_count=0){
        if($type == 2){
            // if($red_count == 0){
            //     $cj_prize = array(
            //         '0' => array(0, 50),//无红包，概率20%,中奖范围1~199
            //         '1' => array(1, 950),//平台红包，概率80%,中奖范围199~999
            //     );
            // }else if($red_count >= 200){
            //     $cj_prize = array(
            //         '0' => array(0, 200),//无红包，概率20%,中奖范围1~199
            //         '2' => array(1, 800),//用户红包，概率80%,中奖范围199~999
            //     );
            // }else{
            //     $cj_prize = array(
            //         '0' => array(0, 50),//无红包，概率20%,中奖范围1~199
            //         '1' => array(1, 150),//平台红包
            //         '2' => array(2, 800),//用户红包
            //     );
            // }
            $cj_prize = array(
                '1' => array(1, 200),//平台红包
                '2' => array(2, 800),//用户红包
            );

        }else if($type == 3){
            if($red_count == 0 && $star_count == 0){
                $cj_prize = array(
                    '0' => array(0, 200),//无红包，概率20%,中奖范围1~199
                    '1' => array(1, 800),//平台红包，概率80%,中奖范围199~999
                );
            }else if($red_count > 0 && $star_count == 0){
                $cj_prize = array(
                    '0' => array(0, 200),//无红包，概率20%,中奖范围1~199
                    '1' => array(1, 300),//平台红包，
                    '2' => array(1, 500),//用户红包，
                );
            }else if($red_count == 0 && $star_count > 0){                
                $cj_prize = array(
                    '0' => array(0, 200),//无红包，概率20%,中奖范围1~199
                    '1' => array(1, 200),//平台红包
                    '3' => array(2, 600),//文字许愿星
                );
            }else if($red_count > 0 && $star_count > 0){    
                $cj_prize = array(
                    '0' => array(0, 200),//无红包，概率20%,中奖范围1~199
                    '2' => array(2, 400),//用户红包
                    '3' => array(2, 400),//文字许愿星
                );
            }

        }
        

        $map = array();
        $t = 0;

        //计算奖项的中奖范围
        foreach ($cj_prize as $id => $data) {
            $map[$id][0] = $t;
            $t+=$data[1];//赋值，为下一奖项范围的开始值，
            $map[$id][1] = $t - 1;
        };

        //1~999999间取随机值
        $d = rand(0, $t * 1000 - 1);

        //随机值%1000，得后三位值，为最终中奖的值
        $id = $d % $t;

        //根据最终中奖的值，判断所处的奖项范围，返回所中的奖项
        foreach ($map as $J => $data) {
            if ($id >= $data[0] and $id <= $data[1]) {
                return  $J; //0~2的奖项
            }
        }
        //return $array;

    }

     /**
     *   获取红包金额 
     *   @param int $remaining_sum  剩余金额
     *   @param int $remain_num 剩余红包个数
     * */
    public function get_red_packerts($remaining_sum,$remain_num){
        if($remain_num == 1){ //剩余最后一个红包
            $return_info = array(
                'money' => $remaining_sum,
                'remaining_sum' => 0,
                'remain_num' => 0
            );

            return $return_info;
        }

        if($remaining_sum == 0.01){ //余额为最小金额
            $return_info = array(
                'money' => $remaining_sum,
                'remaining_sum' => 0,
                'remain_num' => 0
            );

            return $return_info;
        }

        $min=0.01;//每个人最少能收到0.01元 

        $safe_total=($remaining_sum-$remain_num*$min)/$remain_num;//安全上限 

        $money=mt_rand($min*100,$safe_total*100)/100; 

        $remaining_sum=bcsub($remaining_sum,$money,2);

        $return_info = array(
                'money' => $money,
                'remaining_sum' => $remaining_sum,
                'remain_num' => $remain_num-1
            );

        return $return_info; 

    }

    /**
     * 实名认证
     * */
    public function identity_certification(){
        $uid = I('uid');
        $real_name = I('real_name');
        $id_number = I('id_number');
        if(!$uid || !$real_name || !$id_number){
            return $this->json(-1,'参数错误');
        }

        //验证身份证
        if(!preg_match('/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/', $id_number)){
            return $this->json(-2,"身份证号码格式不正确");
        }

        //查询身份认证信息
        $find_certification_info = M('identity_certification')->where('uid = '.$uid)->find();
        if($find_certification_info['state'] == 1){
            return $this->json(-3,"申请已提交，正在审核中");
        }

        if($find_certification_info['state'] == 2){
            return $this->json(-4,"实名认证已成功");
        }

        $data['real_name'] = $real_name;
        $data['uid'] = $uid;
        $data['id_number'] = $id_number;
        $data['create_time'] = time();
        $certification_res = M('identity_certification')->add($data);
        if($certification_res){
            return $this->json(0,"实名认证提交成功");
        }else{
            return $this->json(-5,"实名认证提交失败");
        }

    }

    /**
     *判断是否通过实名认证
     * */
    public function is_verify(){
        $uid = I('uid');
        if(!$uid){
            return $this->json(-1,"参数错误");
        }

        $verify_type = "";

        //查询验证信息
        $find_certification_info = M('identity_certification')->where('uid = '.$uid)->find();
        if(!$find_certification_info){
            $verify_type = "1";//认证失败或者没有认证过
        }else{
            if($find_certification_info['state'] == 1){
                $verify_type = "2";//认证审核中
            }else if($find_certification_info['state'] == 2){
                $verify_type = "3";//认证通过
            }
        }

        return $this->json(0,"成功",array('verify_type' => $verify_type));

    }


}


?>