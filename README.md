# uc-oauth2-server

discuz uc_server 的 oauth2实现

基于<https://github.com/bshaffer/oauth2-server-php> (目前是v1.8.0)实现oauth2流程.


## 安装

先将代码检出到 uc_server/plugin 目录下。

### 1. 增加后台菜单

编辑uc_server/view/default/admin_frame_menu.htm 文件,在

```html
<!--{if $user['isfounder']}--><li><a href="admin.php?m=plugin&a=filecheck" target="main">{lang plugin}</a></li><!--{/if}-->
```

这行下增加一行:

```html
<!--{if $user['isfounder']}--><li><a href="admin.php?m=plugin&a=oauth2" target="main">{lang plugin_oauth2}</a></li><!--{/if}-->
```

### 2. DB初始化

从后台菜单访问“OAuth2管理”,系统会做初始化检测,首次会自动创建相应DB结构,前提是mysql帐号拥有DB的表创建\修改权限.

## 用法

### 后台

OAuth2机制使用uc_server本身的应用体系作为基础,增加oauth2所需的特性.

在OAuth2插件中的应用管理界面只是对原应用管理的扩展.

为了减少重复代码,在应用管理界面仅仅是设置OAuth2相关的参数,基本的应用创建还是在原应用管理界面进行.

### 前台

访问地址:<http://your_domain/uc_server/plugin/oauth2/authorize.php>,其他参数按oauth2标准格式组织,如

?client_id=1&redirect_uri=http://your_domain/uc_server/plugin/oauth2/demo/&response_type=code&state=123456

换取用户Access Token:

```
curl -d "client_id=1&client_secret=R16aB3N6W5dbQbS4k6l2VaP538B632S0w110A8qe42jb2fP9I2i8t1t8C7ge93nb&grant_type=authorization_code&code=abcd" http://your_domain/uc_server/plugin/oauth2/token.php
```
