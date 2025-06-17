<?php

namespace app\admin\model\user;

use think\Model;


class Withdraw extends Model
{

    

    

    // 表名
    protected $name = 'user_withdraw';
    
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
    

    
    public function getStatusList()
    {
        return ['processing' => __('处理中'), 'success' => __('成功'), 'failed' => __('失败')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getTypeList(){
        return [
            'bank' => __('银行卡'),
            'virtual' => __('虚拟币'),
            'third' => __('第三方支付'),
        ];
    }


}
