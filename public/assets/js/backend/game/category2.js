define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'game/category2/index' + location.search,
                    add_url: 'game/category2/add',
                    edit_url: 'game/category2/edit',
                    del_url: 'game/category2/del',
                    multi_url: 'game/category2/multi',
                    import_url: 'game/category2/import',
                    table: 'game_category2',
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
                        {field: 'parent_id', title: __('Parent_id')},
                        {field: 'game_count', title: '游戏数量'},
                        {field: 'key', title: __('Key'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.toggle},
                        {field: 'sort_order', title: __('Sort_order')},
                        {field: 'created_at', title: __('Created_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'updated_at', title: __('Updated_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                            {
                                name: 'edit_game_list',
                                text: __('绑定游戏'),
                                title: __('绑定游戏'),
                                classname: 'btn btn-xs btn-success btn-dialog',
                                // icon: 'fa fa-list',
                                url: 'game/category/data?bindid={id}',
                                // refresh: true,
                                callback: function (data) {
                                    // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                },
                                visible: function (row) {
                                    return true;
                                }
                            }
                        ]}
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
