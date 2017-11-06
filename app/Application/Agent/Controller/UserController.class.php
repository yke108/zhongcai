<?php
namespace Agent\Controller;
class UserController extends BaseController {

    public function userList() {
        $user_name = I('get.user_name','','trim');
        $user = M('user');
        $where = ['invite_code'=> $this->agUserInfo['invite_code']];
        if (!empty($user_name)) {
            $where['_complex'] = ['user_name'=> $user_name, 'nickname'=>$user_name,'_logic'=>'or'];
        }
        $count = $user->where($where)->count();
        $pageInfo = setAppPage($count);
        $list = $user->where($where)->limit($pageInfo['limit'])->field("user_id,user_name,nickname,balance,add_time")->select();
        foreach ($list as $key => $value) {
            $list[$key]['user_name'] = $this->hideUserName($value['user_name']);
        }
        $this->assign('user_name', $user_name);
        $this->assign('list', $list);
    	$this->assign('pageInfo', $pageInfo);
    	$this->display();
    }
}