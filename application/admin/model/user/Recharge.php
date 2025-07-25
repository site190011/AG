<?php

namespace app\admin\model\user;

use think\Model;


class Recharge extends Model
{

    

    

    // 表名
    protected $name = 'user_recharge';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'user'
    ];
    
    /**
     * 关联用户模型一对一关系
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne('app\admin\model\User', 'id', 'user_id');
    }
    

}