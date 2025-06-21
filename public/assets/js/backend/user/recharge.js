define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/recharge/index' + location.search,
                    add_url: 'user/recharge/add',
                    edit_url: 'user/recharge/edit',
                    del_url: 'user/recharge/del',
                    multi_url: 'user/recharge/multi',
                    import_url: 'user/recharge/import',
                    table: 'user_recharge',
                }
            });

            var table = $("#table");
            var statusSet = {
                '0': '待处理',
                '1': '成功',
                '2': '失败'
            }

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
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), searchList: statusSet, formatter: Table.api.formatter.normal},
                        {field: 'pay_method', title: __('Pay_method'), operate: 'LIKE'},
                        {field: 'transaction_id', title: __('Transaction_id'), operate: 'LIKE'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            // Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"), function (data) {
                if (data) {
                    parent.Backend.api.open('user/recharge/edit/ids/' + data, '确认充值');
                }
            });
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
