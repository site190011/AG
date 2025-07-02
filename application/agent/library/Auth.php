<?php

namespace app\agent\library;

use app\agent\model\Agent;
use fast\Random;
use fast\Tree;
use think\Config;
use think\Cookie;
use think\Hook;
use think\Request;
use think\Session;

class Auth extends \fast\Auth
{
    protected $_error = '';
    protected $requestUri = '';
    protected $breadcrumb = [];
    protected $logined = false; //登录状态

    public function __construct()
    {
        parent::__construct();
    }

    public function __get($name)
    {
        return Session::get('agent.' . $name);
    }

    /**
     * 代理登录
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param int    $keeptime 有效时长
     * @return  boolean
     */
    public function login($username, $password, $keeptime = 0)
    {
        $agent = Agent::get(['username' => $username]);
        if (!$agent) {
            $this->setError('用户名不存在');
            return false;
        }
        if ($agent['status'] == 'hidden') {
            $this->setError('账号状态异常');
            return false;
        }
        if ($agent['agent_promotion'] != '1') {
            $this->setError('代理商未启用');
            return false;
        }

        if (Config::get('fastadmin.login_failure_retry') && $agent->loginfailure >= 10 && time() - $agent->updatetime < 86400) {
            $this->setError('登录失败次数过多，请稍后再试');
            return false;
        }
        if ($agent->password != $this->getEncryptPassword($password, $agent->salt)) {
            $agent->loginfailure++;
            $agent->save();
            $this->setError('密码错误');
            return false;
        }
        $agent->loginfailure = 0;
        $agent->logintime = time();
        $agent->loginip = request()->ip();
        $agent->token = Random::uuid();
        $agent->save();
        Session::set("agent", $agent->toArray());
        Session::set("agent.safecode", $this->getEncryptSafecode($agent));
        $this->keeplogin($agent, $keeptime);
        return true;
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $agent = Agent::get(intval($this->id));
        if ($agent) {
            $agent->token = '';
            $agent->save();
        }
        $this->logined = false; //重置登录状态
        Session::delete("agent");
        Cookie::delete("keeplogin");
        setcookie('fastadmin_userinfo', '', $_SERVER['REQUEST_TIME'] - 3600, rtrim(url("/" . request()->module(), '', false), '/'));
        return true;
    }

    /**
     * 自动登录
     * @return boolean
     */
    public function autologin()
    {
        $keeplogin = Cookie::get('keeplogin');
        if (!$keeplogin) {
            return false;
        }
        list($id, $keeptime, $expiretime, $key) = explode('|', $keeplogin);
        if ($id && $keeptime && $expiretime && $key && $expiretime > time()) {
            $agent = Agent::get($id);
            if (!$agent || !$agent->token) {
                return false;
            }
            //token有变更
            if ($key != $this->getKeeploginKey($agent, $keeptime, $expiretime)) {
                return false;
            }
            $ip = request()->ip();
            //IP有变动
            if ($agent->loginip != $ip) {
                return false;
            }
            Session::set("agent", $agent->toArray());
            Session::set("agent.safecode", $this->getEncryptSafecode($agent));
            //刷新自动登录的时效
            $this->keeplogin($agent, $keeptime);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 刷新保持登录的Cookie
     *
     * @param int $keeptime
     * @return  boolean
     */
    protected function keeplogin($agent, $keeptime = 0)
    {
        if ($keeptime) {
            $expiretime = time() + $keeptime;
            $key = $this->getKeeploginKey($agent, $keeptime, $expiretime);
            Cookie::set('keeplogin', implode('|', [$agent['id'], $keeptime, $expiretime, $key]), $keeptime);
            return true;
        }
        return false;
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password 密码
     * @param string $salt     密码盐
     * @return string
     */
    public function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 获取密码加密后的自动登录码
     * @param string $password 密码
     * @param string $salt     密码盐
     * @return string
     */
    public function getEncryptKeeplogin($params, $keeptime)
    {
        $expiretime = time() + $keeptime;
        $key = md5(md5($params['id']) . md5($keeptime) . md5($expiretime) . $params['token'] . config('token.key'));
        return implode('|', [$this->id, $keeptime, $expiretime, $key]);
    }

    /**
     * 获取自动登录Key
     * @param $params
     * @param $keeptime
     * @param $expiretime
     * @return string
     */
    public function getKeeploginKey($params, $keeptime, $expiretime)
    {
        $key = md5(md5($params['id']) . md5($keeptime) . md5($expiretime) . $params['token'] . config('token.key'));
        return $key;
    }

    /**
     * 获取加密后的安全码
     * @param $params
     * @return string
     */
    public function getEncryptSafecode($params)
    {
        return md5(md5($params['username']) . md5(substr($params['password'], 0, 6)) . config('token.key'));
    }

    public function check($name, $uid = '', $relation = 'or', $mode = 'url')
    {
        $uid = $uid ? $uid : $this->id;
        return parent::check($name, $uid, $relation, $mode);
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return bool
     */
    public function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr) {
            return false;
        }

        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr)) {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 检测是否登录
     *
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->logined) {
            return true;
        }
        $agent = Session::get('agent');
        if (!$agent) {
            return false;
        }
        $my = Agent::get($agent['id']);
        if (!$my) {
            return false;
        }
        //校验安全码，可用于判断关键信息发生了变更需要重新登录
        if (!isset($agent['safecode']) || $this->getEncryptSafecode($my) !== $agent['safecode']) {
            $this->logout();
            return false;
        }
        //判断是否同一时间同一账号只能在一个地方登录
        if (Config::get('fastadmin.login_unique')) {
            if ($my['token'] != $agent['token']) {
                $this->logout();
                return false;
            }
        }
        //判断管理员IP是否变动
        if (Config::get('fastadmin.loginip_check')) {
            if (!isset($agent['loginip']) || $agent['loginip'] != request()->ip()) {
                $this->logout();
                return false;
            }
        }
        $this->logined = true;
        return true;
    }

