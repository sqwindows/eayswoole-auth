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
        'session' => false,                // 认证方式:false为实时认证,传SessionDriver则为session认证。
        'mysql_prefix' => 'cater_',       //表名前缀
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
     * @param int system_user_id 用户ID
     * @param array $urlInfo url地址信息
     * @param callable targetUrlType url串接验证方式
     * @return bool
     * @author crazyCater
     */
    public function check(int $system_user_id = 0, array $urlInfo = [], callable $checkUrlType = null, callable $targetUrlType = null)
    {
        if (!$this->config['auth_on']) {
            return true;
        }
        if (in_array($system_user_id, (array)$this->config['allow_userids'])) {
            return true;
        }
        $this->system_user_id = abs(intval($system_user_id));
        $this->urlInfo = $urlInfo;
        $this->urlInfo['module'] = $this->urlInfo['module'] ?? '';
        $this->urlInfo['version'] = $this->urlInfo['version'] ?? '';
        $this->urlInfo['controller'] = $this->urlInfo['controller'] ?? '';
        $this->urlInfo['action'] = $this->urlInfo['action'] ?? '';
        $this->urlInfo['params'] = $this->urlInfo['params'] ? array_keys($this->urlInfo['params']) : [];
        if (!$this->urlInfo || empty($this->urlInfo['controller']) || empty($this->urlInfo['action'])) {
        }
        $this->checkUrlType = $checkUrlType ?? null;
        $this->targetUrlType = $targetUrlType ?? null;

        $url = $this->urlInfo['module'] ? $this->urlInfo['module'] . '/' : '';
        $url .= $this->urlInfo['version'] ? $this->urlInfo['version'] . '/' : '';
        $url .= $this->urlInfo['controller'] ? $this->urlInfo['controller'] . '/' : '';
        $url .= $this->urlInfo['action'] ? $this->urlInfo['action'] : '';

        $this->url = $this->checkUrlType ? call_user_func($this->checkUrlType, $this->urlInfo) : $url;

        if (in_array($this->url, (array)$this->config['allow_urls'])) {
            return true;
        }
        if ($this->system_user_id <= 0) {
            return false;
        }
        $this->menuLists = null;
        $cacheSessionKey = '__' . $this->urlInfo['module'] . '__' . $this->urlInfo['version'] . '__UserAuthLists__' . $this->system_user_id;
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

        return $this->_checkUrl();
    }


    protected function getUserMenuLists($menuId = [])
    {
        $SystemAuthMenu = new SystemAuthMenu($this->config);
        $SystemAuthMenu->create()
            ->where($this->config['menu_pk_field_name'], $menuId, 'in');
        if ($this->urlInfo['module']) {
            $SystemAuthMenu->where('module', $this->urlInfo['module']);
        }
        if ($this->urlInfo['version']) {
            $SystemAuthMenu->where('version', $this->urlInfo['version']);
        }
        $lists = $SystemAuthMenu->where('url', '', '!=')
            ->where('status', 1)
            ->field('module,version,url,params')
            ->select();
        $menuLists = [];
        if ($lists) {

            foreach ($lists as $info) {
                $url = $this->urlInfo['module'] ? $this->urlInfo['module'] . '/' : '';
                $url .= $this->urlInfo['version'] ? $this->urlInfo['version'] . '/' : '';
                $url .= $info['url'] ? $info['url'] : '';
                $menuLists[] = [
                    'url' => $this->targetUrlType ? call_user_func($this->targetUrlType, $info) : $url,
                    'params' => array_filter(explode(',', $info['params']))
                ];
            }
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
        foreach ($this->urlInfo['params'] as $key => $val) {
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

