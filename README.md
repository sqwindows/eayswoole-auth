## 简介
EasySwoole-RBAC版权限验证。
## 安装
```bash
composer require crazycater/easyswoole-auth
```
## 使用方法

1.控制器调用方法
```php
     $Auth = new \CrazyCater\EasySwooleAuth\Auth([
          'session' => $this->session(),    // 认证方式:false为实时认证,传SessionDriver则为session认证。 默认：false实时认证
          'allow_userids' => [10000],  //不用检测权限的用户编号  默认空数组
          'allow_urls' => ['Admin/V1/SystemAuthMenu/tree'], //不用检测权限的URL 默认空数组
          'mysql_prefix' => 'cater_',  //表名前缀  默认Config配置文件中 MYSQL.prefix
          
          //以下参数不修改数据库名和字段名的 默认可不传
        
          //'auth_on' => true,             // 认证开关 默认：开
          //'system_auth_group' => 'system_auth_group',        // 用户组数据表名 
          //'system_auth_group_access' => 'system_auth_group_access', // 用户-用户组关系表
          //'system_auth_menu' => 'system_auth_menu',         // 权限规则表
          //'system_user' => 'system_user',            // 用户信息表
          //'user_field_name' => 'system_user_id',                // 用户表ID字段名
          //'group_field_name' => 'system_auth_group_id',    //用户组表组关联字段命
          //'rules_field_name' => 'menus', //system_auth_group 表中存储规则的字段名
          //'menu_pk_field_name' => 'system_auth_menu_id',
          
     ]);
     //默认验证方法
      $authUrl = [
          'module' => $this->moduleName,//项目分组可传可不传  用于多个项目分组
          'controller' => $this->controllerName,//必须传
          'action' => $this->actionName,//必须传
          'params' => $this->request()->getRequestParam() //可传可不传
       ];
             
     $check1 = $Auth->check($user_id, $authUrl);
     var_dump($check1);
    
    //可自定义url串接方式，第三个回调参数用来自定义串接要鉴权的URL地址，第四个回调参数用来自定义串接数据库查询出URL地址， 两者比对正确为鉴权通过
     $check2 = $Auth->check($user_id, $authUrl,function ($urlInfo) {
           return $urlInfo['module'] . '-' . $urlInfo['controller'] . '-' .$urlInfo['action'] ;
       }, function ($urlInfo) {
           return $urlInfo['module'] . '-' . $urlInfo['url'] ;
      });
      var_dump($check2);
         
```
2.需要创建一下三张数据表
 
```sql
 
 CREATE TABLE IF NOT EXISTS `cater_system_auth_group` (
   `system_auth_group_id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '用户编号',
   `title` varchar(64) NOT NULL COMMENT '分组名称',
   `description` varchar(64) NOT NULL DEFAULT '' COMMENT '分组描述',
   `module` varchar(32) NOT NULL DEFAULT 'Admin' COMMENT '隶属模型',
   `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '分组状态',
   `menus` text NOT NULL DEFAULT '' COMMENT '规则编号',
   `create_time` int(10) NOT NULL DEFAULT 0 COMMENT '创建时间',
   `update_time` int(10) NOT NULL DEFAULT 0 COMMENT '更新时间',
   INDEX `title` (`title`),
   INDEX `module` (`module`),
   INDEX `status` (`status`),
 )
 ENGINE = INNODB DEFAULT COLLATE = 'utf8_general_ci' COMMENT = '权限分组表';
 
 
 CREATE TABLE IF NOT EXISTS `cater_system_auth_group_access` (
   `system_auth_group_access_id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '自动编号',
   `system_user_id` bigint(20) NOT NULL DEFAULT 0 COMMENT '用户编号',
   `system_auth_group_id` bigint(20) NOT NULL DEFAULT 0 COMMENT '分组编号',
   `create_time` int(10) NOT NULL DEFAULT 0 COMMENT '创建时间',
   `update_time` int(10) NOT NULL DEFAULT 0 COMMENT '更新时间',
   INDEX `system_user_id` (`system_user_id`),
   INDEX `system_auth_group_id` (`system_auth_group_id`)
 )
 ENGINE = INNODB DEFAULT COLLATE = 'utf8_general_ci' COMMENT = '权限分组表';
 
 
 CREATE TABLE IF NOT EXISTS `cater_system_auth_menu` (
   `system_auth_menu_id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT '菜单编号',
   `title` varchar(64) NOT NULL COMMENT '菜单名称',
   `module` varchar(32) NOT NULL DEFAULT 'Admin' COMMENT '隶属模型',
   `url` varchar(200) NOT NULL COMMENT '菜单地址',
   `icon` varchar(200) NOT NULL COMMENT '菜单图标',
   `pid` int(10) NOT NULL DEFAULT 0 COMMENT '父级编号',
   `sort` int(10) NOT NULL DEFAULT 0 COMMENT '菜单排序',
   `is_hide` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否隐藏',
   `is_dev` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否隐藏',
   `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示',
   `create_time` int(10) NOT NULL DEFAULT 0 COMMENT '创建时间',
   `update_time` int(10) NOT NULL DEFAULT 0 COMMENT '更新时间',
   INDEX `title` (`title`),
   INDEX `module` (`module`),
   INDEX `url` (`url`),
   INDEX `pid` (`pid`),
   INDEX `sort` (`sort`),
   INDEX `is_hide` (`is_hide`),
   INDEX `is_dev` (`is_dev`),
   INDEX `status` (`status`)
 )
 ENGINE = INNODB DEFAULT COLLATE = 'utf8_general_ci' COMMENT = '规则菜单';

```
