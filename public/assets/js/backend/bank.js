define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bank/index' + location.search,
                    add_url: 'bank/add',
                    edit_url: 'bank/edit',
                    del_url: 'bank/del',
                    multi_url: 'bank/multi',
                    import_url: 'bank/import',
                    table: 'bank',
                }
            });

            var typeList = {
                'bank': '银行卡',
                'virtual': '虚拟币',
                'third': '第三方支付'
            };

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
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'type', title: __('Type'), operate: 'LIKE', formatter: Table.api.formatter.status, searchList: typeList},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'short_name', title: __('Short_name'), operate: 'LIKE'},
                        {field: 'bank_code', title: __('Bank_code'), operate: 'LIKE'},
                        {field: 'logo', title: __('Logo'), operate: 'LIKE', table: table, class: 'table-img', formatter: Table.api.formatter.image},
                        {field: 'sort', title: __('Sort')},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.toggle, events: Table.api.events.toggle},
                        {field: 'is_recharge_scope', title: '用于充值', formatter: Table.api.formatter.toggle, events: Table.api.events.toggle},
                        {field: 'is_withdraw_scope', title: '用于提现', formatter: Table.api.formatter.toggle, events: Table.api.events.toggle},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
