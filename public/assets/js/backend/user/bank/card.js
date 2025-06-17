define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/bank/card/index' + location.search,
                    add_url: 'user/bank/card/add',
                    edit_url: 'user/bank/card/edit',
                    del_url: 'user/bank/card/del',
                    multi_url: 'user/bank/card/multi',
                    import_url: 'user/bank/card/import',
                    table: 'user_bank_card',
                }
            });

            var table = $("#table");
            var statusSet = {
                '0': '禁用',
                '1': '启用'
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
                        {field: 'card_number', title: __('Card_number'), operate: 'LIKE'},
                        {field: 'bank_name', title: __('Bank_name'), operate: 'LIKE'},
                        {field: 'card_holder', title: __('Card_holder'), operate: 'LIKE'},
                        {field: 'expiry_date', title: __('Expiry_date'), operate: 'LIKE'},
                        {field: 'cvv', title: __('Cvv'), operate: 'LIKE'},
                        {field: 'is_default', title: __('Is_default'), formatter: Table.api.formatter.toggle, events: Table.api.events.toggle},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.toggle, events: Table.api.events.toggle},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'delete_time', title: __('Delete_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
