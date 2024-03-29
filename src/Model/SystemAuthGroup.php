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

class SystemAuthGroup extends Model
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
        $table->colBigInt('system_auth_group_id', 20)->setColumnComment('用户编号')->setIsAutoIncrement()->setIsPrimaryKey();
        $table->colVarChar('title')->setColumnLimit(64)->setIsNotNull()->setColumnComment('分组名称');
        $table->colVarChar('description')->setColumnLimit(64)->setIsNotNull()->setDefaultValue('')->setColumnComment('分组描述');
        $table->colVarChar('module')->setColumnLimit(32)->setIsNotNull()->setDefaultValue('Admin')->setColumnComment('隶属模型');
        $table->colInt('module_id', 10)->setIsNotNull()->setDefaultValue(0)->setColumnComment('数据编号');
        $table->colTinyInt('status', 1)->setIsNotNull()->setDefaultValue(1)->setColumnComment('分组状态');
        $table->colText('menus')->setIsNotNull()->setDefaultValue('')->setColumnComment('规则编号');
        $table->colInt('create_time', 10)->setIsNotNull()->setDefaultValue(0)->setColumnComment('创建时间');
        $table->colInt('update_time', 10)->setIsNotNull()->setDefaultValue(0)->setColumnComment('更新时间');
        $table->indexNormal('title', 'title');
        $table->indexNormal('module', 'module');
        $table->indexNormal('status', 'status');
        return $table;
    }

    public function getLists($module = 'Admin', $pid = 0, $title = '')
    {
        if ($title) {
            $this->where('title', $title, 'like');
        }
        $lists = $this->where('module', $module)->where('pid', $pid)->order('sort', 'DESC')->order($this->schemaInfo()->getPkFiledName(), 'DESC')->select();
        return $lists;
    }

    public function getTree($module = 'Admin', $format = false)
    {
        $lists = $this->where('module', $module)->where('status', 1)->order('sort', 'DESC')->order($this->schemaInfo()->getPkFiledName(), 'DESC')->select();
        $Tree = new \CrazyCater\Tree;
        $lists = $Tree->list_to_tree($lists, $this->schemaInfo()->getPkFiledName());
        if ($format === true) {
            $lists = $Tree->toFormatTree($lists, $this->schemaInfo()->getPkFiledName());
        }
        return $lists;
    }

}
