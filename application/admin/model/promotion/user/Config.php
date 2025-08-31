<?php

namespace app\admin\model\promotion\user;

use think\Model;


class Config extends Model
{

    

    

    // 表名
    protected $name = 'promotion_user_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'user_name'
    ];

    /**
     * 关联用户表一对一关系
     */
    public function user()
    {
        return $this->hasOne(\app\admin\model\User::class, 'id', 'user_id');
    }

    public function getUserNameAttr($value, $data)
    {
        $user = $this->user()->find();

        if ($user) {
            return $user->username;
        }
        
        return '-';
    }




}
