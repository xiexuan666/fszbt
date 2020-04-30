<?php
/**
 * Created by PhpStorm.
 * User: wshbin
 * Date: 2019-07-19
 * Time: 16:16
 */

namespace app\home\controller;

use Api\Express;
use app\admin\model\system\SystemConfig;
use app\home\model\store\StoreBargainUser;
use app\home\model\store\StoreBargainUserHelp;
use app\home\model\store\StoreCombination;
use app\home\model\store\StoreOrderCartInfo;
use app\home\model\store\StorePink;
use app\home\model\store\StoreProduct;
use app\home\model\store\StoreProductRelation;
use app\home\model\store\StoreProductReply;
use app\home\model\store\StoreCouponUser;
use app\home\model\store\StoreOrder;
use app\home\model\user\User AS UserModel;
use app\home\model\user\UserBill;
use app\home\model\user\UserExtract;
use app\home\model\user\UserNotice;
use app\core\util\GroupDataService;
use app\home\model\user\UserAddress;
use app\home\model\user\UserSign;
use service\CacheService;
use app\core\util\SystemConfigService;
use think\Request;
use think\Url;

class User extends AuthController
{

}