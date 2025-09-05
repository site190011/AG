<?php

namespace app\admin\controller\game\category;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Data extends Backend
{

    /**
     * Data模型对象
     * @var \app\admin\model\game\category\Data
     */
    protected $model = null;
    protected $model_d = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\game\category\Data;

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function index()
    {
        $bindid = $this->request->request('bindid');
        $this->model = new \app\admin\model\Games;

        $this->model->field([
            'fa_games.id as game_id',
            'fa_games.game_code',
            'fa_games.game_name',
            'fa_games.ingress',
            'fa_games.plat_type',
            'fa_games.game_type',
            'fa_games.is_enable as games_is_enable',
            'fa_game_category_data.id as data_id',
        ]);
        

        $this->model->join('fa_game_category_data', 'fa_game_category_data.game_id = fa_games.id', 'LEFT');
        $this->model->where('fa_games.is_enable', 1);
        return parent::index();
    }
}
