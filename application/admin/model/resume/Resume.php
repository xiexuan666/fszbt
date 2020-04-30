<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-10
 * Time: 15:24
 */

namespace app\admin\model\resume;

use service\PHPExcelService;
use app\admin\model\user\User;
use app\admin\model\resume\ResumeEducation;
use app\admin\model\resume\ResumeExpect;
use app\admin\model\resume\ResumeProject;
use app\admin\model\resume\ResumeRelation;
use app\admin\model\resume\ResumeWork;

use app\admin\model\user\UserSubion;

use app\admin\model\industry\Industry;
use app\admin\model\position\Position;
use app\admin\model\system\SystemAdmin;
use traits\ModelTrait;
use basic\ModelBasic;
use think\Db;

/**
 * 职位管理 Model
 * Class Resume
 */
class Resume extends ModelBasic
{
    use ModelTrait;

    public static function getList($where){
        $model = self::getModelObject($where)->field(['p.*','pav.nickname,pav.avatar,pav.now_money,pav.integral,pav.spread_uid']);
        $model = $model->order('sort desc,id desc');
        if($where['excel']==0) $model = $model->page((int)$where['page'],(int)$where['limit']);

        $data = ($data = $model->select()) && count($data) ? $data->toArray():[];

        foreach ($data as &$item){
            $item['collect'] = ResumeRelation::where('resume_id',$item['id'])->where('type','collect')->count();

            $EducationName = ResumeEducation::where('uid', 'IN', $item['uid'])->column('name', 'id');
            $item['EducationName'] = is_array($EducationName) ? implode(',',$EducationName) : '';

            $ProjectName = ResumeProject::where('uid', 'IN', $item['uid'])->column('name', 'id');
            $item['ProjectName'] = is_array($ProjectName) ? implode(',',$ProjectName) : '';

            $WorkName = ResumeWork::where('uid', 'IN', $item['uid'])->column('name', 'id');
            $item['WorktName'] = is_array($WorkName) ? implode(',',$WorkName) : '';

            $ExpectData = ResumeExpect::where('uid', $item['uid'])->find();
            $positionData = Position::where('id', 'IN', $ExpectData['position'])->column('name', 'id');
            $industryData = Industry::where('id', 'IN', $ExpectData['industry'])->column('name', 'id');
            $item['positionData'] = is_array($positionData) ? implode(',',$positionData) : '';
            $item['industryData'] = is_array($industryData) ? implode(',',$industryData) : '';
            $item['add_time'] = date('Y-m-d',$item['add_time']);

            $item['count'] = UserSubion::where('subion_id',$item['id'])->where('type','resume')->where('paid',1)->count();
            $item['sum'] = UserSubion::where('subion_id',$item['id'])->where('type','resume')->where('paid',1)->sum('pay_price');
        }

        if($where['excel']==1){
            $export = [];
            foreach ($data as $index=>$item){
                $export[] = [
                    $item['name'],
                    $item['EducationName'],
                    $item['ProjectName'],
                    $item['WorktName'],
                    $item['positionData'],
                    $item['industryData'],
                    $item['description'],
                    $item['collect']
                ];
            }
            PHPExcelService::setExcelHeader(['姓名','学历','项目','经历','职位','类型','描述','收藏人数'])
                ->setExcelTile('求职导出','求职信息'.time(),' 生成时间：'.date('Y-m-d H:i:s',time()))
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
        $model = $model->alias('p')->join('User pav','p.uid=pav.uid','LEFT');
        if(!empty($where)){
            $model = $model->group('p.id');

            if(isset($where['keywords']) && $where['keywords']!=''){
                $model = $model->where('p.name|p.phone|p.id','LIKE',"%$where[keywords]%");
            }

            if(isset($where['order']) && $where['order']!=''){
                $model = $model->order(self::setOrder($where['order']));
            }
        }
        return $model;
    }

}