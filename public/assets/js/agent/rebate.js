define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'rebate/index' + location.search,
                    table: 'promotion_rebate_log',
                }
            });

            var table = $("#table");

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
                        {field: 'id', title: "ID"},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'bet_amount', title: '下注金额', operate:'BETWEEN'},
                        {field: 'rebate_amount', title: '得到返水金额', operate:'BETWEEN'},
                        {field: 'related_bet_ids', title: '相关数据', operate: 'LIKE', formatter: ($v, $row) => {
                            return `<div class="related_bet_ids">${$row.related_bet_ids}</div>`;
                        }},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
