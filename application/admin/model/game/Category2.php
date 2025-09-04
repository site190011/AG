<?php

namespace app\admin\model\game;

use think\Model;


class Category2 extends Model
{

    

    

    // 表名
    protected $name = 'game_category2';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'game_count'
    ];

    public function getGameCountAttr($value, $data)
    {
        $count = \think\Db::name('game_category_data')->where('category2_id', $data['id'])->count();
        return $count;
    }
    

    







}
