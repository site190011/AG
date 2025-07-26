<?php

namespace app\admin\model;

use think\Model;


class Article extends Model
{





    // 表名
    protected $name = 'article';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'publishtime_text',
    ];


    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });
    }


    /**
     * 获取主类型列表
     */
    public function getTypeList()
    {
        return [
            'notice' => '常规公告',
            'noticeRoll' => '滚动公告',
            'events' => '活动',
            'news' => '新闻',
            'manual' => '教程',
            'poster' => '海报'
        ];
    }

    /**
     * 获取子类型列表
     */
    public function getType2List($type = '')
    {
        switch ($type) {
            case 'events':
                return [
                    '' => '无',
                    'events_recharge' => '充值活动',
                    'events_vip' => 'VIP特权',
                    'events_cashback' => '高额返水',
                    'events_sports' => '体育优惠',
                    'events_daily' => '日常活动',
                    'events_newcomer' => '新人首存',
                    'events_limited' => '限时活动',
                ];
            default:
                return [];
        }
    }

    public function getStatusList()
    {
        return [
            'draft' => '未发布',
            'published' => '已发布',
        ];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }


    public function getPublishtimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['publishtime'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPublishtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }
}
