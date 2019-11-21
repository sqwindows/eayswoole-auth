<?php
/**
 * Created by crazyCater
 * User: crazyCater
 * Date: 2019/11/20 15:10
 */

namespace CrazyCater\EasySwooleAuth;

use CrazyCater\EasySwooleAuth\Model\SystemAuthGroup;
use CrazyCater\EasySwooleAuth\Model\SystemAuthGroupAccess;
use CrazyCater\EasySwooleAuth\Model\SystemAuthMenu;

class Auth
{

    protected $config = [
        'auth_on' => true,                // 认证开关
        'session' => false,                   // 认证方式:false为实时认证,传SessionDriver则为session认证。
        'mysql_prefix' => 'cater_',          //表名前缀
        'system_auth_group' => 'system_auth_group',        // 用户组数据表名
        'system_auth_group_access' => 'system_auth_group_access', // 用户-用户组关系表
        'system_auth_menu' => 'system_auth_menu',         // 权限规则表
        'system_user' => 'system_user',            // 用户信息表
        'user_field_name' => 'system_user_id',                // 用户表ID字段名
        'group_field_name' => 'system_auth_group_id',    //用户组表组关联字段命
        'rules_field_name' => 'menus', //system_auth_group表中存储规则的字段名
        'menu_pk_field_name' => 'system_auth_menu_id',
        'allow_userids' => [],  //不用检测权限的用户编号
        'allow_urls' => [], //不用检测权限的URL
    ];

    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->prefix = !empty($this->config['mysql_prefix']) ? $this->config['mysql_prefix'] : \EasySwoole\EasySwoole\Config::getInstance()->getConf('MYSQL.prefix');
    }

    /**
     * @param string system_user_id 用户ID
     * @param string url url地址
     * @param  string urlParams  url参数
     * @param  callable urlTargetType url串接验证方式
     * @return bool
     * @author crazyCater
     */
    public function check(int $system_user_id = 0, string $url = '', array $urlParams = [], callable $urlTargetType = null)
    {
        if (!$this->config['auth_on']) {
            return true;
        }
        if (in_array($system_user_id, (array)$this->config['allow_userids'])) {
            return true;
        }
        if (in_array($url, (array)$this->config['allow_urls'])) {
            return true;
        }
        $this->urlTargetType = $urlTargetType ?? null;
        $this->system_user_id = abs(intval($system_user_id));
        if ($this->system_user_id <= 0 || !$url) {
            return false;
        }
        $this->menuLists = null;
        $cacheSessionKey = '__UserAuthLists__' . $this->system_user_id;
        $isSession = ($this->config['session'] && gettype($this->config['session']) == 'object') ? true : false;
        if ($isSession) {
            $this->session = $this->config['session'];
            $this->menuLists = $this->session->get($cacheSessionKey);
        }
        if (!$this->menuLists) {
            $this->menuLists = $this->getUserMenuLists($this->getUserMenuIds($this->system_user_id));
            if ($isSession)
                $this->session->set($cacheSessionKey, $this->menuLists);
        }
        if (!$this->menuLists) {
            return false;
        }
        $this->url = $url;
        $this->urlParams = $urlParams ? array_keys($urlParams) : [];
        return $this->_checkUrl();
    }


    protected function getUserMenuLists($menuId = [])
    {
        $SystemAuthMenu = new SystemAuthMenu($this->config);
        $lists = $SystemAuthMenu->create()
            ->where($this->config['menu_pk_field_name'], $menuId, 'in')
            ->where('url', '', '!=')
            ->where('status', 1)
            ->field('module,version,url,params')
            ->select();
        $menuLists = [];
        if ($lists) {
            foreach ($lists as $info) {
                $menuLists[] = [
                    'url' => $this->urlTargetType ? call_user_func($this->urlTargetType, $info) : $info['module'] . '/' . $info['version'] . '/' . $info['url'],
                    'params' => array_filter(explode(',', $info['params']))
                ];
            }
            print_r($menuLists);
        }
        return $menuLists;
    }

    protected function getUserMenuIds($system_user_id = 0)
    {
        if ($system_user_id <= 0) {
            return [];
        }
        $SystemAuthGroupAccess = new SystemAuthGroupAccess($this->config);
        $lists = $SystemAuthGroupAccess->alias('groupAccess')
            ->join($this->prefix . 'system_auth_group AS `group`', 'group.' . $this->config['group_field_name'] . ' = groupAccess.' . $this->config['group_field_name'])
            ->where('groupAccess.' . $this->config['user_field_name'], $system_user_id)
            ->field('group.' . $this->config['rules_field_name'])
            ->select();
        $rules = [];
        if ($lists) {
            foreach ($lists as $key => $val) {
                $menus = array_filter(explode(',', $val[$this->config['rules_field_name']]));
                if ($menus && is_array($menus)) {
                    foreach ($menus as $v) {
                        $rules[] = $v;
                    }
                }
            }
        }
        return $rules ? array_values(array_unique($rules)) : [];
    }

    private function _checkParams($params = []): bool
    {
        $params = array_filter($params);
        if (!$params) {
            return true;
        }
        $res = false;
        foreach ($this->urlParams as $key => $val) {
            if (in_array($val, $params)) {
                $res = true;
            }
        }
        return $res;

    }

    private function _checkUrl(): bool
    {
        $res = false;
        foreach ($this->menuLists as $key => $val) {
            if ($this->url === $val['url']) {
                $res = true;
                if ($this->_checkParams($val['params']) === false) {
                    $res = false;
                }
            }
        }
        return $res;
    }

}

