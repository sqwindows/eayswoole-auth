<?php
/**
 * Created by crazyCater
 * User: crazyCater
 * Date: 2019/11/21 10:36
 */

namespace CrazyCater\EasySwooleAuth\Model;

use CrazyCater\EasySwooleAuth\Model\Model;
use EasySwoole\ORM\Utility\Schema\Table;
use EasySwoole\DDL\Enum\Character;
use EasySwoole\DDL\Enum\Engine;

class SystemAuthGroupAccess extends Model
{
    /**
     * 表的获取
     * EasySwoole\ORM\Utility\Schema\Table
     * @return Table
     */
    public function schemaInfo(bool $isCache = true): Table
    {
        $table = new Table($this->tableName);
        $table->setIfNotExists()->setTableComment('权限分组表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8_GENERAL_CI);
        $table->colBigInt('system_auth_group_access_id', 20)->setColumnComment('自动编号')->setIsAutoIncrement()->setIsPrimaryKey();
        $table->colInt('system_user_id')->setColumnLimit(20)->setIsNotNull()->setDefaultValue(0)->setColumnComment('用户编号');
        $table->colInt('system_auth_group_id')->setColumnLimit(20)->setIsNotNull()->setDefaultValue(0)->setColumnComment('分组编号');
        $table->colInt('create_time', 10)->setIsNotNull()->setDefaultValue(0)->setColumnComment('创建时间');
        $table->colInt('update_time', 10)->setIsNotNull()->setDefaultValue(0)->setColumnComment('更新时间');
        $table->indexNormal('system_user_id', 'system_user_id');
        $table->indexNormal('system_auth_group_id', 'system_auth_group_id');
        return $table;
    }
}
