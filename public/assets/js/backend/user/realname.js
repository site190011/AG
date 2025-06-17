define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/realname/index' + location.search,
                    add_url: 'user/realname/add',
                    edit_url: 'user/realname/edit',
                    del_url: 'user/realname/del',
                    multi_url: 'user/realname/multi',
                    import_url: 'user/realname/import',
                    table: 'user_realname',
                }
            });

            var table = $("#table");

            var statusSet = {
                '0': '待审核',
                '1': '通过',
                '2': '未通过'
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
                        {field: 'real_name', title: __('Real_name'), operate: 'LIKE'},
                        {field: 'id_card_number', title: __('Id_card_number'), operate: 'LIKE'},
                        {field: 'id_card_image1', title: __('Id_card_image1'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.image},
                        {field: 'id_card_image2', title: __('Id_card_image2'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.image},
                        {field: 'status', title: __('Status'), searchList: statusSet, formatter: Table.api.formatter.status},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
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
