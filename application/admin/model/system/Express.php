<?php
/**
 *
 * @author: 招宝通
 */

namespace app\admin\model\system;

use traits\ModelTrait;
use basic\ModelBasic;

/**
 * Class Express
 * @package app\admin\model\system
 */
class Express extends ModelBasic
{
    use ModelTrait;
    public static function systemPage($params)
    {
        $model = new self;
        if($params['keyword'] !== '') $model = $model->where('name|code','LIKE',"%$params[keyword]%");
        $model = $model->order('sort DESC,id DESC');
        return self::page($model,$params);
    }
}