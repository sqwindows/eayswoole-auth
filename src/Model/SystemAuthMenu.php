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

class SystemAuthMenu extends Model
{


    /**
     * 表的获取
     * EasySwoole\ORM\Utility\Schema\Table
     * @return Table
     */
    public function schemaInfo(bool $isCache = true): Table
    {
        $table = new Table($this->tableName);
        $table->setIfNotExists()->setTableComment('规则菜单')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8_GENERAL_CI);
        $table->colBigInt('system_auth_menu_id', 20)->setColumnComment('菜单编号')->setIsAutoIncrement()->setIsPrimaryKey();
        $table->colVarChar('title')->setColumnLimit(64)->setIsNotNull()->setColumnComment('菜单名称');
        $table->colVarChar('module')->setColumnLimit(32)->setIsNotNull()->setDefaultValue('Admin')->setColumnComment('隶属模型');
        $table->colVarChar('version')->setColumnLimit(32)->setIsNotNull()->setDefaultValue('V1')->setColumnComment('隶属版本');
        $table->colVarChar('url')->setColumnLimit(200)->setIsNotNull()->setColumnComment('菜单地址');
        $table->colVarChar('params')->setColumnLimit(500)->setIsNotNull()->setColumnComment('地址参数');
        $table->colVarChar('icon')->setColumnLimit(200)->setIsNotNull()->setColumnComment('菜单图标');
        $table->colInt('pid', 10)->setIsNotNull()->setDefaultValue(0)->setColumnComment('父级编号');
        $table->colInt('sort', 10)->setIsNotNull()->setDefaultValue(0)->setColumnComment('菜单排序');
        $table->colTinyInt('is_hide', 1)->setIsNotNull()->setDefaultValue(0)->setColumnComment('是否隐藏');
        $table->colTinyInt('is_dev', 1)->setIsNotNull()->setDefaultValue(0)->setColumnComment('是否隐藏');
        $table->colTinyInt('status', 1)->setIsNotNull()->setDefaultValue(1)->setColumnComment('是否显示');
        $table->colInt('create_time', 10)->setDefaultValue(0)->setIsNotNull()->setColumnComment('创建时间');
        $table->colInt('update_time', 10)->setDefaultValue(0)->setIsNotNull()->setColumnComment('更新时间');
        $table->indexNormal('title', 'title');
        $table->indexNormal('module', 'module');
        $table->indexNormal('version', 'version');
        $table->indexNormal('url', 'url');
        $table->indexNormal('pid', 'pid');
        $table->indexNormal('sort', 'sort');
        $table->indexNormal('is_hide', 'is_hide');
        $table->indexNormal('is_dev', 'is_dev');
        $table->indexNormal('status', 'status');
        return $table;
    }
}
