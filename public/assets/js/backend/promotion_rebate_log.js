define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'promotion_rebate_log/index' + location.search,
                    // add_url: 'promotion_rebate_log/add',
                    // edit_url: 'promotion_rebate_log/edit',
                    // del_url: 'promotion_rebate_log/del',
                    // multi_url: 'promotion_rebate_log/multi',
                    // import_url: 'promotion_rebate_log/import',
                    table: 'promotion_rebate_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('收水人信息'), formatter: function(value, row, index) {
                            return 'ID :'+value+'<br>'+'用户名 :'+row.user_id_name;
                        }},
                        {field: 'player_uid', title: __('玩家信息'),formatter: function(value, row, index) {
                            return 'ID :'+value+'<br>'+'用户名 :'+row.player_uid_name;
                        }},
                        {field: 'related_bet_ids', title: __('Related_bet_ids'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'bet_amount', title: __('Bet_amount'), operate:'BETWEEN'},
                        {field: 'rebate_amount', title: __('Rebate_amount'), operate:'BETWEEN'},
                        {field: 'create_time', title: __('Created_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        // add: function () {
        //     Controller.api.bindevent();
        // },
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
