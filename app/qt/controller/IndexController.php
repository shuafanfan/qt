<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\qt\controller;

use cmf\controller\HomeBaseController;
use think\Db;
use think\Request;

class IndexController extends HomeBaseController
{

	//首页
    public function index()
    {	
        $a=Db::connect('mysql://wap_qt120_com:wap_qt120_com@127.0.0.1:3306/wap_qt120_com#utf8');
        $consult=$a->name("php34_consult")->where(['status'=>10])->order('id desc')->find();          
        $consult['user_name']=mb_substr($consult['user_name'],0,1,'utf-8')."**";
        $consult['phone']=hide_phone($consult['phone']) ;             //病人预约
        //dump($consult);die;
        //$cate=Db::name("portal_category")->where(['delete_time' => 0])->select();   //疾病分类
        $slide=Db::name("slide_item")->where(['status' => 1,'slide_id'=>1])->select();       //顶部轮播图
        $slide2=Db::name("portal_category_post")
            ->alias("a")
            ->join('portal_post b', 'a.post_id =b.id')
            ->where(['a.status' => 1,'a.category_id'=>16])
            ->select()
            ->toArray();      //学术交流轮播图
        $slide3=Db::name("portal_category_post")
            ->alias("a")
            ->join('portal_post b', 'a.post_id =b.id')
            ->where(['a.status' => 1,'a.category_id'=>17])
            ->select()
            ->toArray();       //社会地位轮播图
        $slide4=Db::name("slide_item")->where(['status' => 1,'slide_id'=>6])->select()->toArray();       //临床基地轮播图
        $slide2=array_chunk($slide2,2);
        $slide3=array_chunk($slide3,2);
        $slide4=array_chunk($slide4,2);
        $doctor=Db::name("user")->where(['is_doctor' => 0])->select();       //医生
        //dump($slide2);die;
    	$post=Db::name("portal_category_post")
                    ->alias("a")
                    ->join('portal_post b', 'a.post_id =b.id')
                    ->join('portal_category c', 'a.category_id =c.id')
                    ->where(['a.status' => 1,'b.recommended'=>1])
                    ->select();       //推荐文章

        $post2=Db::name("portal_category_post")
            ->alias("a")
            ->join('portal_post b', 'a.post_id =b.id')
            ->join('portal_category c', 'a.category_id =c.id')
            ->where(['a.status' => 1])
            ->order('create_time desc')
            ->limit(0,5)
            ->select();       //普通文章
           
        //$this->assign('cate',$cate);
        $this->assign('slide',$slide);
        $this->assign('slide2',$slide2);
        $this->assign('slide3',$slide3);
        $this->assign('slide4',$slide4);
        $this->assign('doctor',$doctor);
        $this->assign('recommended',$post);
        $this->assign('post',$post2);
    	$this->assign('consult',$consult);
        return $this->fetch('index');
    }


    //文章列表页
    public function post_list()
    {
        $keyword=Request::instance()->param('keyword');
        if(empty($keyword)){
            $id=Request::instance()->param('cate_id');
        }else{
            $data['name'] = array('like', "%".$keyword."%");
            $data['delete_time'] = 0;
            $cate=Db::name("portal_category")->where($data)->find();
            $id=$cate['id'];
        }

        
        $recommended=Db::name("portal_category_post")
                    ->alias("a")
                    ->join('portal_post b', 'a.post_id =b.id')
                    ->join('portal_category c', 'a.category_id =c.id')
                    ->where(['a.status' => 1,'b.recommended'=>1,'a.category_id'=>$id])
                    ->select()       //推荐文章
                    ->toArray();

        $post2=Db::name("portal_category_post")
            ->alias("a")
            ->join('portal_post b', 'a.post_id =b.id')
            ->join('portal_category c', 'a.category_id =c.id')
            ->where(['a.status' => 1,'a.category_id'=>$id])
            ->order('create_time desc')
            ->limit(0,5)
            ->select()       //普通文章
            ->toArray();
        if(empty($recommended)||empty($post2)){
             return $this->fetch('nothing');
        }
        $a=Db::connect('mysql://wap_qt120_com:wap_qt120_com@127.0.0.1:3306/wap_qt120_com#utf8');
        $consult=$a->name("php34_consult")->where(['status'=>10])->order('id desc')->find();          
        $consult['user_name']=mb_substr($consult['user_name'],0,1,'utf-8')."**";
        $consult['phone']=hide_phone($consult['phone']) ;             //病人预约

        $this->assign('recommended',$recommended);
        $this->assign('post',$post2);
        $this->assign('consult',$consult);
        return $this->fetch('post_list');
    }

