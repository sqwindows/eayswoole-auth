<?php
/**
 * Created by crazyCater
 * User: crazyCater
 * Date: 2019/11/23 14:05
 */

namespace CrazyCater\EasySwooleAuth;

use CrazyCater\EasySwooleAuth\Model\SystemAuthGroup;
use CrazyCater\EasySwooleAuth\Model\SystemAuthGroupAccess;
use CrazyCater\EasySwooleAuth\Model\SystemAuthMenu;

class AuthGroup
{


    public function getLists($module = 'Admin', $pid = 0, $title = '')
    {
        $SystemAuthGroup = new SystemAuthGroup();
        return $SystemAuthGroup->getLists($module = 'Admin', $pid = 0, $title = '');
    }

    public function getTree($module = 'Admin', $format = false)
    {
        $SystemAuthGroup = new SystemAuthGroup();
        return $SystemAuthGroup->getTree($module = 'Admin', $format);
    }


}
