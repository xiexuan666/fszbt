<?php
/**
 *
 * Author: 招宝通
 */

namespace app\core\util;

use app\core\traits\LogicTrait;

/** 模版消息类
 * Class Template
 * @package app\core\util
 */
class Template
{
    use LogicTrait;

    protected  $providers=[
        'routine_two'=>ProgramTemplateService::class,
    ];

}