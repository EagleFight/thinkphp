<?php
namespace Hmng\Controller;
use Think\Controller;
class UserController extends BaseController {
	// 待方案用户
    public function index(){
        $this->assign('title','待方案用户');
        $this->userList(0);
    }
	// VIP用户
    public function vipUser(){
        $this->assign('title','VIP用户');
        $this->userList(3);
    }	
    // 已推送方案用户
    public function pushed(){
        $this->assign('title','已推送方案用户');
        $this->userList(2);
    }    
    // 草稿方案
    public function draft(){
        $this->assign('title','草稿方案');
        $this->userList(1);
    }
    // 列表
    private function userList($type=''){
        $search = I('get.search');
        $where = array();
        $where['user_type'] = $type;
        if(!empty($search)){
            $map['mobile'] = $search;
            $map['name'] = array('like','%'.$search.'%');
            $map['_logic'] = 'OR';
            $where['_complex'] = $map;
        }                
        $M = M('health_user');
        $page = new \Think\Page($M->where($where)->count(), 10);
        $page->setConfig('header', '<span class="total">共%TOTAL_PAGE%页 %TOTAL_ROW%条数据</span>');
        $page->setConfig('prev', '上一页');
        $page->setConfig('next', '下一页');
        $page->setConfig('first', '首页');
        $page->setConfig('last', '末页');
        $page->setConfig('theme', '%HEADER%%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%');
        $page->rollPage = 10;
        $page->lastSuffix = false;      
        $this->page = $page->show();
        $list = $M->where($where)->field($field)->limit($page->firstRow . ',' . $page->listRows)->order('health_uid desc')->select();        
        $this->list = $list;
        $this->type = $type;
        $this->display('list');        
    }