    /**
     * 获取当前请求的URI
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI
     * @param string $uri
     */
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }

    public function getUserInfo($uid = null)
    {
        $uid = is_null($uid) ? $this->id : $uid;

        return $uid != $this->id ? Agent::get(intval($uid)) : Session::get('agent');
    }


    /**
     * 获得面包屑导航
     * @param string $path
     * @return array
     */
    public function getBreadCrumb($path = '')
    {
        if ($this->breadcrumb || !$path) {
            return $this->breadcrumb;
        }
        $titleArr = [];
        $menuArr = [];
        $urlArr = explode('/', $path);
        foreach ($urlArr as $index => $item) {
            $pathArr[implode('/', array_slice($urlArr, 0, $index + 1))] = $index;
        }
        foreach ($this->rules as $rule) {
            if (isset($pathArr[$rule['name']])) {
                $rule['title'] = __($rule['title']);
                $rule['url'] = url($rule['name']);
                $titleArr[$pathArr[$rule['name']]] = $rule['title'];
                $menuArr[$pathArr[$rule['name']]] = $rule;
            }
        }
        ksort($menuArr);
        $this->breadcrumb = $menuArr;
        return $this->breadcrumb;
    }

    /**
     * 获取左侧和顶部菜单栏
     *
     * @param array  $params    URL对应的badge数据
     * @param string $fixedPage 默认页
     * @return array
     */
    public function getSidebar($params = [], $fixedPage = 'dashboard')
    {
        // 边栏开始
        Hook::listen("agent_sidebar_begin", $params);
        $colorArr = ['red', 'green', 'yellow', 'blue', 'teal', 'orange', 'purple'];
        $colorNums = count($colorArr);
        $badgeList = [];
        $module = request()->module();
        // 生成菜单的badge
        foreach ($params as $k => $v) {
            $url = $k;
            if (is_array($v)) {
                $nums = $v[0] ?? 0;
                $color = $v[1] ?? $colorArr[(is_numeric($nums) ? $nums : strlen($nums)) % $colorNums];
                $class = $v[2] ?? 'label';
            } else {
                $nums = $v;
                $color = $colorArr[(is_numeric($nums) ? $nums : strlen($nums)) % $colorNums];
                $class = 'label';
            }
            //必须nums大于0才显示
            if ($nums) {
                $badgeList[$url] = '<small class="' . $class . ' pull-right bg-' . $color . '">' . $nums . '</small>';
            }
        }

        // 读取管理员当前拥有的权限节点
        $selected = $referer = [];
        $refererUrl = Session::get('referer');

        $ruleList = [
            [
                "id" => 1,
                "pid" => 0,
                "name" => "index",
                "title" => "首页",
                "icon" => "fa fa-dashboard",
                "url" => "",
                "menutype" => null,
            ],
            [
                "id" => 2,
                "pid" => 0,
                "name" => "user",
                "title" => "会员列表",
                "icon" => "fa fa-user",
                "url" => "",
                "menutype" => null,
            ],
            [
                "id" => 3,
                "pid" => 0,
                "name" => "recharge",
                "title" => "充值列表",
                "icon" => "fa fa-list",
                "url" => "",
                "menutype" => null,
            ],
            [
                "id" => 4,
                "pid" => 0,
                "name" => "withdraw",
                "title" => "提现列表",
                "icon" => "fa fa-list",
                "url" => "",
                "menutype" => null,
            ],
            [
                "id" => 5,
                "pid" => 0,
                "name" => "rebate",
                "title" => "返水列表",
                "icon" => "fa fa-list",
                "url" => "",
                "menutype" => null,
            ],
            [
                "id" => 6,
                "pid" => 0,
                "name" => "gamerecord",
                "title" => "游戏记录",
                "icon" => "fa fa-list",
                "url" => "",
                "menutype" => null,
            ]
        ];

        $pidArr = array_unique(array_filter(array_column($ruleList, 'pid')));
        foreach ($ruleList as $k => &$v) {
            $indexRuleName = $v['name'] . '/index';
            $v['icon'] = $v['icon'] . ' fa-fw';
            $v['url'] = isset($v['url']) && $v['url'] ? $v['url'] : '/' . $module . '/' . $v['name'];
            $v['badge'] = $badgeList[$v['name']] ?? '';
            $v['title'] = __($v['title']);
            $v['url'] = preg_match("/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i", $v['url']) ? $v['url'] : url($v['url']);
            $v['menuclass'] = in_array($v['menutype'], ['dialog', 'ajax']) ? 'btn-' . $v['menutype'] : '';
            $v['menutabs'] = !$v['menutype'] || in_array($v['menutype'], ['default', 'addtabs']) ? 'addtabs="' . $v['id'] . '"' : '';
            $selected = $v['name'] == $fixedPage ? $v : $selected;
            $referer = $v['url'] == $refererUrl ? $v : $referer;
        }
        $lastArr = array_unique(array_filter(array_column($ruleList, 'pid')));
        $pidDiffArr = array_diff($pidArr, $lastArr);
        foreach ($ruleList as $index => $item) {
            if (in_array($item['id'], $pidDiffArr)) {
                unset($ruleList[$index]);
            }
        }
        
        if ($selected == $referer) {
            $referer = [];
        }

        $select_id = $referer ? $referer['id'] : ($selected ? $selected['id'] : 0);
        $menu = $nav = '';
        $showSubmenu = config('fastadmin.show_submenu');
        if (Config::get('fastadmin.multiplenav')) {
            $topList = [];
            foreach ($ruleList as $index => $item) {
                if (!$item['pid']) {
                    $topList[] = $item;
                }
            }
            $selectParentIds = [];
            $tree = Tree::instance();
            $tree->init($ruleList);
            if ($select_id) {
                $selectParentIds = $tree->getParentsIds($select_id, true);
            }
            foreach ($topList as $index => $item) {
                $childList = Tree::instance()->getTreeMenu(
                    $item['id'],
                    '<li class="@class" pid="@pid"><a @extend href="@url@addtabs" addtabs="@id" class="@menuclass" url="@url" py="@py" pinyin="@pinyin"><i class="@icon"></i> <span>@title</span> <span class="pull-right-container">@caret @badge</span></a> @childlist</li>',
                    $select_id,
                    '',
                    'ul',
                    'class="treeview-menu' . ($showSubmenu ? ' menu-open' : '') . '"'
                );
                $current = in_array($item['id'], $selectParentIds);
                $url = $childList ? 'javascript:;' : $item['url'];
                $addtabs = $childList || !$url ? "" : (stripos($url, "?") !== false ? "&" : "?") . "ref=" . ($item['menutype'] ? $item['menutype'] : 'addtabs');
                $childList = str_replace(
                    '" pid="' . $item['id'] . '"',
                    ' ' . ($current ? '' : 'hidden') . '" pid="' . $item['id'] . '"',
                    $childList
                );
                $nav .= '<li class="' . ($current ? 'active' : '') . '"><a ' . $item['extend'] . ' href="' . $url . $addtabs . '" ' . $item['menutabs'] . ' class="' . $item['menuclass'] . '" url="' . $url . '" title="' . $item['title'] . '"><i class="' . $item['icon'] . '"></i> <span>' . $item['title'] . '</span> <span class="pull-right-container"> </span></a> </li>';
                $menu .= $childList;
            }
        } else {
            // 构造菜单数据
            Tree::instance()->init($ruleList);
            $menu = Tree::instance()->getTreeMenu(
                0,
                '<li class="@class"><a @extend href="@url@addtabs" @menutabs class="@menuclass" url="@url" py="@py" pinyin="@pinyin"><i class="@icon"></i> <span>@title</span> <span class="pull-right-container">@caret @badge</span></a> @childlist</li>',
                $select_id,
                '',
                'ul',
                'class="treeview-menu' . ($showSubmenu ? ' menu-open' : '') . '"'
            );
            if ($selected) {
                $nav .= '<li role="presentation" id="tab_' . $selected['id'] . '" class="' . ($referer ? '' : 'active') . '"><a href="#con_' . $selected['id'] . '" node-id="' . $selected['id'] . '" aria-controls="' . $selected['id'] . '" role="tab" data-toggle="tab"><i class="' . $selected['icon'] . ' fa-fw"></i> <span>' . $selected['title'] . '</span> </a></li>';
            }
            if ($referer) {
                $nav .= '<li role="presentation" id="tab_' . $referer['id'] . '" class="active"><a href="#con_' . $referer['id'] . '" node-id="' . $referer['id'] . '" aria-controls="' . $referer['id'] . '" role="tab" data-toggle="tab"><i class="' . $referer['icon'] . ' fa-fw"></i> <span>' . $referer['title'] . '</span> </a> <i class="close-tab fa fa-remove"></i></li>';
            }
        }

        return [$menu, $nav, $selected, $referer];
    }

    /**
     * 设置错误信息
     *
     * @param string $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }
}
