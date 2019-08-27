<?php
/**
 * Created by PhpStorm.
 * User: qcwl02
 * Date: 2017/11/21
 * Time: 15:46
 */

namespace Admin\Controller;


use Common\Controller\AdminbaseController;

class VideoController extends AdminbaseController
{
    //展示所有视频
    public function index()
    {
        $where = [];

        $p = I('p');
        if(!$p){
            $p = 1;
        }
        $url = 'http://debo.shangtongyuntian.com/application/Common/Common/allvideo_list.php?pageno='.$p;
        //获取视频播放地址
        $video_play_url = 'http://debo.shangtongyuntian.com/application/Common/Common/admin_get_video_url.php?video_id=';
        //获取接口中的信息
        $res = $this->curl_get($url);
        $result = json_decode($res,true);//将json格式的数据转化为数组，true将对象数组转化为键值索引数组

        if(!$result['info_list']){
            echo '没有更多数据了';
        }else{
            $count = $result['total'];
            $page = $this->page($count,10);
            //收集视频id
            $video_id_arr = array();
            foreach($result['info_list'] as $key => $val){
                $video_id_arr[]=$val['VideoId'];
            }

            $play_info = array();
            foreach($video_id_arr as $k => $v){
                $get_play_info = $this->curl_get($video_play_url.$v);
                $get_play_info = json_decode($get_play_info,true);
                $play_info[]=$get_play_info;
            }
            $state = I('state');
            $user_nickname = I('content');
            if($state)
            {
                $where['state'] = $state;

            }
            if($user_nickname)
            {
                $where['de_joke_video_posi.uid | de_joke_video_posi.user_nickname'] = $user_nickname;
            }
            $resulta = M('joke_video_posi')->group('state')->select();
            $where['de_joke_video_posi.video_id'] = array('in',$video_id_arr);
            $field="de_joke_video_posi.id as jvp_id,de_joke_video_posi.uid,de_joke_video_posi.video_id,de_joke_video_posi.city,de_user.id,de_user.user_nickname,de_joke_video_posi.state";
            $user_info = M('joke_video_posi')->join('de_user on de_joke_video_posi.uid = de_user.id')->field($field)->where($where)->select();

            foreach($result['info_list'] as $kk => $vv){
                foreach($user_info as $kk2 => $vv2){
                    if($vv['VideoId'] == $vv2['video_id']){
                        $result['info_list'][$kk]['id'] = $vv2['jvp_id'];
                        $result['info_list'][$kk]['user_nickname'] = $vv2['user_nickname'];
                        $result['info_list'][$kk]['city'] = $vv2['city'];
                    }
                }

                foreach($play_info as $kk3 => $vv3){
                    if($vv['VideoId'] == $vv3['video_id']){
                        $result['info_list'][$kk]['PlayURL'] = $vv3['PlayURL'];
                    }

                }
            }

            $this->assign('result',$result['info_list']);
            $this->assign('resulta',$resulta);
            $this->assign('page',$page->show('Admin'));
            $this->display();
        }


    }

    public function curl_get($url, $header = null)
    {
        $my_curl = curl_init();
        curl_setopt($my_curl, CURLOPT_URL, $url);
        curl_setopt($my_curl, CURLOPT_RETURNTRANSFER, 1);

        if ($header) {
            $header_list = array();
            foreach ($header as $key => $value) {
                $header_list[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header_list);
        }

        $str = curl_exec($my_curl);
        curl_close($my_curl);

        return $str;
    }
//post
    public function curl_post($url, $data, $header = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->to_params($data));

        if ($header) {
            $header_list = array();
            foreach ($header as $key => $value) {
                $header_list[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header_list);
        }

        $str = curl_exec($ch);
        curl_close($ch);

        return $str;
    }
    public function to_params($input)
    {
        $index = 0;
        $pair = '';
        foreach ($input as $key => $value) {
            if ($index != 0) {
                $pair .= '&';
            }
            $pair .= "$key=".$value;
            ++$index;
        }

        return $pair;
    }
//视频删除
    public function delete()
    {
        $video_id = I('video_id');
        //获取删除接口
        $url = 'http://debo.shangtongyuntian.com/application/Common/Common/del_video_stream.php';
        $data['video_id'] = $video_id;
         $res = $this->curl_post($url,$data);
         $res = json_decode($res,true);
        if($res['code'] == 0)
        {
            //$where['video_id'] = array('in',$video_id);
            //$result = M('joke_video_posi')->where($where)->delete();
//            if($result)
//            {
//                $this->success('删除成功');
//            }else{
//                $this->error('删除失败');
//            }
            $this->success('删除成功');
        }else{
            $this->error('服务器删除视频失败');
        }


    }

}