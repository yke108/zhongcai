<?php
namespace Common\Model;
use Think\Model;
use Lib\Enum\CacheEnum;

class HostModel extends Model {
	// 获取庄家列表
	public function getHostList($room_id) {
		$hostList = M('host')->join(" as h left join zc_user as u on h.user_id = u.user_id")->where(['h.room_id'=> $room_id, 'is_delete'=> 0])->field('h.user_id,h.status,h.host_zone,h.host_balance,u.nickname')->order('h.status desc,h.host_balance desc,h.id asc')->select();
		return $hostList;
	}

	// 获取庄家上庄信息
	public function getHostInfo($room_id) {
		$hostList = $this->getHostList($room_id);
		$hostInfo = ['host_button'=> 1, 'current_host'=> null, 'waitHostList'=> []];
    	foreach ($hostList as $key => $value) {
    		$value['key'] = $key + 1;
    		if ($value['status'] > 0) {
    			$hostInfo['current_host'] = $value;
    		}
    		$hostInfo['waitHostList'][] = $value;
    	}
		return $hostInfo;
	}

	// 获取庄家至少上庄金额
	public function getLessHostBalance($room_id) {
		$siteInfo = D('Site')->getSiteInfo($room_id);
		return $siteInfo['less_host_banlance'];
	}

	// 获取庄家最大上庄金额
	public function getMaxHostBalance($room_id) {
		$siteInfo = D('Site')->getSiteInfo($room_id);
		return $siteInfo['max_host_banlance'];
	}

	// 庄家下庄后换新庄
	public function changeHost($room_id) {
		$id = M('host')->where(['room_id'=> $room_id, 'is_delete'=> 0])->order('status desc,host_balance desc,id asc')->getField('id');
		if (!empty($id)) {
			M('Host')->where(['id'=> $id])->save(['status'=> 1]);
		}
		return true;
	}

	// 庄家继续连任
	public function continueHost($room_id, $host_balance) {
		$siteInfo = D('Site')->getSiteInfo($room_id);
		$gameInfo = D('Game')->getGameInfo($siteInfo['game_id']);
		$host_zone = 0;
		if ($gameInfo['must_host'] == 0) {
			$host_zone = rand(1, $gameInfo['zone_count']);
		}
		M('Host')->where(['room_id'=> $room_id, 'status'=> ['gt', 0], 'is_delete'=> 0])->save(['host_balance'=> $host_balance, 'host_zone'=> $host_zone]);
		return true;
	}
}