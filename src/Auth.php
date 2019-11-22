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
        'session' => false,               // 认证方式:false为实时认证,传SessionDriver则为session认证。
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
    private $systemUserId = 0; //用户ID
    private $checkUrlInfo = [];//需验证的URL地址信息
    private $checkUrlType = null; //需验证的url串接方法
    private $targetUrlType = null; // 数据库url串接方法
    private $checkUrl = '';//需检测的URL地址字符串
    private $menuLists = []; //解析后的权限菜单一维数组ids
    private $userMenuLists = []; //用户有权限的菜单列表,含URL为空的
    private $session = null;//Seesion对象

    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->prefix = !empty($this->config['mysql_prefix']) ? $this->config['mysql_prefix'] : \EasySwoole\EasySwoole\Config::getInstance()->getConf('MYSQL.prefix');
    }

    /**
     * @param int systemUserId 用户ID
     * @param array $checkUrlInfo url地址信息
     * @param callable checkUrlType 需验证的url串接方法
     * @param callable targetUrlType 数据库url串接方法
     * @return bool
     * @author crazyCater
     */
    public function check(int $systemUserId = 0, array $checkUrlInfo = [], callable $checkUrlType = null, callable $targetUrlType = null): bool
    {
        $this->systemUserId = abs(intval($systemUserId));
        $this->checkUrlInfo = $this->_setcheckUrlInfo($checkUrlInfo);
        $this->checkUrlType = $checkUrlType ?? null;
        $this->targetUrlType = $targetUrlType ?? null;
        $this->checkUrl = $this->_setcheckUrl();

        if (!$this->config['auth_on']) {
            return true;
        }
        if (in_array($this->systemUserId, (array)$this->config['allow_userids'])) {
            return true;
        }
        if (!$this->checkUrlInfo || empty($this->checkUrlInfo['controller']) || empty($this->checkUrlInfo['action'])) {
            return false;
        }
        if (in_array($this->checkUrl, (array)$this->config['allow_urls'])) {
            return true;
        }
        if ($this->systemUserId <= 0) {
            return false;
        }
        $this->menuLists = $this->_getUserSessionMenuAuths();
        return $this->_checkAuth();
    }

    /**
     * @param int systemUserId 用户ID
     * @param array $checkUrlInfo url地址信息
     * @return array
     * @author crazyCater
     */

    public function checkMenu($systemUserId = 0, $checkUrlInfo = [])
    {
        $this->systemUserId = abs(intval($systemUserId));
        $this->checkUrlInfo = $this->_setcheckUrlInfo($checkUrlInfo);
        $this->checkUrl = $this->_setcheckUrl();
        $this->menuLists = $this->_getUserSessionMenuAuths();
        return ['auth' => $this->_checkAuth(), 'userMenuLists' => $this->userMenuLists];
    }

    public function getUserMenuLists()
    {
        return $this->userMenuLists;
    }

    public function getMenuLists()
    {
        return $this->menuLists;
    }

    private function _parseUserMenuLists()
    {
        if (!$this->userMenuLists) {
            return [];
        }
        $menuLists = [];
        foreach ($this->userMenuLists as $key => $val) {
            if ($val['url'] != '')
                $menuLists[] = [
                    'module' => $val['module'],
                    'version' => $val['version'],
                    'url' => $val['url'],
                    'params' => $val['params'],
                ];
        }
        if ($menuLists) {
            $newMenuLists = [];
            foreach ($menuLists as $info) {
                $url = $this->checkUrlInfo['module'] ? $this->checkUrlInfo['module'] . '/' : '';
                $url .= $this->checkUrlInfo['version'] ? $this->checkUrlInfo['version'] . '/' : '';
                $url .= $info['url'] ? $info['url'] : '';
                $newMenuLists[] = [
                    'url' => $this->targetUrlType ? call_user_func($this->targetUrlType, $info) : $url,
                    'params' => array_filter(explode(',', $info['params']))
                ];
            }
        }

        return $this->menuLists = $newMenuLists;
    }


    private function _getUserMenuLists($menuId = [])
    {
        $SystemAuthMenu = new SystemAuthMenu($this->config);
        $SystemAuthMenu->create();
        $SystemAuthMenu->where($this->config['menu_pk_field_name'], $menuId, 'in');
        if ($this->checkUrlInfo['module']) {
            $SystemAuthMenu->where('module', $this->checkUrlInfo['module']);
        }
        if ($this->checkUrlInfo['version']) {
            $SystemAuthMenu->where('version', $this->checkUrlInfo['version']);
        }
        $this->userMenuLists = $SystemAuthMenu->where('status', 1)->select();
         return $this->userMenuLists;

    }

    private function _getUserSessionMenuAuths()
    {
        $this->menuLists = null;
        $cacheSessionKey = '__' . $this->checkUrlInfo['module'] . '__' . $this->checkUrlInfo['version'] . '__UserAuthLists__' . $this->systemUserId;
        $isSession = ($this->config['session'] && gettype($this->config['session']) == 'object') ? true : false;
        if ($isSession) {
            $this->session = $this->config['session'];
            $this->menuLists = $this->session->get($cacheSessionKey);
        }
        if (!$this->menuLists) {
            $this->menuLists = $this->_parseUserMenuLists($this->_getUserMenuLists($this->_getUserMenuIds($this->systemUserId)));
            if ($isSession)
                $this->session->set($cacheSessionKey, $this->menuLists);
        }
        return $this->menuLists;
    }

    private function _getUserMenuIds($systemUserId = 0)
    {
        if ($systemUserId <= 0) {
            return [];
        }
        $SystemAuthGroupAccess = new SystemAuthGroupAccess($this->config);
        $lists = $SystemAuthGroupAccess->alias('groupAccess')
            ->join($this->prefix . 'system_auth_group AS `group`', 'group.' . $this->config['group_field_name'] . ' = groupAccess.' . $this->config['group_field_name'])
            ->where('groupAccess.' . $this->config['user_field_name'], $systemUserId)
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
        foreach ($this->checkUrlInfo['params'] as $key => $val) {
            if (in_array($val, $params)) {
                $res = true;
            }
        }
        return $res;

    }

    private function _setcheckUrl(): string
    {
        $url = $this->checkUrlInfo['module'] ? $this->checkUrlInfo['module'] . '/' : '';
        $url .= $this->checkUrlInfo['version'] ? $this->checkUrlInfo['version'] . '/' : '';
        $url .= $this->checkUrlInfo['controller'] ? ($this->checkUrlInfo['controller'] == '/' ? '/' : $this->checkUrlInfo['controller'] . '/') : '';
        $url .= $this->checkUrlInfo['action'] ? $this->checkUrlInfo['action'] : '';
        $this->checkUrl = $this->checkUrlType ? call_user_func($this->checkUrlType, $this->checkUrlInfo) : $url;
        return $this->checkUrl;
    }

    private function _setcheckUrlInfo($checkUrlInfo = []): array
    {
        $this->checkUrlInfo['module'] = $checkUrlInfo['module'] ?? '';
        $this->checkUrlInfo['version'] = $checkUrlInfo['version'] ?? '';
        $this->checkUrlInfo['controller'] = $checkUrlInfo['controller'] ?? '';
        $this->checkUrlInfo['action'] = $checkUrlInfo['action'] ?? '';
        $this->checkUrlInfo['params'] = !empty($checkUrlInfo['params']) ? array_keys($checkUrlInfo['params']) : [];
        return $this->checkUrlInfo;
    }

    private function _checkAuth(): bool
    {
        if (!$this->menuLists) {
            return false;
        }
        $res = false;
        foreach ($this->menuLists as $key => $val) {
            if ($this->checkUrl === $val['url']) {
                $res = true;
                if ($this->_checkParams($val['params']) === false) {
                    $res = false;
                }
            }
        }
        return $res;
    }

}

