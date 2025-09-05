<?php

namespace app\admin\controller\game\category;

use app\common\controller\Backend;
use think\Db;

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
            'if(fa_game_category_data.category2_id, 1, 0) as bind',
        ]);
        

        $this->model->join('fa_game_category_data', 'fa_game_category_data.game_id = fa_games.id', 'LEFT');
        $this->model->where('fa_games.is_enable', 1);
        return parent::index();
    }

    public function edit($ids = null)
    {
        $category2_id = $this->request->request('category2_id');
        $game_id = $this->request->request('game_id');
        $bind = $this->request->request('bind');
        
        if ($bind == 'yes') {
            if (Db::name('game_category_data')->where('game_id', $game_id)->where('category2_id', $category2_id)->count() == 0) {
                Db::name('game_category_data')->insert([
                    'category2_id' => $category2_id,
                    'game_id' => $game_id
                ]);
            }
            $this->success('绑定成功');
        } else {
            Db::name('game_category_data')->where('game_id', $game_id)->where('category2_id', $category2_id)->delete();
            $this->success('解绑成功');
        }
    }
}
