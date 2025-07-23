define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/total/index',
                    add_url: 'user/total/add',
                    edit_url: 'user/total/edit',
                    del_url: 'user/total/del',
                    multi_url: 'user/total/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                searchFormVisible: true,
                search:false,
                fixedRightNumber: 1,
                columns: [
                        [{
                            title: '会员相关',
                            align: 'center',
                            color:'red',
                            colspan: 4, // 跨2列
                            group: true // 启用分组
                        },
                        {
                            title: '充值',
                            align: 'center',
                            colspan: 3,
                            group: true
                        },
                         {
                            title: '提款',
                            align: 'center',
                            colspan: 2,
                            group: true
                        },
                         {
                            title: '加/扣款',
                            align: 'center',
                            colspan: 2,
                            group: true
                        },
                          {
                            title: '优惠福利',
                            align: 'center',
                            colspan: 3,
                            group: true
                        },
                         {
                            title: '投注',
                            align: 'center',
                            colspan: 4,
                            group: true
                        }
                        ],
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('会员ID')},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        // {field: 'money', title: '余额', operate: false},
                        {field: 'viplevel', title: '等级', operate: false},
                        {field: 'agent_promotion', title: '代理',formatter: Table.api.formatter.status, searchList: {0: __('否'), 1: __('是')},operate: false},
                        {field: 'recharge', title: '充值金额', operate: false},
                        {field: 'recharge_num', title: '充值加送', operate: false},
                        {field: 'recharge_gift', title: '充值次数', operate: false},
                        {field: 'withdraw', title: '提现金额', operate: false},
                        {field: 'withdraw_num', title: '提现次数', operate: false},
                        {field: 'add', title: '加款', operate: false},
                        {field: 'sub', title: '扣款', operate: false},
                         {field: 'discount', title: '优惠金额', operate: false},
                        {field: 'red', title: '红包福利', operate: false},
                         {field: 'activity', title: '活动彩金', operate: false},
                        {field: 'defect', title: '返水金额', operate: false},
                        {field: 'valid', title: '有效投注', operate: false},
                         {field: 'bet', title: '总投注', operate: false},
                        {field: 'settled', title: '总输赢', operate: false},
                        {field: 'jointime', title: __('选择日期'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true,visible: false},
                        

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        edit_money: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});