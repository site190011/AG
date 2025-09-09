<?php

namespace app\admin\controller\promotion\user;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class AgentReport extends Backend
{

    public function index()
    {
        return $this->view->fetch('/promotion/user/agent_report');
    }
}
