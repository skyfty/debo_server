<?php
/**
 * Created by PhpStorm.
 * User: Barley
 * Date: 2018/6/4
 * Time: 14:23
 * 备忘录
 */

namespace Appapi\Controller\Debo1;

use Common\Controller\ApibaseController;
use Common\Common\Easemob;

class MemorandumController extends ApibaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 增加备忘录
     */
    public function add_memorandum()
    {

        $start_time = I('start_time');
        $end_time = I('end_time');
        $uid = I('uid');
        $rem_time = I('rem_time');
        $rem_content = I('rem_content');
        $content = I('content');
        $status = I('status');
        $font_color = I('font_color');   //字体颜色需要
        $m_type = I('m_type');

        $time = time();

        // 如果没有类型设置默认类型 默认工作
        if(!$m_type) {
            $m_type = 1;
        }

        // 如果没有开始时间默认提交时间就是开始时间
        if(!$start_time) {
            $start_time = $time;
        }

        if ($start_time < $time) {
            return $this->json(-2, '开始时间不能小于现在');
        }


        //取消结束时间,结束时间可选
        //判断如果不写结束时间,默认当天23:59:59结束
        if (!$end_time){
             $date_str = date('Y-m-d',$start_time);
             $t_time = strtotime($date_str);
             $end_time = $t_time + 24*3600-1;
        }

        //查询分类信息
        $map['id'] = $m_type;
        $find_info = M('memorandum_type')->where($map)->find();
        if (!$find_info) {
            return $this->json(-4, '分类信息错误');
        }

        if ($m_type > 5 && $find_info['uid'] != $uid) {
            return $this->json(-5, '该分类不属于你,请自行创建使用');
        }

        $data = array();
        //取消结束时间必选
        if (!$start_time || !$content || !$m_type) {
            return $this->json(-1, '缺少参数');
        }

        if ($rem_time) {
            $data['rem_time'] = $rem_time;
            $data['rem_content'] = $rem_content;
        }else{
            $data['rem_time'] = 0;
        }

        $img_arr = array();
        //判断是否有图片上传
        if (!empty($_FILES)) {
            $info = $this->upload("memorandum/" . $uid);
            foreach ($info as $key => $value) {
                switch ($value['key']) {
                    case 'img1':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img2':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img3':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img3':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img4':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img5':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img6':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img7':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img8':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img9':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                }
            }

            $data['path'] = json_encode($img_arr);
        }

        //组装数据
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $data['uid'] = $uid;
        $data['content'] = $content;
        $data['status'] = 0;
        $data['font_color'] = $font_color;
        $data['m_type'] = $m_type;

        $res = M('memorandum')->add($data);
        if ($res) {
            return $this->json(0, '添加成功');
        } else {
            return $this->json(-3, '添加失败');
        }


    }

    /**
     * 删除备忘录,某类型全部删除
     */
    public function del_memorandum()
    {

        $uid = I('uid');
        $m_type = I('m_type');
        if (!$uid || !$m_type) {
            return $this->json(-1, '参数不全');
        }

        //判断类型是否存在且属于自己
        $conditon1['id'] = $m_type;
        $class_info = M('memorandum_type')->where($conditon1)->find();
        if (!$class_info) {
            return $this->json(-3, '分类信息有误');
        }
        //除系统分类后的归属
        if ($class_info['id'] > 5 && $class_info['uid'] != $uid) {
            return $this->json(-4, '分类归属有问题');
        }

        $map['uid'] = $uid;
        $map['m_type'] = $m_type;
        $res = M('memorandum')->where($map)->delete();
        if ($res) {
            return $this->json(0, '删除成功');
        } else {
            return $this->json(-2, '删除失败,请稍后再试');
        }
    }

    /**
     * 删除某单个备忘录
     */
    public function del_one_memorandum()
    {
        $uid = I('uid');
        $m_id = I('m_id');//备忘录id

        if (!$uid || !$m_id) {
            return $this->json(-1, '参数不全');
        }

        $map['uid'] = $uid;
        $map['id'] = $m_id;
        $m_info = M('memorandum')->where($map)->find();
        if (!$m_info) {
            return $this->json(-2, '备忘信息不存在');
        }

        $result = M('memorandum')->where($map)->delete();
        if ($result) {
            return $this->json(0, '删除成功');
        } else {
            return $this->json(-3, '删除失败,请稍后再试');
        }
    }

    /**
     * 首页备忘录列表
     */
    public function list_memorandum()
    {
        //默认当天的
        $uid = I('uid');
        $time = I('time');
        if (!$uid) {
            return $this->json(-1, '参数不全');
        }

        if ($time) {
            $d_start = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
            $d_end = mktime(0, 0, 0, date('m', $time), date('d', $time) + 1, date('Y', $time)) - 1;
            $map['start_time'] = array('BETWEEN', array($d_start, $d_end));
        } else {
            //计算当天的开始结束时间戳
            $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
            $map['start_time'] = array('BETWEEN', array($beginToday, $endToday));
        }

        $map['uid'] = $uid;
        $m_info = M('memorandum')->where($map)->order('id asc')->select();

        if (!$m_info) {
            return $this->json(-2, '暂无事件');
        }

        //组装返回数据
        $back_arr = array();
        foreach ($m_info as $key => $value) {
            $back_arr[$key]['id'] = $value['id'];
            $back_arr[$key]['start_time'] = $value['start_time'];
            $back_arr[$key]['end_time'] = $value['end_time'];
            $back_arr[$key]['content'] = $value['content'];
            $back_arr[$key]['status'] = $value['status'];
            $back_arr[$key]['m_type'] = $value['m_type'];
            $back_arr[$key]['font_color'] = $value['font_color'];
//            if (!$value['rem_time'] || !$value['rem_content']) {
            if (!$value['rem_time']) {
                $back_arr[$key]['is_remind'] = 0;//没有提醒时间和内容
            } else {
                $back_arr[$key]['is_remind'] = 1;
                $back_arr[$key]['rem_time'] = $value['rem_time'];
                $back_arr[$key]['rem_content'] = $value['rem_content'];
            }
            if (!$value['path']) {
                $back_arr[$key]['has_img'] = 0;//没有图片就是0, 有图片返回图片地址img_url
            } else {
                $back_arr[$key]['has_img'] = 1;
                $back_arr[$key]['img_url'] = json_decode($value['path']);
            }

            //根据类型id返回背景颜色
//            $condition['uid'] = $uid;
            $condition['id'] = $value['m_type'];
            $type_info = M('memorandum_type')->where($condition)->find();

            if (!$type_info) {
                return $this->json(-3, '获取分类失败');
            } else {
                $back_arr[$key]['back_color'] = $type_info['back_color'];
                $back_arr[$key]['name'] = $type_info['name'];
            }
        }

        return $this->json(0, '成功', $back_arr);


    }

    /**
     * 新增分类类型
     */
    public function add_class()
    {
        $m_type = I('m_type');
        $uid = I('uid');
        $font_color = I('back_color');

        if (!$m_type || !$uid) {
            return $this->json(-1, '缺少参数');
        }

        if (!$font_color) {
            $font_color = 1;
        }
        $sys_type = M('memorandum_type')->where('is_sys = 0')->select();
        $type_arr = array();
        foreach ($sys_type as $key => $value) {
            $type_arr[] = $value['name'];
        }

        if (in_array($m_type, $type_arr)) {
            return $this->json(-2, '系统已存在该分类');
        }

        $data['uid'] = $uid;
        $data['name'] = $m_type;
        $data['back_color'] = $font_color;
        $data['is_sys'] = 1;
        $data['m_count'] = 0;

        $result = M('memorandum_type')->add($data);
        $info['class_id'] = $result;
        if ($result) {
            return $this->json(0, '添加分类成功', $info); //将新增类型的id传回去
        } else {
            return $this->json(-3, '添加失败');
        }

    }

    /**
     * 删除分类
     */
    public function del_class()
    {
        $uid = I('uid');
        $class_id = I('class_id');
        if (!$uid) {
            return $this->json(-1, '参数不全');
        }
        if ($class_id >= 1 && $class_id <= 5) {
            return $this->json(-2, '系统分类不能删除');
        }
        $map['uid'] = $uid;
        $map['id'] = $class_id;
        $find_info = M('memorandum_type')->where($map)->find();
        if (!$find_info) {
            return $this->json(-4, '查找分类失败,请稍后再试');
        }

        $res = M('memorandum_type')->where($map)->delete();
        if ($res) {
            return $this->json(0, '删除分类成功');
        } else {
            return $this->json(-3, '删除分类失败,请稍后再试');
        }

    }

    /**
     * 返回每个人的分类,包括系统默认的5个分类
     */
    public function get_class()
    {
        $uid = I('uid');
        if (!$uid) {
            return $this->json(-1, '参数不全');
        }

        $condtion['uid'] = $uid;
        $condtion['is_sys'] = array('EQ', 0);
        $condtion['_logic'] = 'OR';
        $type_array = M('memorandum_type')->field('id,name,back_color')->where($condtion)->select();
        if (!$type_array) {
            return $this->json(-1, '获取分类信息失败,请稍后再试');
        }

        $new_arr = array();
        foreach ($type_array as $kk => $vv) {
            $new_arr[$kk]['name'] = $vv['name'];
            $new_arr[$kk]['m_type'] = $vv['id'];
            $new_arr[$kk]['back_color'] = $vv['back_color'];
            $new_arr[$kk]['count'] = M('memorandum')->where('uid=' . $uid . ' and m_type=' . $vv['id'])->count();
        }

        return $this->json(0, '成功', $new_arr);

    }

    /**
     * 点击某个分类进去某个分类的详情-全部
     */
    public function one_class_info()
    {
        $uid = I('uid');
        $class_id = I('class_id');
        if (!$uid || !$class_id) {
            return $this->json(-1, '参数不全');
        }

        $map['uid'] = array('eq', $uid);
        $map['m_type'] = array('eq', $class_id);
        $class_info = M('memorandum')->where($map)->select();
        if (!$class_info) {
            return $this->json(-2, '该分类下没有数据');
        }
        $new_arr = array();
        foreach ($class_info as $key => $value) {

            $new_arr[$key]['id'] = $value['id'];
            $new_arr[$key]['start_time'] = $value['start_time'];
            $new_arr[$key]['end_time'] = $value['end_time'];
            $new_arr[$key]['content'] = $value['content'];
            $new_arr[$key]['status'] = $value['status'];
            $new_arr[$key]['m_type'] = $value['m_type'];
            $new_arr[$key]['font_color'] = $value['font_color'];

            if (!$value['rem_time'] || !$value['rem_content']) {
                $new_arr[$key]['is_remind'] = 0;//没有提醒时间和内容
            } else {
                $new_arr[$key]['is_remind'] = 1;
                $new_arr[$key]['rem_time'] = $value['rem_time'];
                $new_arr[$key]['rem_content'] = $value['rem_content'];
            }
            if (!$value['path']) {
                $new_arr[$key]['has_img'] = 0;//没有图片就是0, 有图片返回图片地址img_url
            } else {
                $new_arr[$key]['has_img'] = 1;
                $new_arr[$key]['img_url'] = json_decode($value['path']);
            }
        }

        return $this->json(0, '成功', $new_arr);
    }

    /**
     * 点击某个分类进去某个分类的详情-已完成
     */
    public function one_class_info_done()
    {
        $uid = I('uid');
        $class_id = I('class_id');
        if (!$uid || !$class_id) {
            return $this->json(-1, '参数不全');
        }

        $map['uid'] = array('eq', $uid);
        $map['m_type'] = array('eq', $class_id);
        $map['status'] = array('eq', 1);
        $class_info = M('memorandum')->where($map)->select();
        if (!$class_info) {
            return $this->json(-2, '该分类下没有数据');
        }
        $new_arr = array();
        foreach ($class_info as $key => $value) {

            $new_arr[$key]['id'] = $value['id'];
            $new_arr[$key]['start_time'] = $value['start_time'];
            $new_arr[$key]['end_time'] = $value['end_time'];
            $new_arr[$key]['content'] = $value['content'];
            $new_arr[$key]['status'] = $value['status'];
            $new_arr[$key]['m_type'] = $value['m_type'];
            $new_arr[$key]['font_color'] = $value['font_color'];

            if (!$value['rem_time'] || !$value['rem_content']) {
                $new_arr[$key]['is_remind'] = 0;//没有提醒时间和内容
            } else {
                $new_arr[$key]['is_remind'] = 1;
                $new_arr[$key]['rem_time'] = $value['rem_time'];
                $new_arr[$key]['rem_content'] = $value['rem_content'];
            }
            if (!$value['path']) {
                $new_arr[$key]['has_img'] = 0;//没有图片就是0, 有图片返回图片地址img_url
            } else {
                $new_arr[$key]['has_img'] = 1;
                $new_arr[$key]['img_url'] = json_decode($value['path']);
            }
        }

        return $this->json(0, '成功', $new_arr);
    }

    /**
     * 点击某个分类进去某个分类的详情-未完成
     */
    public function one_class_info_undo()
    {
        $uid = I('uid');
        $class_id = I('class_id');
        if (!$uid || !$class_id) {
            return $this->json(-1, '参数不全');
        }

        $map['uid'] = array('eq', $uid);
        $map['m_type'] = array('eq', $class_id);
        $map['status'] = array('eq', 0);
        $class_info = M('memorandum')->where($map)->select();
        if (!$class_info) {
            return $this->json(-2, '该分类下没有数据');
        }
        $new_arr = array();
        foreach ($class_info as $key => $value) {

            $new_arr[$key]['id'] = $value['id'];
            $new_arr[$key]['start_time'] = $value['start_time'];
            $new_arr[$key]['end_time'] = $value['end_time'];
            $new_arr[$key]['content'] = $value['content'];
            $new_arr[$key]['status'] = $value['status'];
            $new_arr[$key]['m_type'] = $value['m_type'];
            $new_arr[$key]['font_color'] = $value['font_color'];

            if (!$value['rem_time'] || !$value['rem_content']) {
                $new_arr[$key]['is_remind'] = 0;//没有提醒时间和内容
            } else {
                $new_arr[$key]['is_remind'] = 1;
                $new_arr[$key]['rem_time'] = $value['rem_time'];
                $new_arr[$key]['rem_content'] = $value['rem_content'];
            }
            if (!$value['path']) {
                $new_arr[$key]['has_img'] = 0;//没有图片就是0, 有图片返回图片地址img_url
            } else {
                $new_arr[$key]['has_img'] = 1;
                $new_arr[$key]['img_url'] = json_decode($value['path']);
            }
        }

        return $this->json(0, '成功', $new_arr);
    }

    /**
     * 单个日程详情
     */
    public function one_mem()
    {
        $uid = I('uid');
        $m_id = I('m_id');
        if (!$uid || !$m_id) {
            return $this->json(-1, '参数不正确');
        }

        $map['id'] = array('eq', $m_id);
        $map['uid'] = array('eq', $uid);
        $one_info = M('memorandum')->where($map)->find();

        if ($one_info['rem_time'] == '') {
            $one_info['is_remind'] = 0;
            unset($one_info['rem_time']);
            unset($one_info['rem_content']);
        }else{
            $one_info['is_remind'] = 1;
        }
        if (!$one_info) {
            return $this->json(-2, '打开备忘出错,请稍后再试');
        }


        if ($one_info['path'] == '') {
            unset($one_info['path']);
        } else {
            $one_info['path'] = json_decode($one_info['path']);
        }

        $weekarray=array("周日","周一","周二","周三","周四","周五","周六");
        //获取分类名字
        $where['id'] = $one_info['m_type'];
        $name = M('memorandum_type')->where($where)->find();
        if (!$name) {
            return $this->json(-3, '获取分类失败,请稍后再试');
        }
        $one_info['name'] = $name['name'];
        $one_info['back_color'] = $name['back_color'];
        $one_info['weak'] =$weekarray[date('w',$one_info['start_time'])];
        return $this->json(0, '成功', $one_info);
    }

    /**
     * 数据统计
     */
    public function data_statistics()
    {
        $uid = I('uid');
        if (!$uid) {
            return $this->json(-1, '参数不全');
        }
        //统计这个人的所有分类及每个分类里有多少数据
        $return = array();
        $type_array = M('memorandum')->field('m_type')->where('uid=' . $uid)->select();
        foreach ($type_array as $key => $value) {
            foreach ($value as $k => $v) {
                $return[$key]['value'] = M('memorandum')->where('m_type=' . $v)->count();
                $return[$key]['name'] = M('memorandum_type')->where('id=' . $v)->getField('name');
            }
        }
        $res = unique_arr($return);


        foreach ($res as $key1 => $info1) {
            $x[] = $info1;

        }

        //要返回的数据:总计,已完成,每个完成的百分比
        $total_count = M('memorandum')->where('uid=' . $uid)->count();
        $all_done_count = M('memorandum')->where('uid=' . $uid . ' and status=1')->count();

        $return1 = array();
        $type_array = M('memorandum')->field('m_type')->where('uid=' . $uid)->select();
        foreach ($type_array as $key => $value) {
            foreach ($value as $k => $v) {
                $return1[$key]['count'] = $count = M('memorandum')->where('m_type=' . $v)->count();
                $return1[$key]['done_count'] = $done = M('memorandum')->where('m_type=' . $v . ' and status=1')->count();
                $return1[$key]['percent'] = $done / $count * 100;
                $return1[$key]['name'] = M('memorandum_type')->where('id=' . $v)->getField('name');
            }
        }
        $res1 = unique_arr($return1);
        foreach ($res1 as $key => $v) {
            $new[] = $v;
        }

        $this->assign('res1', $new);
        $this->assign('total_count', $total_count);
        $this->assign('all_done', $all_done_count);
        $xx = json_encode($x);
        $this->assign('res', $xx);
        $this->display();
    }

    /**
     * 返回数据统计所需的数据
     */
    public function data_statis()
    {
        $uid = I('uid');
        if (!$uid) {
            return $this->json(-1, '参数不全');
        }
        //要返回的数据:总计,已完成,每个完成的百分比
        $total_count = M('memorandum')->where('uid=' . $uid)->count();
        $all_done_count = M('memorandum')->where('uid=' . $uid . ' and status=1')->count();

        $return = array();
        $type_array = M('memorandum')->field('m_type')->where('uid=' . $uid)->select();
        foreach ($type_array as $key => $value) {
            foreach ($value as $k => $v) {
                $return[$key]['count'] = $count = M('memorandum')->where('m_type=' . $v)->count();
                $return[$key]['done_count'] = $done = M('memorandum')->where('m_type=' . $v . ' and status=1')->count();
                $return[$key]['percent'] = $done / $count * 100;
                $return[$key]['name'] = M('memorandum_type')->where('id=' . $v)->getField('name');
            }
        }
        $res = unique_arr($return);
        foreach ($res as $key => $v) {
            $new[] = $v;
        }

        $new['total'] = $total_count;
        $new['all_done_count'] = $all_done_count;

        return $this->json(0, '成功', $new);

    }

    /**
     * 定时提醒
     */
    public function remind_me()
    {
        $ease = new Easemob();
        //当天有事件的
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $map['rem_time'] = array('BETWEEN', array($beginToday, $endToday));
        $find_info = M('memorandum')->where($map)->select();
        if (!$find_info) {
            return $this->json(-1, '无提醒事件');
        }
        foreach ($find_info as $key => $value) {
            $retuen[$key]['content'] = $content = $value['rem_content'];
            $retuen[$key]['time'] = $time = $value['rem_time'];
            $retuen[$key]['mobile'] = $mobile = M('user')->where('id=' . $value['uid'])->getField('mobile');
            $type = '1';
            $ext = array('type' => $type, 'content' => $content, 'send_time' => $time - 3600, 'location' => 'send');
            //重新组装提醒内容
            $content = '您好,您有新的提醒事件: ' . ' 时间:' . date('Y-m-d H:i:s', $time) . ' 提醒内容:' . $content;
            \SeasLog::info('推送提醒:用户手机' . $mobile . ',推送时间:' . time() . ',推送内容:' . $content);
            $res = $ease->sendText('系统消息', 'users', [$mobile], $content, $ext);
            \SeasLog::info('系统发送信息.' . json_encode($res));
        }
    }

    /**
     * 主动单独添加提醒
     */
    public function add_remind()
    {

        $m_id = I('m_id');//备忘录id
        $uid = I('uid');
        $rem_time = I('rem_time');
        $rem_content = I('rem_content');

        if (!$m_id || !$uid) {
            return $this->json(-1, '参数不全');
        }

        //验证归属
        $map['id'] = $m_id;
        $m_info = M('memorandum')->where($map)->find();
        if (!$m_info) {
            return $this->json(-3, '备忘录不存在');
        }

        if ($m_info['uid'] != $uid) {
            return $this->json(-2, '该备忘不属于你');
        }

        $data['rem_time'] = $rem_time;
        $data['rem_content'] = $rem_content;

        $result = M('memorandum')->where($map)->save($data);
        if ($result) {
            return $this->json(0, '添加提醒成功');
        } else {
            return $this->json(-4, '添加提醒失败');
        }
    }

    /**
     * 将整个月的备忘录返回,当月哪天有日程,日程类别,日程数量
     */
    public function month_memorandum()
    {
        $uid = I('uid');
        $time = I('time');
        if (!$uid) {
            return $this->json(-1, '参数不全');
        }
        //获取本月起始时间戳
        if (!$time) {
            $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        } else {
            $beginThismonth = mktime(0, 0, 0, date('m', $time), 1, date('Y', $time));
            $endThismonth = mktime(23, 59, 59, date('m', $time), date('t', $time), date('Y', $time));
        }

        for ($start = $beginThismonth; $start <= $endThismonth; $start += 24 * 3600) {
            $data_arr[] = date('Y-m-d', $start);
        }

        foreach ($data_arr as $key1 => $value1) {
            //根据每天的时间,查当天是否有事件
            $day_start = strtotime($value1);
            $day_end = $day_start + 24 * 3600 - 1;

            if ($map['end_time'] == 0){
                $map['start_time'] = array('BETWEEN',array($day_start,$day_end));
            }else{
                $map['start_time'] = array('EGT', $day_start);
                $map['end_time'] = array('ELT', $day_end);
            }

            $map['uid'] = $uid;
            $day_info = M('memorandum')->where($map)->select();
            $day_count = M('memorandum')->where($map)->count();
            $day_type = M('memorandum')->field('m_type')->where($map)->select();
            foreach ($day_type as $kk1 => $vv1) {
                $color[] = M('memorandum_type')->field('back_color')->where('id=' . $vv1['m_type'])->find();
            }
            $co = unique_arr($color);
            $back_info[$key1]['day'] = $value1;
            $back_info[$key1]['count'] = $day_count;
            if (!$day_count) {
                $back_info[$key1]['color'] = array();
            } else {

                $back_info[$key1]['color'] = $co;
            }
//            $back_info[$key1]['color']
            if (!$day_type) {
                $back_info[$key1]['type'] = array();
            } else {
                $back_info[$key1]['type'] = unique_arr($day_type);
            }

        }



        return $this->json(0, '成功', $back_info);
    }

    /**
     * 主动设置完成事件
     */
    public function complete_event()
    {
        $uid = I('uid');
        $m_id = I('m_id');

        if (!$uid || !$m_id) {
            return $this->json(-1, '参数不全');
        }

        $confiton['id'] = array('eq', $m_id);
        $confiton['uid'] = array('eq', $uid);

        $result = M('memorandum')->where($confiton)->setField('status', 1);
        if ($result) {
            return $this->json(0, '已标注为完成');
        } else {
            return $this->json(-2, '标注失败,请稍后再试');
        }

    }

    /**
     * 返回每个分类里的备忘条数,分类列表用
     */
    public function every_class_count()
    {
        $uid = I('uid');
        if (!$uid) {
            return $this->json(-1, '参数不全');
        }
        //统计这个人的所有分类及每个分类里有多少数据
        $return = array();
        $type_array = M('memorandum')->field('m_type')->where('uid=' . $uid)->select();
        foreach ($type_array as $key => $value) {
            foreach ($value as $k => $v) {
                $return[$key]['count'] = M('memorandum')->where('m_type=' . $v)->count();
                $return[$key]['name'] = M('memorandum_type')->where('id=' . $v)->getField('name');
            }
        }
        $res = unique_arr($return);
        foreach ($res as $key1 => $info1) {
            $x[] = $info1;
        }
        return $this->json(0, '成功', $x);
    }

    /**
     * 编辑备忘录内容
     */
    public function edit_memorandum()
    {

        $start_time = I('start_time');
        $end_time = I('end_time');
        $uid = I('uid');
        $rem_time = I('rem_time');
        $rem_content = I('rem_content');
        $content = I('content');
        $status = I('status');
        $font_color = I('font_color');   //字体颜色需要
        $m_type = I('m_type');//备忘类型
        $status = I('status');
        $m_id = I('m_id');//备忘录id
        $time = time();

        //取消结束时间必选
        if (!$content || !$m_type) {
            return $this->json(-1, '缺少参数');
        }

        //查询分类信息
        $map['id'] = $m_type;
        $find_info = M('memorandum_type')->where($map)->find();
        if (!$find_info) {
            return $this->json(-4, '分类信息错误');
        }

        if ($m_type > 5 && $find_info['uid'] != $uid) {
            return $this->json(-5, '该分类不属于你,请自行创建使用');
        }


        $data = array();//最后要保存的数据
        //查询当前的备忘录内容
        $find_result = M('memorandum')->where('id=' . $m_id)->find();
        if (!$find_result) {
            return $this->json(-6, '信息获取出错,请稍后再试');
        }

        if ($rem_time) {
            $data['rem_time'] = $rem_time;
            $data['rem_content'] = $rem_content;
        } else {
            //如果没有重新设置,取原有数据
            $data['rem_time'] = $find_result['rem_time'];
            $data['rem_content'] = $find_result['rem_content'];
        }

        $img_arr = array();
        //判断是否有图片上传
        if (!empty($_FILES)) {
            $info = $this->upload("memorandum/" . $uid);
            foreach ($info as $key => $value) {
                switch ($value['key']) {
                    case 'img1':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img2':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img3':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img3':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img4':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img5':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img6':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img7':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img8':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                    case  'img9':
                        $img_arr[] = UPLOADS . $value['savepath'] . $value['savename'];
                        break;
                }
            }

            $data['path'] = json_encode($img_arr);
        } else {
            //取原数据
            $data['path'] ='';
        }

        if (!$font_color) {
            $font_color = $find_result['font_color'];
        }

        if (!$m_type) {
            $m_type = $find_result['m_type'];
        }

        if (!$end_time) {
            $end_time = $find_result['end_time'];
        }

        if (!$content) {
            $content = $find_result['content'];
        }

        if (!$status) {
            $status = $find_result['status'];
        }

        if (!$uid) {
            $uid = $find_result['uid'];
        }

        if (!$start_time) {
            $start_time = $find_result['start_time'];
        }

        //组装数据
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $data['uid'] = $uid;
        $data['content'] = $content;
        $data['status'] = $status;
        $data['font_color'] = $font_color;
        $data['m_type'] = $m_type;
        $conditon['id'] = array('EQ', $m_id);
        $res = M('memorandum')->where($conditon)->save($data);
        if ($res) {
            return $this->json(0, '修改成功');
        } else {
            return $this->json(-3, '修改失败');
        }
    }

    /**
     * 每天定时23:59:59完成当天的事件
     */
    public function complete_memorandum(){
        $save['status'] = 1;
        $time = time();
        $tmp = date('Y-m-d',$time);
        $start =  strtotime($tmp);
        $end = strtotime($tmp)+24*3600-1;
        $condition['end_time'] = array('ELT',$end);
        $condition['rem_time'] = array('ELT',$end);
        $condition['status'] = 0;
        $condition['_logic'] = 'AND';
        $res = M('memorandum')->where($condition)->save($save);
        if ($res){
            \SeasLog::info('完成当天事件:'.date('Y-m-d H:i:s',time()));
        }else{
            \SeasLog::info('完成当天事件出错:'.date('Y-m-d H:i:s',time()));
        }
    }

}