<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-25
 * Time: 19:41
 */

namespace app\admin\model\job;
use basic\ModelBasic;
use traits\ModelTrait;

use service\PHPExcelService;
use app\admin\model\user\User;
use app\admin\model\job\Job;
use app\admin\model\job\JobRelation;
use app\admin\model\industry\Industry;
use app\admin\model\position\Position;
use app\admin\model\user\UserSubion;

class JobPosition extends ModelBasic
{
    use  ModelTrait;

    /*
     * 获取产品列表
     * @param $where array
     * @return array
     *
     */
    public static function ProductList($where){
        $model = self::getModelObject($where)->field(['p.*','sum(pav.visit) as vstock']);
        $model = $model->order('sort desc');
        if($where['excel']==0) $model = $model->page((int)$where['page'],(int)$where['limit']);

        $data = ($data = $model->select()) && count($data) ? $data->toArray():[];

        foreach ($data as &$item){
            $userData = User::where('uid', $item['uid'])->field('avatar')->find();
            $jobData = Job::where('uid', 'IN', $item['uid'])->find();
            $positionData = Position::where('id', $item['position'])->find();
            $pid_positionData = Position::where('id', $positionData['pid'])->find();

            $industryData = Industry::where('id', $item['industry'])->find();
            $pid_industryData = Industry::where('id', $positionData['pid'])->find();
            $item['add_time'] = date('Y-m-d',$item['add_time']);
            $item['avatar'] = $userData['avatar'];
            $item['jobData'] = $jobData;
            $item['industryData'] = $pid_industryData['name'].'·'.$industryData['name'];
            $item['positionData'] = $pid_positionData['name'].'·'.$positionData['name'];
            $item['collect'] = JobRelation::where('job_id',$item['id'])->where('type','collect')->count();

            $item['count'] = UserSubion::where('subion_id',$item['id'])->where('type','job')->where('paid',1)->count();
            $item['sum'] = UserSubion::where('subion_id',$item['id'])->where('type','job')->where('paid',1)->sum('pay_price');
        }

        if($where['excel']==1){
            $export = [];
            foreach ($data as $index=>$item){
                $export[] = [
                    $item['skills'],
                    $item['experience'],
                    $item['education'],
                    $item['salary'],
                    $item['city'],
                    $item['address'],
                    $item['description'],
                    $item['collect']
                ];
            }
            PHPExcelService::setExcelHeader(['技能','经验','学历','薪资范围','城市','工作地点','描述','收藏人数'])
                ->setExcelTile('职位导出','职位信息'.time(),' 生成时间：'.date('Y-m-d H:i:s',time()))
                ->setExcelContent($export)
                ->ExcelSave();
        }
        $count = self::getModelObject($where)->count();
        return compact('count','data');
    }

    /**
     * 获取连表MOdel
     * @param $model
     * @return object
     */
    public static function getModelObject($where=[]){
        $model = new self();
        $model = $model->alias('p')->join('Job pav','p.uid=pav.uid','LEFT');
        if(!empty($where)){
            $model = $model->group('p.id');
            
            if(isset($where['keywords']) && $where['keywords']!=''){
                $model = $model->where('p.skills|p.address|p.id','LIKE',"%$where[keywords]%");
            }

            if(isset($where['position']) && trim($where['position'])!=''){
                $catid1 = $where['position'].',';//匹配最前面的cateid
                $catid2 = ','.$where['position'].',';//匹配中间的cateid
                $catid3 = ','.$where['position'];//匹配后面的cateid
                $catid4 = $where['position'];//匹配全等的cateid
                $sql    = " LIKE '$catid1%' OR `position` LIKE '%$catid2%' OR `position` LIKE '%$catid3' OR `position`=$catid4";
                $model->where(self::getPidSql($where['position']));
            }

            if(isset($where['order']) && $where['order']!=''){
                $model = $model->order(self::setOrder($where['order']));
            }
        }
        return $model;
    }
}