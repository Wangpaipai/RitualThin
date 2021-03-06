<?php
namespace app\api\controller;


use think\Db;
use think\Loader;
use think\Request;

class Ritualthin extends Common
{
	/**
	 * 小程序首页数据
	 * Created by：Mp_Lxj
	 * @date 2018/11/5 14:41
	 * @return array|\Illuminate\Http\JsonResponse|void
	 */
	public function index()
	{
		$Site = new Site();
		$Giftgive = new Giftgive();
		$Giftreceive = new Giftreceive();
		$Remind = new Remind();

		$param = Request::instance()->param();
		$data = [];

		Db::startTrans();
		try{
			$data['banner'] = $Site->getBannerList();//获取幻灯片
			$data['gift_give_total'] = $Giftgive->getTotal($param);//送礼总数统计
			$data['gift_receive_total'] = $Giftreceive->getTotal($param);//收礼总数统计
			$data['remind'] = $Remind->getNotExpiredRemind($param);//获取提醒列表
			$ritual_thin = $this->getRitualThin($param);//获取礼薄
			$data['ritualthin'] = $this->titualThinTotal($ritual_thin);//礼薄统计后结果
			Db::commit();
			return trueAjax('',$data);
		}catch(\Exception $e){
			Db::rollback();
			return falseAjax($e->getMessage());
		}
	}

	/**
	 * 礼薄统计
	 * Created by：Mp_Lxj
	 * @date 2018/11/5 14:37
	 * @param $data
	 * @return mixed
	 */
	public function titualThinTotal($data)
	{
		foreach($data as &$value){
			$map['rt_id'] = $value['id'];
			$value['count'] = Loader::model('GiftReceive')->getGiftReceiveCount($map);
			$value['money'] = Loader::model('GiftReceive')->getGiftReceiveSum($map);
		}
		return $data;
	}

	/**
	 * 获取礼薄列表
	 * Created by：Mp_Lxj
	 * @date 2018/11/5 14:35
	 * @param $data
	 * @return mixed
	 */
	public function getRitualThin($data)
	{
		$map['uid'] = $data['uid'];
		$field = [
			'id','name','start_time'
		];
		$result = Loader::model('RitualThin')->getRitualThinList($map,$field);
		return $this->dateFormat($result,'start_time','.');
	}

	/**
	 * 时间格式化
	 * Created by：Mp_Lxj
	 * @date 2018/11/5 14:38
	 * @param $data
	 * @param $field
	 * @return mixed
	 */
	public function dateFormat($data,$field = 'start_time',$connect = '-')
	{
		foreach($data as &$value){
			$value[$field] = date('Y'. $connect .'m'. $connect .'d',$value[$field]);
			if(isset($value['money'])){
				$value['money'] = intval($value['money']);
			}
		}
		return $data;
	}

	/**
	 * 礼薄翻页
	 * Created by：Mp_Lxj
	 * @date 2018/11/5 14:44
	 * @return array|\Illuminate\Http\JsonResponse|void
	 */
	public function getRitualThinList()
	{
		$param = Request::instance()->param();
		$map['uid'] = $param['uid'];
		$first_page = ($param['page'] - 1) * 10;
		$limit = $first_page . ',10';
		$field = [
			'id','name','start_time'
		];
		$result = Loader::model('RitualThin')->getRitualThinList($map,$field,$limit);
		return trueAjax('',$this->dateFormat($result));
	}

	/**
	 * 礼薄详情---收礼记录
	 * Created by：Mp_Lxj
	 * @date 2018/11/5 16:46
	 * @return array|\Illuminate\Http\JsonResponse|void
	 */
	public function getGiftReceiveDetail()
	{
		$Giftreceive = new Giftreceive();
		$param = Request::instance()->param();
		$gift_receive = $Giftreceive->getGiftReceive($param);
		$res = asciiGroup($this->dateFormat($gift_receive,'give_time','.'));
		$map['id'] = $param['rt_id'];
		$map['uid'] = $param['uid'];
		$field = ['id','name','start_time'];
		$rt = Loader::model('RitualThin')->rtDetail($map,$field);
		$rt['start_time'] = date('Y-m-d',$rt['start_time']);
		unset($map['id']);
		$map['rt_id'] = $param['rt_id'];
		$money = intval(Loader::model('GiftReceive')->getGiftReceiveSum($map));
		$count = Loader::model('GiftReceive')->getGiftReceiveCount($map);
		$index = 0;
		$data = [];
		foreach($res as $k=>$v){
			$arr = [];
			$arr['englis_id'] = $index;
			$arr['ENG'] = $k;
			$arr['data'] = $v;
			$data[] = $arr;
			$index++;
		}
		$result['rt'] = $rt;
		$result['money'] = $money;
		$result['count'] = $count;
		$result['receive'] = $data;
		return trueAjax('',$result);
	}

	/**
	 * 搜索
	 * created by:Mp_Lxj
	 * @date:2018/11/5 20:15
	 * @return array
	 */
	public function search()
	{
		$Giftgive = new Giftgive();
		$Giftreceive = new Giftreceive();
		$param = Request::instance()->param();
		//命名写反了  意外意外
		$data['gift_give'] = $Giftreceive->searchReceive($param);//收礼
		$data['gift_receive'] = $Giftgive->getGiftgiveList($param);//送礼

		foreach($data as &$value){
			$value = $this->dateFormat($value,'give_time','.');
		}
		return trueAjax('',$data);
	}

	/**
	 * 更新礼薄信息
	 * created by:Mp_Lxj
	 * @date:2018/11/5 21:00
	 * @return array
	 */
	public function rtUpdate()
	{
		$param = Request::instance()->param();
		$map['id'] = $param['id'];
		$map['uid'] = $param['uid'];
		Db::startTrans();
		try{
			$param['start_time'] = strtotime($param['start_time']);
			Loader::model('RitualThin')->rtUpdate($map,$param);
			Db::commit();
			return trueAjax('更新成功');
		}catch(\Exception $e){
			Db::rollback();
			return falseAjax($e->getMessage());
		}
	}

	/**
	 * 删除礼薄所有信息
	 * created by:Mp_Lxj
	 * @date:2018/11/5 21:05
	 * @return array
	 */
	public function rtRemove()
	{
		$param = Request::instance()->param();
		$map['id'] = $param['id'];
		$map['uid'] = $param['uid'];
		Db::startTrans();
		try{
			Loader::model('RitualThin')->rtRemove($map,$param);//删除礼薄信息
			$receiveMap['rt_id'] = $param['id'];
			$receiveMap['uid'] = $param['uid'];
			Loader::model('GiftReceive')->GiftReceiveDel($receiveMap);//删除礼薄下收礼记录
			Db::commit();
			return trueAjax('删除成功');
		}catch(\Exception $e){
			Db::rollback();
			return falseAjax($e->getMessage());
		}
	}

	/**
	 * 新增礼薄
	 * Created by：Mp_Lxj
	 * @date 2018/11/6 15:22
	 * @return array|\Illuminate\Http\JsonResponse|void
	 */
	public function rtInsert()
	{
		$param = Request::instance()->param();
		$param['time'] = time();
		$param['start_time'] = strtotime($param['start_time']);
		Db::startTrans();
		try{
			$id = Loader::model('RitualThin')->rtCreate($param);//写入礼薄信息
			Db::commit();
			return trueAjax('新增成功',$id);
		}catch(\Exception $e){
			Db::rollback();
			return falseAjax($e->getMessage());
		}
	}
}