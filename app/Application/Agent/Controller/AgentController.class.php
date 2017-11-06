<?php
namespace Agent\Controller;
class AgentController extends BaseController {
    private $rateEnum = [2,1,0.8,0.6,0.4,0.35,0.3,0.25,0.2,0.15,0.1,0.05];

    public function agentList() {
        $admin_user = M('admin_user');
        $user_name = I('get.user_name','','htmlspecialchars,trim');
        // 查询条件
        $where = ['pid'=> $this->agUserInfo['user_id']];
        if (!empty($user_name)) {
            $where['_complex'] = ['user_name'=> $user_name, 'nickname'=>$user_name,'_logic'=>'or'];
        }
        $count = $admin_user->where($where)->count();
        $pageInfo = setAppPage($count);
        $list = $admin_user->where($where)->limit($pageInfo['limit'])->field("user_id,user_name,nickname,balance,rate,add_time")->select();
        $draw_cash = M('draw_cash');
        foreach ($list as $key => $value) {
            $apply_cash = $draw_cash->where(['user_id'=> $value['user_id'], 'type'=> 2, 'sync'=> ['neq',2]])->sum('apply_cash');
            $list[$key]['apply_cash'] = !empty($apply_cash) ? $apply_cash : "0.00";
            $list[$key]['user_name'] = $this->hideUserName($value['user_name']);
        }
        $canadd = $this->agUserInfo['rate'] <= $this->rateEnum[count($this->rateEnum)-1] ? 0 : 1;
        $this->assign('canadd', $canadd);
        $this->assign('list', $list);
    	$this->assign('pageInfo', $pageInfo);
    	$this->display();
    }

    public function agentInfo () {
        $user_id = I('user_id',0,'intval');
        $admin_user = M('admin_user');
        $adminInfo = $admin_user->where(['user_id'=> $user_id,'pid'=> $this->agUserInfo['user_id']])->find();
        if (empty($adminInfo)) {
            $this->error("直属代理不存在");
        }
        $apply_cash = M('draw_cash')->where(['user_id'=> $user_id, 'type'=> 2, 'sync'=> ['neq',2]])->sum('apply_cash');
        $adminInfo['apply_cash'] = !empty($apply_cash) ? $apply_cash : "0.00";
        $adminInfo['user_count'] = M('user')->where(['invite_code'=>$adminInfo['invite_code']])->count();
        $this->assign('adminInfo', $adminInfo);
        $this->display();
    }