    //更多文章
    public function more_post(){
        $num=Request::instance()->param('num');
        $cate_id=Request::instance()->param('cate_id');
        if(empty($cate_id)){
            $cate_id="";
        }
        $post=Db::name("portal_category_post")
            ->alias("a")
            ->join('portal_post b', 'a.post_id =b.id')
            ->join('portal_category c', 'a.category_id =c.id')
            ->where(['a.status' => 1,'a.category_id'=>$cate_id])
            ->order('create_time desc')
            ->limit($num,5)
            ->select()
            ->toArray();
        foreach ($post as $key => $value) {
             $post[$key]['href']=url("qt/index/post_info",["post_id"=>$value["post_id"]]);
             $post[$key]['create_time']=date('Y-m-d',$value['create_time']);
         } 
        return json($post);
    }

    // 文章详情
    public function post_info(){

        $id=Request::instance()->param('post_id'); 
        $num=Db::name("portal_post")->where(['id'=>$id])->setInc('post_hits');

        $post=Db::name("portal_category_post")
                ->alias("a")
                ->join('portal_post b', 'a.post_id =b.id')
                ->join('portal_category c', 'a.category_id =c.id')
                ->where(['a.status' => 1,'b.id'=>$id])
                ->find();       //文章详情

        $doctor=Db::name("user")->where(['user_status' => 1,'is_top'=>1])->select();  //推荐医生

        $recommended=Db::name("portal_category_post")
            ->alias("a")
            ->join('portal_post b', 'a.post_id =b.id')
            ->join('portal_category c', 'a.category_id =c.id')
            ->where(['a.status' => 1,'b.recommended'=>1])
            ->select();       //推荐文章

        $this->assign('recommended',$recommended);
        $this->assign('post',$post);
        $this->assign('doctor',$doctor);

        return $this->fetch('post_info');
    }


    //专家团队
    public function doctor_list(){
        $doctor=Db::name("user")->where(['is_doctor' => 0])->limit(0,5)->select();       //医生
        $this->assign('doctor',$doctor);
        return $this->fetch('doctor_list');
    }

    //加载更多医生
    public function more_doctor(){
         $num=Request::instance()->param('num');
         $doctor=Db::name("user")->where(['is_doctor' => 0])->limit($num,5)->select()->toArray();
         foreach ($doctor as $key => $value) {
             $doctor[$key]['href'] = url('qt/index/doctor_info',['doctor_id'=>$value['id']]);
         }
         return json($doctor);
    }    

    //医生简介
    public function doctor_info(){
        $id=Request::instance()->param('doctor_id');
        $doctor=Db::name("user")->where(['is_doctor' => 0,'id'=>$id])->find();       //医生
        $recommended=Db::name("portal_category_post")
            ->alias("a")
            ->join('portal_post b', 'a.post_id =b.id')
            ->join('portal_category c', 'a.category_id =c.id')
            ->where(['a.status' => 1,'b.recommended'=>1])
            ->select();       //推荐文章

        $this->assign('recommended',$recommended);
        $this->assign('doctor',$doctor);
        return $this->fetch('doctor_info');
    }

    //专病专方
    public function zbzf(){
        return $this->fetch('zbzf');
    }

    // 预约挂号
    public function order(){
        return $this->fetch('order');
    }

    // 专家预约挂号
    public function doctor_order(){
        $id=Request::instance()->param('doctor_id');
        $doctor=Db::name("user")->where(['is_doctor' => 0,'id'=>$id])->find();       //医生
        $this->assign('doctor',$doctor);
        return $this->fetch('doctor_order');
    }
}