    // 用户详情
    public function detailUser($action=1){
        if(IS_POST){
            $post = I('post.');
            $saveData = array();
            $saveData['classify_id'] = $post['classify_id'];
            $saveData['setmeal_id'] = $post['setmeal_id'];
            $saveData['name'] = $post['name'];
            $saveData['sex'] = $post['sex'];
            $saveData['birthyear'] = $post['age'];
            $saveData['mobile'] = $post['mobile'];
            $saveData['explain'] = $post['explain'];
            $saveData['create_time'] = time();
            $rules = array(
                array('name', 'require', '请输入用户姓名', 1, 'regex', 1),
                array('sex', 'require', '请选择性别', 1, 'regex', 1),
                array('birthyear', 'require', '请输入用户年龄', 1, 'regex', 1),
                array('mobile', 'require', '请输入手机号', 1, 'regex', 1),                
                array('classify_id', 'require', '请选择套餐类型', 1, 'regex', 1),
                array('setmeal_id', 'require', '请选择套餐', 1, 'regex', 1),
            );
            $M = M('health_user');
            if (!$M->validate($rules)->create($saveData)) {
                $this->ajaxReturn(['status' => 0, 'msg' => $M->getError()]);
            } else {
                $saveData['birthyear'] = yearToAge($saveData['birthyear']);
                $Model = M();
                $Model -> startTrans();                                
                if($post['action']==2&&!empty($post['uid'])){
                    $uid = $post['uid'];
                    // 编辑
                    $user = $M->where(['health_uid'=>$uid])->find();
                    if($user['user_id']==0){
                        // 后台手动录入，可修改所有信息、删除原来答案
                        $res = $M->where(['health_uid'=>$uid])->save($saveData);
                        if($res) M('health_answer')->where(['health_uid'=>$uid])->delete();                       
                    }else{
                        // 微信提交、只修改备注
                        $resEdit = $M->where(['health_uid'=>$post['uid']])->setField('explain',$saveData['explain']);
                    }
                }else{
                    // 新增
                    $res = $M->data($saveData)->add();
                    $uid = $res;                     
                }
                if($res){
                    $saveAnswer = array();
                    $topicId = $post['topic_id'];
                    foreach ($post['answer'] as $k => $v) {
                        if(empty($v)) $this->ajaxReturn(['status' => 0, 'msg' => '请填写完整的问卷']);
                        $saveAnswer[$k]['health_uid'] = $uid;
                        $saveAnswer[$k]['answer'] = $v;
                        $saveAnswer[$k]['is_choose'] = 0;
                        $saveAnswer[$k]['topic_id'] = $topicId[$k];
                    }
                    $resAnswer = M('health_answer')->addAll($saveAnswer);
                }
                if(($res&&$resAnswer)||$resEdit){
                    $Model->commit();
                    $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
                }else{
                    $Model->rollback();
                    $this->ajaxReturn(['status' => 0, 'msg' => '失败！原因可能如下：<br>1.未作修改<br>2.微信上传用户只能修改备注']);
                }                               
            }            
            
        }else{
            $classify = M('health_classify')->order('sort asc')->select();
            $this->classify = $classify;
            $this->title = '录入新用户';
            $this->action = $action;
            $this->uid = 0;
            // 编辑操作
            if($action==2){
                $healthUid = I('get.uid');
                $data = M('health_user')->where(['health_uid'=>$healthUid])->find();
                if(!$data) $this->error('参数错误！');
                $M = M('health_answer');
                $this->setmealList = M('health_setmeal')->where(['classify_id'=>$data['classify_id']])->select();
                $topicList = $M->alias('m')->join('left join health_topic as c on m.topic_id=c.topic_id')->where(['m.health_uid'=>$healthUid])->field('m.answer,m.topic_id,m.option_id,c.topic_name')->select();
                if(empty($topicList)){
                    $topicList = M('health_topic')->where(['classify_id'=>$data['classify_id']])->select();
                }
                if($data['user_id']!=0){
                    $answer = $M->where(['health_uid'=>$healthUid])->getField('option_id',true);
                    $optionIds = array();
                    foreach ($answer as $ke => $va) {
                        $va = explode('|', $va);
                        $va = array_filter(array_unique($va));
                        foreach ($va as $ks => $vs) {
                            $optionIds[] = $vs;
                        }
                    }
                    $options = M('health_option')->where(['option_id'=>array('in',$optionIds)])->select();
                    $answerArr = array();
                    foreach ($options as $keys => $vals) {
                        $answerArr[$vals['topic_id']][] = $vals['option_name'];
                    }
                    foreach ($topicList as $key => $val) {
                        $topicList[$key]['answer'] = implode(';',$answerArr[$val['topic_id']]);
                    }
                }            
                $this->uid = $healthUid;
                $this->topicList = $topicList;
                $this->title = '编辑/查看用户';
                // 方案的管理偏向
                $this->point = M('health_tags')->where(['tags_type'=>2])->select();
                // 方案的服务内容
                $service = D('health_service')->relation(true)->select();
                // 方案页
                $getProjectId = I('get.project_id');
                $type = I('get.type'); 
                //判断是否是VIP用户新增方案
                if(empty($type)||$type!='new'){
                    $map = array();
                    $map['health_uid'] = $healthUid;
                    if(!empty($getProjectId)) $map['project_id'] = $getProjectId;
                    $project = M('health_project')->where($map)->find();
                    $projectId = $project['project_id'];
                    $serviceArr = M('health_project_service')->where(['project_id'=>$projectId])->getField('service_id,project_id,text_explain,tags_ids');
                    if($serviceArr){
                        foreach ($service as $kes => $vals) {
                            $sid = $vals['service_id'];
                            $service[$kes]['text_explain'] = $serviceArr[$sid]['text_explain'];
                            $service[$kes]['tags_ids'] = $serviceArr[$sid]['tags_ids'];                        
                        }
                    }
                    if($project) $data['project'] = $project;                    
                }else{
                    $this->actionType = 'new';
                }
                $this->service = $service;
                $this->data = $data;                
            }            
            $this->display();            
        }        
    }
    public function saveScheme(){
        $post = I('post.');
        $acid = $post['acid'];
        $uid = $post['uid'];
        $saveData = array();
        if(empty($uid)||$uid==0) $this->ajaxReturn(['status' => 2, 'msg' => '非法操作1']); 
        if($acid!=1&&$acid!=2&&$acid!=100) $this->ajaxReturn(['status' => 2, 'msg' => '非法操作2']);  //100为预览
        if(empty($post['tags_ids'])||count($post['tags_ids'])!=3) $this->ajaxReturn(['status' => 2, 'msg' => '请选择三种管理偏向！']); 
        $saveData['health_uid'] = $uid;
        $saveData['title'] = $post['title'];
        $saveData['analyze'] = $post['analyze'];
        $saveData['explain'] = $post['explain'];
        $saveData['tags_ids'] = implode(',',$post['tags_ids']);
        $saveData['create_time'] = time();
        $rules = array(
            array('health_uid', 'require', '用户信息错误', 1, 'regex', 1),
            array('title', 'require', '请填写标题', 1, 'regex', 1),
            array('analyze', 'require', '请输入需求分析', 1, 'regex', 1),
            array('explain', 'require', '请输入方案说明', 1, 'regex', 1)
        );
        $M = M('health_project');
        if (!$M->validate($rules)->create($saveData)) {
            $this->ajaxReturn(['status' => 2, 'msg' => $M->getError()]);
        } else {
            $Model = M();
            $Model -> startTrans();
            $user = M('health_user')->where(['health_uid'=>$uid])->find();
            $userType = $user['user_type'];
            if($acid!=100){
                if($userType<$acid){
                    $resUser = M('health_user')->where(['health_uid'=>$uid])->setField('user_type',$acid);
                }elseif ($userType!=3&&$userType>$acid) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '已推送用户无法保存草稿']);  
                }else{
                    $resUser = true;
                } 
            }else{
                $resUser = true;
            }
            $userInfo = $M->where(['health_uid'=>$uid])->find();
            $actionType = $post['action_type'];
            if(!$userInfo||($userInfo&&$actionType=='new'&&$userType==3)){
                $res = $M->data($saveData)->add();
                $saveProjectId = $res;
            }else{
                $project_id = $post['project_id'];
                unset($saveData['create_time']);
                $res = $M->where(['health_uid'=>$uid,'project_id'=>$project_id])->save($saveData);
                $saveProjectId = $project_id;
                if($res) M('health_project_service')->where(['project_id'=>$project_id])->delete();   
            }
            if($res){
                $saveService = array(); 
                $serEx = $post['text_explain'];
                foreach ($serEx as $key => $val) {
                     $saveService['project_id'] = $saveProjectId;
                     $saveService['service_id'] = $key;
                     $saveService['text_explain'] = $val;
                     $saveService['tags_ids'] = $post['tags_ids_'.$key]?implode(',',$post['tags_ids_'.$key]):'';
                     $addAllService[]=$saveService;
                }
                $resService = M('health_project_service')->addAll($addAllService);
            }
            if($res&&$resService&&$resUser){
                $Model->commit();
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
            }else{
                $Model->rollback();
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
            }            
        }                        
    }
    public function addService(){
        $post = I('post.');
        $dom_id = $post['dom_id'];
        $saveData = array();
        $saveData['tags_name'] = $post['tags_name'];
        $saveData['tags_explain'] = $post['tags_explain'];
        $saveData['service_id'] = $post['service_id'];
        $rules = array(
            array('tags_name', 'require', '请填写项目名称！', 1, 'regex', 1),
            array('tags_explain', 'require', '请填写项目说明！', 1, 'regex', 1),
            array('service_id', 'require', '服务内部错误，请刷新重试！', 1, 'regex', 1)
        );
        $M = M('health_tags');
        if (!$M->validate($rules)->create($saveData)) {
            $this->ajaxReturn(['status' => 0, 'msg' => $M->getError()]);
        } else {
            $icon = $_FILES['tags_icon'];
            if($icon['size']==0) $this->ajaxReturn(['status' => 0, 'msg' => '请上传项目图标！']);
            $size = getimagesize($icon['tmp_name']);
            if($size[0]!=$size[1]) $this->ajaxReturn(['status' => 0, 'msg' => '图标尺寸错误，<br>请上传1:1比例的png格式白色透明图标！']);
            $resIcon = uploadImg($icon);
            if($resIcon){
                $saveData['tags_icon'] = $resIcon['path'].$resIcon['info']['savepath'].$resIcon['info']['savename'];
                $res = $M->add($saveData);
                if($res){
                   $saveData['tags_id'] = $res;
                   $this->ajaxReturn(['status' => 1,'dom_id'=>$dom_id,'msg' => $saveData]); 
                }
            }else{
                $this->ajaxReturn(['status' => 0, 'msg' => '图标上传失败！']);
            }
        }        
    }
    public function pushTpl(){
        $classify_id = I('post.cid');
        $this->setmealList = M('health_setmeal')->where(['classify_id'=>$classify_id])->select();
        $this->topicList = M('health_topic')->where(['classify_id'=>$classify_id])->select();
        $data = array();
        $data['answer'] = $this->fetch('tpl_answer');
        $data['setmeal'] = $this->fetch('tpl_choose_setmeal');
        $this->ajaxReturn(['status' => 1, 'msg' => $data]); 
    }
    public function addServiceTpl(){
        $this->tag = I('get.tag')?:'说明'; 
        $this->dom_id = I('get.dom_id');
        $this->sid = I('get.sid');
        $content = $this->fetch('tpl_add_service');
        $this->ajaxReturn(['status' => 1, 'msg' => $content]);
    }

    /**
     * 以下是VIP用户相关操作
     */
    public function editVipUser($action=1){
        if(IS_POST){
            $post = I('post.');
            $headImg = $_FILES['head_img'];
            if($headImg['size']==0&&empty($post['head_img'])) $this->ajaxReturn(['status' => 0, 'msg' => '请上传宽高比1:1的头像']);
            $saveData = array();
            $saveData['classify_id'] = $post['classify_id'];
            $saveData['setmeal_id'] = $post['setmeal_id'];
            $saveData['name'] = $post['name'];
            $saveData['sex'] = $post['sex'];
            $saveData['birthyear'] = $post['age'];
            $saveData['mobile'] = $post['mobile'];
            $saveData['ethnic'] = $post['ethnic'];
            $saveData['native'] = $post['native'];
            $saveData['address'] = $post['address'];
            $saveData['user_type'] = 3;
            $saveData['steward_id'] = $post['steward_id'];
            $saveData['create_time'] = time();
            $rules = array(
                array('name', 'require', '请填写用户姓名', 1, 'regex', 1),
                array('ethnic', 'require', '请填写用户民族', 1, 'regex', 1),
                array('native', 'require', '请填写用户籍贯', 1, 'regex', 1),
                array('birthyear', 'require', '请填写用户年龄', 1, 'regex', 1),
                array('mobile', 'require', '请填写联系电话', 1, 'regex', 1),
                array('address', 'require', '请填写地址', 1, 'regex', 1),
                array('classify_id', 'require', '请选择套餐类型', 1, 'regex', 1),
                array('steward_id', 'require', '请选择管家', 1, 'regex', 1),
                array('setmeal_id', 'require', '请选择套餐', 1, 'regex', 1)
            );
            $M = M('health_user');
            if (!$M->validate($rules)->create($saveData)) {
                $this->ajaxReturn(['status' => 0, 'msg' => $M->getError()]);
            } else {
                if(empty($post['record_type_id'])||empty($post['record_id'])||empty($post['record_text'])){
                    $this->ajaxReturn(['status' => 0, 'msg' => '请填写个人记录']);
                }
                $saveData['birthyear'] = yearToAge($saveData['birthyear']);
                $resHeadImg = uploadImg($headImg);
                if($resHeadImg){
                    $pathHead = $resHeadImg['path'].$resHeadImg['info']['savepath'].$resHeadImg['info']['savename'];
                }
                $saveData['head_img'] = $resHeadImg?$pathHead:$post['head_img'];                          
                $Model = M();
                $Model -> startTrans();                          
                if($post['action']==2&&!empty($post['uid'])){
                    $uid = $post['uid'];
                    // 编辑
                    $user = $M->where(['health_uid'=>$uid])->find();
                    if($user&&($user['user_type']==3||$user['user_type']==2)){
                        $res = $M->where(['health_uid'=>$uid])->save($saveData);
                        if($res) M('health_user_record')->where(['health_uid'=>$uid,'record_classify'=>0])->delete(); 
                    }
                }else{
                    // 新增
                    if(!empty($post['pid'])){
                        $userInfo = $M->where(['health_uid'=>$post['pid']])->find();
                        if(!$userInfo||$userInfo['health_pid']!=0) $this->ajaxReturn(['status' => 0, 'msg' => '成员用户无法添加新成员']);
                        $saveData['health_pid'] = $post['pid'];
                    }
                    $res = $M->data($saveData)->add();
                    $uid = $res;                     
                }
                if($res){
                    $record = array();
                    $saveRecord = array();
                    $recordIds = $post['record_id'];
                    $recordText = $post['record_text'];
                    $healthRecord = M('health_record')->getField('record_id,record_type_id');
                    foreach ($recordIds as $k => $v) {
                        if(empty($v)||empty($recordText[$k])) $this->ajaxReturn(['status' => 0, 'msg' => '请完善个人记录']);
                        $record['health_uid'] = $uid;
                        $record['record_id'] = $v;                        
                        $resType = $healthRecord[$v];
                        $record['record_type_id'] = $resType['record_type_id'];
                        $record['record_text'] = $recordText[$k];
                        $saveRecord[] = $record;
                    }
                    $resRecord = M('health_user_record')->addAll($saveRecord);
                }
                if($res&&$resRecord){
                    $Model->commit();
                    $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
                }else{
                    $Model->rollback();
                    $this->ajaxReturn(['status' => 0, 'msg' => '操作失败，原因可能如下：<br>1.未作任何修改<br>2.内部错误']);
                }                 
            }           

        }else{
            $pid = I('get.pid');
            if(!empty($pid)){
                $userInfo = M('health_user')->where(['health_uid'=>$pid])->find();
                if(!$userInfo||$userInfo['health_pid']!=0) $this->error('该用户无法添加新成员');
            }
            $classify = M('health_classify')->order('sort asc')->select();
            $this->recordData = M('health_record_type')->where(['record_classify'=>0])->select();            
            $this->classify = $classify;     
            $this->title = '添加/编辑VIP用户';
            $this->action = $action;
            $this->pid = I('get.pid');
            // 编辑操作
            if($action==2){
                $uid = I('get.uid');
                $data = M('health_user')->where(['health_uid'=>$uid])->find();
                if(!$data||($data['user_type']!=3&&$data['user_type']!=2)) $this->error('参数错误！');
                if($data['user_id']!=0){
                    $wxUser = M('users')->where(['user_id'=>$data['user_id']])->find();
                    $data['head_img'] = $wxUser['avatar'];
                }
                $steward = M('health_steward')->where(['steward_id'=>$data['steward_id']])->field('steward_id,recommend_img,name_cn,motto')->find();
                $data['steward'] = $steward;
                $this->setmealList = M('health_setmeal')->where(['classify_id'=>$data['classify_id']])->select();
                $this->records = D('HealthUserRecord')->getRecord($uid);
                $this->uid = $uid;
                $this->data = $data;
            }
            $this->recordType = $this->fetch('tpl_vip_user_record');
            $this->display();            
        }
    }

    public function detailVipUser(){
        $uid = I('get.uid');
        $M = M('health_user');
        $user = $M->alias('m')->join('left join health_setmeal as s on s.setmeal_id=m.setmeal_id')->join('left join health_steward as t on t.steward_id=m.steward_id')->where(['m.health_uid'=>$uid])->field('m.health_uid,m.health_pid,m.name,m.sex,m.head_img,m.birthyear,m.mobile,m.ethnic,m.native,m.address,m.setmeal_id,m.steward_id,m.classify_id,m.user_type,s.name as setmeal_name,s.icon as setmeal_icon,t.name_cn as steward_name,t.recommend_img')->find();
        if(!$user||$user['user_type']!=3) $this->error('该用户还不是VIP！');
        $steward = M('health_steward_classify')->where(['steward_id'=>$user['steward_id'],'classify_id'=>$user['classify_id']])->find();
        $user['steward_grade'] = $steward['steward_grade'];
        $pid = $user['health_pid'];
        if($pid==0){
            $createUser = $user;
            $searchId = $uid;
        }else{
            $createUser = $M->alias('m')->join('left join health_setmeal as s on s.setmeal_id=m.setmeal_id')->where(['m.health_uid'=>$pid])->field('m.health_uid,m.name,m.head_img,s.name as setmeal_name')->find();
            $searchId = $pid;
        }
        $this->member = $M->alias('m')->join('left join health_setmeal as s on s.setmeal_id=m.setmeal_id')->where(['m.health_pid'=>$searchId])->field('m.health_uid,m.name,m.sex,m.head_img,s.name as setmeal_name')->select();
        $this->user = $user;
        $this->create_user = $createUser;
        $this->records = D('HealthUserRecord')->getRecord($uid);
        $this->title = 'VIP用户详情';
        $this->uid = $uid;
        $this->display();
    }

    public function chooseSteward(){
        $classify_id = I('get.classify_id');
        $guanjia = D('health_steward_classify')->relation(true)->where(['classify_id'=>$classify_id])->select();
        if(empty($guanjia)) $this->ajaxReturn(['status' => 0, 'msg' => '暂无此类管家！']);
        $this->guanjia = $guanjia;
        $content = $this->fetch('tpl_choose_gj');
        $this->ajaxReturn(['status' => 1, 'msg' => $content]);        
    }

    public function resSteward($id){
        $res = M('health_steward')->field('steward_id,name_cn,recommend_img,motto')->find($id);
        if($res) $this->ajaxReturn(['status' => 1, 'msg' => $res]);
        $this->ajaxReturn(['status' => 0, 'msg' => '管家不存在！']); 
    }

    public function pushRecord(){
        $record_type_id = I('post.record_type_id');
        $record_classify = I('post.record_classify');
        $recordData = M('health_record_type')->where(['record_classify'=>$record_classify])->select();
        foreach ($recordData as $k => $v) {
           foreach ($record_type_id as $key => $val) {
               if($v['record_type_id']==$val) unset($recordData[$k]);
           }
        }
        if(empty($recordData)) $this->ajaxReturn(['status' => 0, 'msg' => '无更多选项']);   
        $this->recordData = $recordData;
        $data = $this->fetch('tpl_vip_user_record');
        $this->ajaxReturn(['status' => 1, 'msg' => $data]);         
    }

    public function resRecord(){
        $id = I('get.id');
        $record = M('health_record')->where("record_type_id='$id'")->select();
        $this->ajaxReturn($record);        
    }
    /**
     * @param    integer    $type [1:健康档案 2:日志 3:周报 4:检查检验报告 5:健康方案]
     * @return   [type]           [description]
     * @Author   Luop
     * @DateTime 2018-01-13
     * @param
     */
    public function logsVipUser($type=1){
        $uid = I('get.uid');
        $user = M('health_user')->find($uid);
        if(!$user||$user['user_type']!=3) $this->error('该用户不存在或非VIP！');        
        $list = array();
        switch ($type) {
            case '1':
                $data = M('health_archive')->where(['health_uid'=>$uid])->select();
                foreach ($data as $k=> $v) {
                    $list[$k]['id'] = $v['archive_id'];
                    $list[$k]['name'] = $v['archive_title'];
                    $list[$k]['intro'] = $v['diagnose'];
                    $list[$k]['create_time'] = $v['create_time'];
                }
                break;
            case '2':
                # code...
                break;
            case '3':
                # code...
                break;
            case '4':
                $data = M('health_report')->where(['health_uid'=>$uid])->select();
                foreach ($data as $k=> $v) {
                    $list[$k]['id'] = $v['report_id'];
                    $list[$k]['name'] = $v['report_title'];
                    $list[$k]['intro'] = $v['report_title'];
                    $list[$k]['create_time'] = $v['create_time'];
                }               
                break;
            case '5':
                $data = M('health_project')->where(['health_uid'=>$uid])->select();
                foreach ($data as $k=> $v) {
                    $list[$k]['id'] = $v['project_id'];
                    $list[$k]['name'] = $v['title'];
                    $list[$k]['intro'] = $v['analyze'];
                    $list[$k]['create_time'] = $v['create_time'];
                }
                break;                                                            
            default:
                $this->error('参数错误！');
                break;
        }
        $this->user = $user;
        $this->type = $type;
        $this->title = 'VIP用户详情-档案列表';
        $this->list = $list;
        $this->vipCard = $this->fetch('tpl_vip_user_card');
        $this->display();
    }    

    public function addReport($action=1){
        if(IS_POST){
            $post = I('post.');
            if(empty($post['report_title'])) $this->ajaxReturn(['status' => 0, 'msg' => '请填写标题！']);  
            if(empty($post['report_media'])) $this->ajaxReturn(['status' => 0, 'msg' => '请上传检验报告！']);
            $uid = $post['uid'];
            $user = M('health_user')->find($uid);
            if(!$uid||!$user||$user['user_type']!=3) $this->ajaxReturn(['status' => 0, 'msg' => '该用户不是VIP']);
            $M = M('health_report');
            $saveData = array();
            $saveData['health_uid'] = $uid;
            $saveData['report_title'] = $post['report_title'];
            $saveData['report_media'] = implode(',',$post['report_media']);
            $saveData['create_time'] = time();
            if($post['action']==2&&!empty($post['report_id'])){
                unset($saveData['create_time']);
                $res = $M->where(['report_id'=>$post['report_id']])->save($saveData);
                $msg = '编辑';
            }else{
                $res = $M->add($saveData);
                $msg = '添加';
            }
            if($res){
                $this->ajaxReturn(['status' => 1, 'msg' => $msg.'成功！']);  
            }else{
                $this->ajaxReturn(['status' => 0, 'msg' => $msg.'失败！']); 
            }
        }else{
            $uid = I('get.uid');
            $user = M('health_user')->find($uid);
            if(!$user||$user['user_type']!=3) $this->error('该用户还不是VIP！');
            $this->user = $user;
            $this->action = $action;
            $this->vipCard = $this->fetch('tpl_vip_user_card');
            if($action==2){
                // 编辑操作
                $report_id = I('get.rid');
                $data = M('health_report')->where(['health_uid'=>$uid,'report_id'=>$report_id])->find();
                $this->data = $data;
                $this->rid = $report_id;
            }
            $this->title = '添加/编辑检查检验报告';
            $this->display();
        }
    }

    public function addArchives(){
        if(IS_POST){
            $post = I('post.');
            $uid = $post['uid'];
            $user = M('health_user')->find($uid);
            if(!$user||$user['user_type']!=3) $this->ajaxReturn(['status' => 0, 'msg' => '非法提交！']);
            $saveData = array();
            $saveData['health_uid'] = $uid;
            $saveData['archive_title'] = $post['archive_title'];
            $saveData['diagnose'] = $post['diagnose'];
            $saveData['create_time'] = time();
            $rules = array(
                array('health_uid', 'require', '用户信息错误', 1, 'regex', 1),
                array('archive_title', 'require', '请填写档案标题', 1, 'regex', 1),
                array('diagnose', 'require', '请输入护理诊断结果', 1, 'regex', 1)
            );
            $M = M('health_archive');
            $Model = M();
            $Model -> startTrans();
            if (!$M->validate($rules)->create($saveData)) {
                $this->ajaxReturn(['status' => 0, 'msg' => $M->getError()]);
            } else {
                $res = $M->data($saveData)->add();       
            }
            if($res){
                $record = array();
                $saveRecord = array();
                $recordIds = $post['record_id'];
                $recordText = $post['record_text'];
                $healthRecord = M('health_record')->getField('record_id,record_type_id');
                foreach ($recordIds as $k => $v) {
                    if(empty($v)||empty($recordText[$k])) $this->ajaxReturn(['status' => 0, 'msg' => '请完善个人档案']);
                    $record['health_uid'] = $uid;
                    $record['record_id'] = $v;                        
                    $resType = $healthRecord[$v];
                    $record['record_type_id'] = $resType['record_type_id'];
                    $record['archive_id'] = $res;
                    $record['record_text'] = $recordText[$k];
                    $record['record_classify'] = 1;
                    $saveRecord[] = $record;
                }
                $resRecord = M('health_user_record')->addAll($saveRecord);
                if($post['isOutp']==1){
                    // 添加门诊记录
                    $saveOutp = array();
                    $saveOutp['archive_id'] = $res;
                    $saveOutp['health_uid'] = $uid;
                    $saveOutp['outp_time'] = $post['outp_time'];
                    $saveOutp['outp_result'] = $post['outp_result'];
                    $saveOutp['create_time'] = time();
                    $rulesOutp = array(
                        array('health_uid', 'require', '用户信息错误', 1, 'regex', 1),
                        array('outp_time', 'require', '请填写门诊时间', 1, 'regex', 1),
                        array('outp_result', 'require', '请填写门诊结果', 1, 'regex', 1)
                    );
                    if (!M('health_outpatient')->validate($rulesOutp)->create($saveOutp)) {
                        $this->ajaxReturn(['status' => 0, 'msg' => M('health_outpatient')->getError()]);
                    } else {
                        $resOutp = M('health_outpatient')->data($saveOutp)->add();
                        if($resOutp){
                            $tem = array();
                            $saveOutpDetail = array();
                            $outpOption = $post['option'];
                            $outpText = $post['text'];
                            foreach ($outpOption as $key => $val) {
                                if(empty($val)||empty($outpText[$key])) $this->ajaxReturn(['status' => 0, 'msg' => '请完善门诊记录']);
                                $tem['outpatient_id'] = $resOutp;
                                $tem['option'] = $val;
                                $tem['text'] = $outpText[$key];                            
                                $saveOutpDetail[] = $tem;
                            }
                            $resOutpDetail = M('health_outpatient_option')->addAll($saveOutpDetail);
                        }    
                    }                 
                }
                if($post['isHosp']==1){
                    // 添加住院记录
                    $saveHosp = array();
                    $saveHosp['archive_id'] = $res;
                    $saveHosp['health_uid'] = $uid;
                    $saveHosp['in_time'] = $post['in_time'];
                    $saveHosp['out_time'] = $post['out_time'];
                    $saveHosp['in_diagnose'] = $post['in_diagnose'];
                    $saveHosp['in_situation'] = $post['in_situation'];
                    $saveHosp['report_media'] = implode(',',$post['report_media']);
                    $saveHosp['out_diagnose'] = $post['out_diagnose'];
                    $saveHosp['out_situation'] = $post['out_situation'];
                    $saveHosp['out_advice'] = $post['out_advice'];
                    $saveHosp['create_time'] = time();
                    $rulesHosp = array(
                        array('health_uid', 'require', '用户信息错误', 1, 'regex', 1),
                        array('in_time', 'require', '请填写住院时间', 1, 'regex', 1),
                        array('out_time', 'require', '请填写出院时间', 1, 'regex', 1),
                        array('in_situation', 'require', '请填写入院情况', 1, 'regex', 1),
                        array('out_situation', 'require', '请填写出院情况', 1, 'regex', 1)
                    );
                    if (!M('health_hosp')->validate($rulesHosp)->create($saveHosp)) {
                        $this->ajaxReturn(['status' => 0, 'msg' => M('health_hosp')->getError()]);
                    } else {
                        $resHosp = M('health_hosp')->data($saveHosp)->add();       
                    }                
                }
                $isSave = false;
                if($post['isOutp']==1&&$post['isHosp']!=1){
                    if($res&&$resRecord&&$resOutp&&$resOutpDetail) $isSave = true;
                }elseif($post['isOutp']!=1&&$post['isHosp']==1){
                    if($res&&$resRecord&&$resHosp) $isSave = true;
                }elseif($post['isOutp']==1&&$post['isHosp']==1){
                    if($res&&$resRecord&&$resOutp&&$resOutpDetail&&$resHosp) $isSave = true;
                }else{
                    if($res&&$resRecord) $isSave = true;
                }
                if($isSave){
                    $Model->commit();
                    $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);                    
                }else{
                    $Model->rollback();
                    $this->ajaxReturn(['status' => 0, 'msg' => '操作失败，原因可能如下：<br>1.未作任何修改<br>2.填写不完整']);                    
                }            
            }                        
        }else{
            $uid = I('get.uid');
            $user = M('health_user')->find($uid);
            if(!$user||$user['user_type']!=3) $this->error('该用户还不是VIP！');
            $this->user = $user;
            $this->vipCard = $this->fetch('tpl_vip_user_card');
            $this->recordData = M('health_record_type')->where(['record_classify'=>1])->select();
            $this->recordType = $this->fetch('tpl_vip_user_record');            
            $this->title = '添加健康档案';           
            $this->display();
        }
    }

    public function addOutpHtml(){
        $data = $this->fetch('tpl_add_outp');
        $this->ajaxReturn(['status' => 1, 'msg' => $data]); 
    }

    public function addHospHtml(){
        $data = $this->fetch('tpl_add_hosp');
        $this->ajaxReturn(['status' => 1, 'msg' => $data]); 
    }

    public function detailArchive(){
        $get = I('get.');
        $uid = $get['uid'];
        $user = M('health_user')->find($uid);
        if(!$user||$user['user_type']!=3) $this->error('该用户还不是VIP！');        
        $archiveId = $get['archive_id'];
        $data = D('HealthArchive')->getArchive($archiveId);
        if(!$data) $this->error('该档案不存在！');
        $this->user = $user;
        $this->vipCard = $this->fetch('tpl_vip_user_card');
        $this->data = $data;
        $this->title = '健康档案详情';
        $this->display();
    }

    // 删除一条门诊记录
    public function delOutp(){
        $id = I('post.id');
        $res = M('health_outpatient')->where(['outpatient_id'=>$id])->delete();
        if($res){
            $resOption = M('health_outpatient_option')->where(['outpatient_id'=>$id])->delete();
        }
        if($resOption) $this->ajaxReturn(['status' => 1, 'msg' => '删除成功!']);
        $this->ajaxReturn(['status' => 0, 'msg' => '删除失败!']);   
    }

    //弹出添加记录弹窗
    public function openPopup(){
        $id = I('get.id');
        $uid = I('get.uid');
        $action = I('get.action');
        $this->noClole = 1;
        $this->archive_id = $id;
        $this->health_uid = $uid;
        if(I('get.action')==1)$tpl = $this->fetch('tpl_add_outp');
        if(I('get.action')==2)$tpl = $this->fetch('tpl_add_hosp');
        $this->ajaxReturn(['tpl'=>$tpl,'action'=>$action]);
    }

    //添加住院记录
    public function addHosp(){
        $id = I('post.archive_id');
        $M = M('health_hosp');
        $post = I('post.');
        $hospData = array();
        $hospData['archive_id'] = $post['archive_id'];
        $hospData['health_uid'] = $post['health_uid'];
        $hospData['in_time'] = $post['in_time'];
        $hospData['out_time'] = $post['out_time'];
        $hospData['in_diagnose'] = $post['in_diagnose'];
        $hospData['in_situation'] = $post['in_situation'];
        $hospData['report_media'] = implode(',',$post['report_media']);
        $hospData['out_diagnose'] = $post['out_diagnose'];
        $hospData['out_situation'] = $post['out_situation'];
        $hospData['out_advice'] = $post['out_advice'];
        $hospData['create_time'] = time();
        $rulesHosp = array(
            array('health_uid', 'require', '用户信息错误', 1, 'regex', 1),
            array('in_time', 'require', '请填写住院时间', 1, 'regex', 1),
            array('out_time', 'require', '请填写出院时间', 1, 'regex', 1),
            array('in_situation', 'require', '请填写入院情况', 1, 'regex', 1),
            array('out_situation', 'require', '请填写出院情况', 1, 'regex', 1)
        );
        if (!$M->validate($rulesHosp)->create($hospData)) {
            $this->ajaxReturn(['status' => 0, 'msg' => $M->getError()]);
        } else {
            $resHosp = M('health_hosp')->data($hospData)->add();
            if($resHosp)$this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
            $this->ajaxReturn(['status' => 0, 'msg' => '添加数据失败']);
        }
    }
    //添加一条门诊记录
    public function addOutp(){
        $id = I('post.archive_id');
        $M = M('health_outpatient');
        $post = I('post.');
        $outpatient = array();
        $outpatient['archive_id'] = $post['archive_id'];
        $outpatient['health_uid'] = $post['health_uid'];
        $outpatient['outp_time'] = $post['outp_time'];
        $outpatient['outp_result'] = $post['outp_result'];
        $outpatient['create_time'] = time();
        $rulesOutp = array(
            array('health_uid', 'require', '用户信息错误', 1, 'regex', 1),
            array('outp_time', 'require', '请填写门诊时间', 1, 'regex', 1),
            array('outp_result', 'require', '请填写门诊结果', 1, 'regex', 1)
        );
        if (!M('health_outpatient')->validate($rulesOutp)->create($outpatient)) {
            $this->ajaxReturn(['status' => 0, 'msg' => $M->getError()]);
        }
        $Model = M();
        $Model -> startTrans();
        $resOutp = M('Health_outpatient')->data($outpatient)->add();
        if($resOutp){
            $tem = array();
            $saveOutpDetail = array();
            $outpOption = $post['option'];
            $outpText = $post['text'];
            foreach ($outpOption as $key => $val) {
                if(empty($val)||empty($outpText[$key])) $this->ajaxReturn(['status' => 0, 'msg' => '请完善门诊记录']);
                $tem['outpatient_id'] = $resOutp;
                $tem['option'] = $val;
                $tem['text'] = $outpText[$key];
                $saveOutpDetail[] = $tem;
            }
            $resOutpDetail= M('health_outpatient_option')->addAll($saveOutpDetail);
        }
        if($resOutp&&$resOutpDetail){
            $Model->commit();
            $this->ajaxReturn(['status' => 1, 'msg' => '门诊记录添加成功']);
        }else{
            $Model->rollback();
            $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
        }
    }



    // 删除一条住院记录
    public function delHosp(){
        $id = I('post.id');
        $res = M('health_hosp')->where(['hosp_id'=>$id])->delete();
        if($res) $this->ajaxReturn(['status' => 1, 'msg' => '删除成功!']);
        $this->ajaxReturn(['status' => 0, 'msg' => '删除失败!']);                     
    }

    // 日志
    public function addJournal(){
        if(IS_POST){
            $post = I('post.data');
            $saveData['health_uid'] = I('get.uid');
            $saveData['log_date'] = date('Y-m-d');
            $saveData['create_time'] = time();
            $logId = M('Health_logs')->data($saveData)->add();
            if(!$logId) return;
            $option = M('Health_journal_option');
            foreach ($post as $key=>$val) {
                $timeName = M('Health_journal_times')->where('time_id = %d',$post[$key]['time_id'])->getField('time_name');
                $logDetail= array('log_id'=>$logId,'time_name'=>$timeName);
                $res = M('Health_logdetail')->data($logDetail)->add();
                if(!$res)return;
                foreach ($val as $k => $v) {
                    if (strpos($k, 'check_') !== false) {
                        foreach ($v as $kk => $vv) {
                            $logTitle = $option->where('option_id = %d',$vv)->getField('name');
                            $optionList[] = array('detail_id' => $post[$key]['time_id'], 'option_id' => $vv, 'option_pid' => substr($k, 6), 'log_text' => '', 'log_title' => $logTitle);
                        }
                    }
                    if (strpos($k, 'log_') !== false){
                        $arr = explode('_', $k);
                        $logTitle = $option->where('option_id = %d',$arr[2])->getField('name');
                        $optionList[] = array('detail_id' => $post[$key]['time_id'], 'option_id' =>$arr[2] , 'option_pid' => $arr[1], 'log_text' => $v, 'log_title' => $logTitle);
                    }
                }
            }
            $data = M('Health_log_option')->addAll($optionList);
            if($data)$this->ajaxReturn(['status'=>1,'msg'=>'添加成功了']);
        }else{
            $uid = I('get.uid');
            $M = M('health_user');
            $user = $M->alias('m')->join('left join health_setmeal as s on s.setmeal_id=m.setmeal_id')->join('left join health_steward as t on t.steward_id=m.steward_id')->where(['m.health_uid'=>$uid])->field('m.health_uid,m.health_pid,m.name,m.sex,m.head_img,m.birthyear,m.mobile,m.ethnic,m.native,m.address,m.setmeal_id,m.steward_id,m.classify_id,m.user_type,s.name as setmeal_name,s.icon as setmeal_icon,t.name_cn as steward_name,t.recommend_img')->find();
            if(!$user||$user['user_type']!=3) $this->error('该用户还不是VIP！');
            $steward = M('health_steward_classify')->where(['steward_id'=>$user['steward_id'],'classify_id'=>$user['classify_id']])->find();
            $user['steward_grade'] = $steward['steward_grade'];
            $this->user = $user;
            $this->vipCard = $this->fetch('tpl_vip_user_card');
            $this->timesData = M('health_journal_times')->select();
            $this->option = M('health_journal_option')->where(['level'=>1])->select();
            $this->journalOption = $this->fetch('tpl_journal_option');
            $this->tplJournal = $this->fetch('tpl_journal');      
            $this->title = '添加日志';           
            $this->display();            
        }
    }

    public function addJournalHtml(){
        $time_id = I('post.time_id');
        $timesData = M('health_journal_times')->where(['time_id'=>array('not in',$time_id)])->select();
        if(empty($timesData)) $this->ajaxReturn(['status' => 0, 'msg' => '无更多选项']);
        $this->timesData = $timesData;
        $this->option = M('health_journal_option')->where(['level'=>1])->select();  
        $this->journalOption = $this->fetch('tpl_journal_option');
        $data = $this->fetch('tpl_journal');
        $this->ajaxReturn(['status' => 1, 'msg' => $data]);         
    }

    public function resJournalOption($id){
        $M = M('health_journal_option');
        $option = $M->where("pid='$id'")->select();
        $tmp = $M->find($id);
        $type = $tmp['is_fill']==1?2:1;
        $this->ajaxReturn(['status' => $type, 'msg' => $option]);
    }

}