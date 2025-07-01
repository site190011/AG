<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 入口文件 ]
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/base.php';

// 绑定到agent模块
\think\Route::bind('agent');

// 关闭路由
\think\App::route(false);

// 设置根url
\think\Url::root('');

// 执行应用
\think\App::run()->send();
