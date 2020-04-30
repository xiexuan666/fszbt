<?php
namespace app\ebapi\controller;

use app\core\model\routine\RoutineCode;
use app\core\util\SystemConfigService;
use app\ebapi\model\store\StoreProduct;
use app\ebapi\model\store\StoreRaise;
use app\ebapi\model\store\StoreOrder;
use app\ebapi\model\store\StoreRaisedata;
use app\ebapi\model\store\StoreRaiseAttr;
use app\ebapi\model\store\StoreProductRelation;
use app\ebapi\model\store\StoreProductReply;
use app\ebapi\model\user\WechatUser;
use app\core\util\GroupDataService;
use service\JsonService;
use service\UtilService;


/**
 * TODO 小程序众筹产品和众筹其他api接口
 * Class RaisedataApi
 * @package app\ebapi\controller
 */
class RaiseApi extends AuthController
{
    /**
     * TODO 获取众筹列表
     */
    public function get_raise_list(){
        $data = UtilService::postMore([['offset',0],['limit',20]]);
        $store_raise = StoreRaise::getAll($data['offset'],$data['limit']);
        return JsonService::successful($store_raise);
    }

    /**
     * TODO 获取众筹列表顶部图
     */
    public function get_raise_list_banner(){
        return JsonService::successful();
    }

    /**
     * TODO 获取众筹产品详情
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function raise_detail(){

        list($id) = UtilService::postMore([['id',0]],null,true);
        if(!$id) return JsonService::fail('众筹不存在或已下架');
        $raiseOne = StoreRaise::getRaiseOne($id);
        $product = StoreProduct::where('id',$raiseOne['product_id'])->find();

        if(!$raiseOne) return JsonService::fail('众筹不存在或已下架');
        $raiseOne['images'] = json_decode($raiseOne['images'],true);
        //$raiseOne['userCollect'] = StoreProductRelation::isProductRelation($id,$this->userInfo['uid'],'collect','raise_product');

        list($pindAll)= StoreRaisedata::getRaisedataAll($id,true);//众筹列表
        list($productAttr,$productValue) = StoreRaiseAttr::getRaiseAttrDetail($id);

        $firstOne = array();
        $lastOne = array();
        $valueData = array();
        $current = array();
        $locationBg = 0;
        $locationCu = 0;
        $Orso = 'left';
        $bcadd = 1;

        if(count($productValue)){
            $sort = array(
                'direction' => 'SORT_ASC',  //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'stock',     //排序字段
            );
            $arrSort = array();
            $i = 0;
            foreach($productValue AS $uniqid => $row){
                foreach($row AS $key=>$value){
                    $arrSort[$key][$uniqid] = $value;
                }
            }

            if($sort['direction']){
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $productValue);
            }

            foreach($productValue as $key => $value){
                $valueData[$i++] = $value;
            }

            $count = bcsub(count($valueData),1);   //最后一个数组
            $countTwo = bcsub(count($valueData),2) > 0 ? bcsub(count($valueData),2) : $count;//倒数第二个数组
            $firstOne = $valueData[0];
            $lastOne = $valueData[$count];

            foreach($valueData as $key => $value){
                if($raiseOne['sales'] < $valueData[0]['stock']){
                    $current['cost'] = $valueData[0]['cost'];
                    $current['image'] = $valueData[0]['image'];
                    $current['price'] = $valueData[0]['price'];
                    $current['product_id'] = $valueData[0]['product_id'];
                    $current['sales'] = $valueData[0]['sales'];
                    $current['stock'] = $valueData[0]['stock'];
                    $current['suk'] = $valueData[0]['suk'];
                    $current['unique'] = $valueData[0]['unique'];
                    $current['key']  = 1;
                } else if($raiseOne['sales'] >= $value['stock']){
                    $item = $key ? bcadd($key,1) : 1;
                    if(count($valueData) > $item){
                        $current['cost']        = $valueData[$item]['cost'];
                        $current['image']       = $valueData[$item]['image'];
                        $current['price']       = $valueData[$item]['price'];
                        $current['product_id']  = $valueData[$item]['product_id'];
                        $current['sales']       = $valueData[$item]['sales'];
                        $current['stock']       = $valueData[$item]['stock'];
                        $current['suk']         = $valueData[$item]['suk'];
                        $current['unique']      = $valueData[$item]['unique'];
                        $current['key']         = $item;
                    }
                }
            }

            //return JsonService::successful($valueData);
            if($raiseOne['people'] == 0){
                $locationBg = 0;
                $locationCu = 0;
            } else {
                $locationBg = bcmul(bcdiv($current['key'],count($valueData),3),100,2);
                $locationCu = bcsub(bcmul(bcdiv($current['key'],count($valueData),3),100,2),1);
            }
            //判断浮动左右

            if($raiseOne['sales'] < $valueData[$countTwo]['stock']){
                $Orso = 'left';
            } else {
                if(bcsub(count($valueData),$current['key']) > 2){
                    $Orso = 'right';
                    $locationCu = 0;
                } else {
                    $Orso = 'left';
                }
            }

            if($raiseOne['sales']) $bcadd = bcsub($current['stock'],$raiseOne['sales']);
        }

        $data['bcadd'] = $bcadd;
        $data['Orso'] = $Orso;
        $data['locationBg'] = $locationBg;
        $data['locationCu'] = $locationCu;
        $data['current'] = $current;
        $data['first'] = $firstOne;
        $data['last'] = $lastOne;
        $data['productAttr'] = $productAttr;
        $data['productValue'] = $valueData;
        $data['user'] = $this->userInfo;//用户信息
        $data['pindAll'] = $pindAll;
        $data['storeInfo'] = $raiseOne;
        $data['product'] = $product;

        $data['raise_ok_list']=StoreRaisedata::getRaisedataOkList($id);
        $data['raise_ok_sum']=StoreRaisedata::getRaisedataOkSumTotalNum($id);
        return JsonService::successful($data);
    }

    /**
     * 开团页面
     * @param int $id
     * @return mixed
     */
    public function get_raise($id = 0){
        $is_ok = 0;//判断众筹是否完成
        $userBool = 0;//判断当前用户是否在团内  0未在 1在
        $raiseBool = 0;//判断当前用户是否在团内  0未在 1在
        if(!$id) return JsonService::fail('参数错误');
        $raise = StoreRaisedata::getRaisedataUserOne($id);
        if(isset($raise['is_refund']) && $raise['is_refund']) {
            if($raise['is_refund'] != $raise['id']){
                $id = $raise['is_refund'];
                return $this->get_raise($id);
            }else{
                return JsonService::fail('订单已退款');
            }
        }
        if(!$raise) return JsonService::fail('参数错误');
        list($raiseAll,$raiseT,$count,$idAll,$uidAll)=StoreRaisedata::getRaisedataMemberAndRaisedataK($raise);
        if($raiseT['status'] == 2){
            $raiseBool = 1;
            $is_ok = 1;
        }else{
            if(!$count){//组团完成
                $is_ok = 1;
                $raiseBool=StoreRaisedata::RaisedataComplete($uidAll,$idAll,$this->userInfo['uid'],$raiseT);
            }else{
                $raiseBool=StoreRaisedata::RaisedataFail($raiseAll,$raiseT,$raiseBool);
            }
        }
        if(!empty($raiseAll)){
            foreach ($raiseAll as $v){
                if($v['uid'] == $this->userInfo['uid']) $userBool = 1;
            }
        }
        if($raiseT['uid'] == $this->userInfo['uid']) $userBool = 1;
        $raiseOne = StoreRaise::getRaiseOne($raise['cid']);
        if(!$raiseOne) return JsonService::fail('众筹不存在或已下架');
        $data['userInfo'] = $this->userInfo;
        $data['raiseBool'] = $raiseBool;
        $data['is_ok'] = $is_ok;
        $data['userBool'] = $userBool;
        $data['store_raise'] =$raiseOne;
        $data['raiseT'] = $raiseT;
        $data['raiseAll'] = $raiseAll;
        $data['count'] = $count;
        $data['store_raise_host'] = StoreRaise::getRaiseHost();
        $data['current_raise_order'] = StoreRaisedata::getCurrentRaisedata($id,$this->uid);
        return JsonService::successful($data);
    }

