<?php

namespace app\admin\model\user;

use think\Model;


class Realname extends Model
{

    

    

    // 表名
    protected $name = 'user_realname';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    /**
     * 关联用户表一对一关系
     */
    public function user()
    {
        return $this->hasOne(\app\admin\model\User::class, 'id', 'user_id');
    }

    
    public function getStatusList()
    {
        return [
            '0' => '待审核',
            '1' => '通过',
            '2' => '未通过'
        ];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }




}
