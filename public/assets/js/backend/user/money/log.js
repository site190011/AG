define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/money/log/index' + location.search,
                    // add_url: 'user/money/log/add',
                    // edit_url: 'user/money/log/edit',
                    // del_url: 'user/money/log/del',
                    // multi_url: 'user/money/log/multi',
                    // import_url: 'user/money/log/import',
                    table: 'user_money_log',
                }
            });

            var table = $("#table");

            var typeSet = {
                recharge: '存款',
                withdraw: '取款',
                rebate: '返水',
                birthday: '生日礼金',
                promotion: '晋级礼金',
                monthlyRedPacket: '每月红包',
                exclusive: '专属豪礼',
                game_bet: '游戏下注',
            };

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'type', title: __('Type'), searchList: typeSet, formatter: Table.api.formatter.normal},
                        {field: 'wallet', title: __('Wallet'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'before', title: __('Before'), operate:'BETWEEN'},
                        {field: 'after', title: __('After'), operate:'BETWEEN'},
                        // {field: 'related_table', title: __('Related_table'), operate: 'LIKE'},
                        // {field: 'related_table_ids', title: __('Related_table_ids'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