    /**
     * 获取今天正在众筹的人的头像和名称
     * @return \think\response\Json
     */
    public function get_raise_second_one()
    {
        return JsonService::successful(StoreRaisedata::getRaisedataSecondOne());
    }

    /*
     * 取消开团
     * @param int $raise_id 团长id
     * */
    public function remove_raise($raise_id=0,$cid=0,$formId='')
    {
        if(!$raise_id || !$cid) return JsonService::fail('缺少参数');
        $res=StoreRaisedata::removeRaisedata($this->uid,$cid,$raise_id,$formId);
        if($res)
            return JsonService::successful('取消成功');
        else{
            $error=StoreRaisedata::getErrorInfo();
            if(is_array($error))
                return JsonService::status($error['status'],$error['msg']);
            else
                return JsonService::fail($error);
        }
    }

    /**
     * TODO 生成海报
     */
    public function raise_share_poster()
    {
        list($raiseId) = UtilService::postMore([['id',0]],null,true);
        $raiseInfo = StoreRaisedata::getRaisedataUserOne($raiseId);
        $storeRaiseInfo = StoreRaise::getRaiseOne($raiseInfo['cid']);
        $data['title'] = $storeRaiseInfo['title'];
        $data['image'] = substr($storeRaiseInfo['image'],stripos($storeRaiseInfo['image'], '/public/uploads/'),strlen($storeRaiseInfo['image']));
        $data['price'] = $raiseInfo['total_price'];
        $data['label'] = $raiseInfo['people'].'人团';
        if($raiseInfo['k_id']) $raiseAll = StoreRaisedata::getRaisedataMember($raiseInfo['k_id']);
        else $raiseAll = StoreRaisedata::getRaisedataMember($raiseInfo['id']);
        $count = count($raiseAll)+1;
        $data['msg'] = '原价￥'.$storeRaiseInfo['product_price'].' 还差'.(int)bcsub((int)$raiseInfo['people'],$count,0).'人众筹成功';
        try{
            $path = makePathToUrl('routine/activity/raise/code',3);
            if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
            $codePath = $path.$raiseId.'_'.$this->userInfo['uid'].'.jpg';
            if(!file_exists($codePath)){
                $res = RoutineCode::getPageCode('pages/activity/goods_raise_status/index','id='.$raiseId,280);
                if($res) file_put_contents($codePath,$res);
                else return JsonService::fail('二维码生成失败');
            }
            $data['url'] = $codePath;
            $path = makePathToUrl('routine/activity/raise/poster',3);
            if($path == '') return JsonService::fail('生成上传目录失败,请检查权限!');
            $filename = ROOT_PATH.$path.'/'.$raiseId.'_'.$this->userInfo['uid'].'.jpg';
            UtilService::setShareMarketingPoster($data,$filename);
            $domain = SystemConfigService::get('site_url').'/';
            $poster = $domain.$path.'/'.$raiseId.'_'.$this->userInfo['uid'].'.jpg';
            return JsonService::successful('ok',$poster);
        }catch (\Exception $e){
            return JsonService::fail('系统错误：生成图片失败',['line'=>$e->getLine(),'message'=>$e->getMessage()]);
        }

    }


}