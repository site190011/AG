<?php

namespace app\admin\model\user\bank;

use think\Model;


class Card extends Model
{

    

    

    // 表名
    protected $name = 'user_bank_card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;


}
