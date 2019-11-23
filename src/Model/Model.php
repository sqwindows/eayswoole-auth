<?php
/**
 * Created by crazyCater
 * User: crazyCater
 * Date: 2019/11/12 15:36
 */

namespace CrazyCater\EasySwooleAuth\Model;

use EasySwoole\ORM\AbstractModel;


class Model extends AbstractModel
{
    protected $autoTimeStamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $config = [];

    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $prefix = !empty($this->config['mysql_prefix']) ? $this->config['mysql_prefix'] : \EasySwoole\EasySwoole\Config::getInstance()->getConf('MYSQL.prefix');
        $className = explode("\\", str_replace("CrazyCater\EasySwooleAuth\Model\\", '', get_class($this)));
        $tableName = \EasySwoole\Utility\Str::snake(array_pop($className));
        $tableName = !empty($this->config[$tableName]) ? $this->config[$tableName] : $tableName;
        $this->tableName = $this->tableName ? $this->tableName : $prefix . $tableName;
        parent::__construct($config);
    }

    public function trace(array $data = [])
    {
        !empty($this->config['call']) && $this->onQuery($this->config['call']);
        return $this->create($data);
    }

    protected function getCreateTimeAttr($data)
    {
        return date('Y-m-d H:i:s', $data);
    }

    protected function getUpdateTimeAttr($data)
    {
        return date('Y-m-d H:i:s', $data);
    }

    public function getColumns()
    {
        $newFiels = [];
        try {
            $table = $this->schemaInfo();
            $fiels = $table->getColumns();
            $newFiels = [];
            if ($fiels)
                foreach ($fiels as $key => $val) {
                    $newFiels[] = $key;
                }
        } catch (\Exception $e) {

        }
        return $newFiels;
    }

    /**
     * 增加feild排除功能
     * @param $fields
     * @param $exceptField
     * @return $this
     */
    public function field($fields = '', $exceptField = '')
    {
        $fields = $fields === true ? '*' : $fields;
        $isAll = ($fields === true || $fields === '*') ? true : false;

        if (!is_array($fields)) {
            $fields = [$fields];
        }
        if (!is_array($exceptField)) {
            $exceptField = [$exceptField];
        }
        if ($exceptField) {
            $allFields = $isAll === true ? $this->getColumns() : $fields;
            foreach ($allFields as $key => $val) {
                if (in_array($val, $exceptField)) {
                    unset($allFields[$key]);
                }
            }
            $fields = $allFields;
        }
        return parent::field($fields);
    }


}
