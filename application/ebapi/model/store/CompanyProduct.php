<?php
/**
 *
 * @author: 招宝通
 */

namespace app\ebapi\model\store;

use basic\ModelBasic;
use traits\ModelTrait;

class CompanyProduct extends ModelBasic
{
    use  ModelTrait;

    protected function getPosterImageAttr($value)
    {
        return json_decode($value,true)?:[];
    }

    protected function getSliderImageAttr($value)
    {
        return json_decode($value,true)?:[];
    }

}