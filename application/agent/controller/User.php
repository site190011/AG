<?php

namespace app\agent\controller;

use app\common\controller\Agent;
use think\Config;
use think\Db;
use think\Hook;
use think\Session;
use think\Validate;

/**
 * 后台首页
 * @internal
 */
class User extends Agent
{

    protected $noNeedLogin = [''];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
    }

    /**
     * 后台首页
     */
    public function index()
    {

    }
}