<?php

namespace app\admin\model;

use think\Model;


class Bank extends Model
{

    

    

    // 表名
    protected $name = 'bank';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    public function getTypeList(){
        return [
            'bank' => __('银行卡'),
            'virtual' => __('虚拟币'),
            'third' => __('第三方支付'),
        ];
    }

    public function getStatusList()
    {
        return [
            '1' => __('启用'),
            '0' => __('停用'),
        ];
    }

    







}
