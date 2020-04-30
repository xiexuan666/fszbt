<?php
/**
 *
 * User: 招宝通
 */

namespace app\core\logic\routine;

use app\core\implement\ProviderInterface;

class RoutineLogin implements ProviderInterface
{
    public function register($config)
    {
        return ['routine_login',new self()];
    }




}