    public function addAgent() {
        if (IS_POST) {
            $user_name = I('post.user_name');
            $nickname = I('post.nickname');
            $password = I('post.password');
            $rate = I('post.rate', 0, 'floatval');
            if (empty($user_name) || empty($password) || empty($rate) || empty($nickname)) {
                $this->ajaxOutput("参数不完整");
            }
            if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $user_name)) {
                $this->ajaxOutput("账号必须是6-15位英文字母或数字");
            }
            if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $password)) {
                $this->ajaxOutput("密码必须是6-15位英文字母或数字");
            }
            if (!in_array($rate, $this->rateEnum)) {
                $this->ajaxOutput("返点不合法");
            }
            if ($this->agUserInfo['rate'] <= $rate) {
                $this->ajaxOutput("返点不能高于当前帐号");
            }
            if ($this->agUserInfo['pid'] != 0 && $this->agUserInfo['agent_count'] <= 0) {
                $this->ajaxOutput("没有代理名额");
            }
            $admin_user = M('admin_user');
            if ($admin_user->where(['user_name'=> $user_name])->count()) {
                $this->ajaxOutput("该代理帐号已存在，请重新输入");
            }
            if ($admin_user->where(['nickname'=> $nickname])->count()) {
                $this->ajaxOutput("该帐号昵称已存在，请重新输入");
            }
            // 添加代理
            $invite_code = getInviteCode();
            $admin_user->add([
                'user_name'=> $user_name,
                'nickname'=> $nickname,
                'password'=> md5($password),
                'pid'=> $this->agUserInfo['user_id'],
                'rate'=> $rate,
                'invite_code'=> $invite_code,
                'add_time'=> time(),
            ]);
            // 减少代理名额
            if ($this->agUserInfo['pid'] != 0) {
                $admin_user->where(['user_id'=> $this->agUserInfo['user_id']])->setDec('agent_count',1);
            }
            $this->ajaxOutput("添加成功",1,U('Agent/agentList'));
        } else {
            $rateList = [];
            foreach ($this->rateEnum as $value) {
                if ($value < $this->agUserInfo['rate']) {
                    $rateList[] = bcadd($value, 0, 2);
                }
            }
            $this->assign('max_rate', isset($rateList[0]) ? $rateList[0] : 0);
            $this->assign('rateList', $rateList);
            $this->display();
        }
    }

    public function editAgent() {
        if (IS_POST) {
            $user_id = I('post.user_id', 0, 'intval');
            $nickname = I('post.nickname','','htmlspecialchars,trim');
            $password = I('post.password','','trim');
            $re_password = I('post.re_password','','trim');
            $agent_count = I('post.agent_count', 0,'intval');
            if ($user_id < 1 || $agent_count < 0) {
                $this->ajaxOutput('参数错误');
            }
            $saveData = [];
            if (!empty($password)) {
                if (!preg_match('/^[A-Za-z0-9]{6,16}$/', $password)) {
                    $this->ajaxOutput('请输入6至16位英文或数字作为密码');
                }
                if ($password != $re_password) {
                    $this->ajaxOutput('两个密码不一致');
                }
                $saveData['password'] = md5($password);
            }
            $admin_user = M('admin_user');
            $adminInfo = $admin_user->where(['user_id'=> $user_id, 'pid'=> $this->agUserInfo['user_id']])->find();
            if (empty($adminInfo)) {
                $this->ajaxOutput('代理不存在');
            }
            $dc = $agent_count - $adminInfo['agent_count'];
            if ($dc !=0 ) {
                if ($this->agUserInfo['agent_count'] < $dc && $this->agUserInfo['pid'] != 0) {
                    $this->ajaxOutput('代理名额不够');
                }
                $saveData['agent_count'] = $agent_count;
                // 改变自身代理名额
                if ($this->agUserInfo['pid'] != 0) {
                    $admin_user->where(['user_id'=> $this->agUserInfo['user_id']])->setDec('agent_count', $dc);
                }
            }  
            if (!empty($nickname)) {
                if ($admin_user->where(['nickname'=>$nickname,'user_id'=>['neq',$this->agUserInfo['user_id']]])->count()) {
                    $this->ajaxOutput('别名已存在');
                }
                $saveData['nickname'] = $nickname;
            }
            $admin_user->where(['user_id'=> $user_id])->save($saveData);
            $this->ajaxOutput('修改成功', 1, U('Agent/agentInfo', ['user_id'=>$user_id]));
        }
    }

    public function earnings() {
        // 查询
        $admin_id = I('get.user_id', 0, 'intval');
        $bet_log = M('bet_log');
        $admin_income = M('admin_income');
        $user = M('user');
        $admin_user = M('admin_user');
        $is_exist = $admin_user->where(['pid'=> $this->agUserInfo['user_id'],'user_id'=> $admin_id])->count();
        if (!$is_exist) {
            $this->error('代理不存在');
        }
        $where = ['admin_id'=> $admin_id];
        $count = $admin_income->where($where)->count();
        $pageInfo = setAppPage($count);
        $incomeList = $admin_income->where($where)->order('id desc')->field('user_id,bet_id,win_balance,commission,issue,add_time')->limit($pageInfo['limit'])->select();
        $site = D('Site');
        $game = D('Game');
        $lotteryNameList = [1=>'赛车',2=>'时时彩',3=>'飞艇'];
        $tempGame =[];
        $tempSite =[];
        $tempUser =[];
        $list = [];
        foreach ($incomeList as $key => $value) {
            if (isset($tempUser[$value['user_id']])) {
                $userInfo = $tempUser[$value['user_id']];
            } else {
                $userInfo = $user->where(['user_id'=> $value['user_id']])->field('user_name,nickname')->find();
                $tempUser[$value['user_id']] = $userInfo;
            }
            $betInfo = $bet_log->where(['id'=> $value['bet_id']])->field('lottery_id,room_id,bet_detail')->find();
            if (isset($tempSite[$betInfo['room_id']])) {
                $siteInfo = $tempSite[$betInfo['room_id']];
            } else {
                $siteInfo = $site->getSiteInfo($betInfo['room_id']);
                $tempSite[$betInfo['room_id']] = $siteInfo;
            }
            if (isset($tempGame[$siteInfo['game_id']])) {
                $gameInfo = $tempGame[$siteInfo['game_id']];
            } else {
                $gameInfo = $game->getGameInfo($siteInfo['game_id']);
                $tempGame[$siteInfo['game_id']] = $gameInfo;
            }
            if (!empty($betInfo)) {
                $bet_detail = json_decode($betInfo['bet_detail'], true);
                foreach ($bet_detail as $k => $v) {
                    $list[] = [
                        'user_name'=> $this->hideUserName($userInfo['user_name']),
                        'nickname'=> $userInfo['nickname'],
                        'title'=> $lotteryNameList[$betInfo['lottery_id']].' '.$value['issue'].' '.$gameInfo['game_name'].'-'.$siteInfo['site_name'],
                        'zone'=> $v['zone'],
                        'balance'=> $v['balance'],
                        'win_balance'=> $v['win_balance'],
                        'rate'=> bcmul(bcdiv($value['commission'], $value['win_balance'], 4), 100, 2),
                        'commission'=> !empty($v['commission']) ? $v['commission'] : '0.00',
                        'add_time'=> $value['add_time'],
                    ];
                }
            }
        }   
        $this->assign('user_id', $admin_id);
        $this->assign('list', $list);
        $this->assign('pageInfo', $pageInfo);
        $this->display();
    }
}