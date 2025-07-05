define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'gamerecord/index' + location.search,
                    table: 'game_bet',
                }
            });

            var table = $("#table");

            var typeSet = {
                 '1': '视讯',
                '2': '老虎机',
                '3': '彩票',
                '4': '体育',
                '5': '电竞',
                '6': '捕猎',
                '7': '棋牌'
            };

             var statusSet = {
                '0': '未完成',
                '1': '已完成',
                '2': '已取消',
                '3': '已撤单'
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
                        // {field: 'player_id', title: __('Player_id'), operate: 'LIKE'},
                        {field: 'plat_type', title: __('Plat_type'), operate: 'LIKE'},
                        {field: 'currency', title: __('Currency'), operate: 'LIKE'},
                        {field: 'game_type', title: __('Game_type'), searchList: typeSet, formatter: Table.api.formatter.normal},
                        {field: 'game_name', title: __('Game_name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'round', title: __('Round'), operate: 'LIKE'},
                        {field: 'table_no', title: __('Table_no'), operate: 'LIKE'},
                        {field: 'seat_no', title: __('Seat_no'), operate: 'LIKE'},
                        {field: 'bet_amount', title: __('Bet_amount'), operate:'BETWEEN'},
                        {field: 'valid_amount', title: __('Valid_amount'), operate:'BETWEEN'},
                        {field: 'settled_amount', title: __('Settled_amount'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), searchList: statusSet, formatter: Table.api.formatter.status},
                        {field: 'game_order_id', title: __('Game_order_id'), operate: 'LIKE'},
                        {field: 'bet_time', title: __('Bet_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'last_update_time', title: __('Last_update_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        // {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
