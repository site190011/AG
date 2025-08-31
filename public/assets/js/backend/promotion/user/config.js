define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'promotion/user/config/index' + location.search,
                    // add_url: 'promotion/user/config/add',
                    // edit_url: 'promotion/user/config/edit',
                    // del_url: 'promotion/user/config/del',
                    // multi_url: 'promotion/user/config/multi',
                    // import_url: 'promotion/user/config/import',
                    table: 'promotion_user_config',
                }
            });

            var table = $("#table");

            var formatter = {
                rebate: function (value, row, index) {
                    return value + "%";
                }
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
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'pid', title: __('Pid') },
                        { field: 'user_id', title: __('用户ID') },
                        {
                            field: 'user_id', title: '用户名', formatter: function (value, row, index) {
                                return '<a href="javascript:;" class="text-primary edit-user" data-id="' + row.user_id + '">' + row.user_name + '</a>';
                            }
                        },
                        { field: 'rebate1', title: __('Rebate1'), formatter: formatter.rebate },
                        { field: 'rebate2', title: __('Rebate2'), formatter: formatter.rebate },
                        { field: 'rebate3', title: __('Rebate3'), formatter: formatter.rebate },
                        { field: 'rebate4', title: __('Rebate4'), formatter: formatter.rebate },
                        { field: 'rebate5', title: __('Rebate5'), formatter: formatter.rebate },
                        { field: 'rebate6', title: __('Rebate6'), formatter: formatter.rebate },
                        { field: 'rebate7', title: __('Rebate7'), formatter: formatter.rebate },
                        { field: 'parent_remark', title: __('Parent_remark'), operate: 'LIKE' },
                        { field: 'kefuUrl', title: __('KefuUrl'), operate: 'LIKE'},
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            table.on('click', '.edit-user', function () {
                var id = $(this).data('id');
                var editUrl = 'user/user/edit/ids/' + id;
                Fast.api.open(editUrl, '用户信息');
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
