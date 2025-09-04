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
                        {field: 'status', title: __('Status')},
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
            },
            formatter: {
                // 添加绑定游戏按钮
                operate: function (value, row, index) {
                    var table = this;
                    // // 操作配置
                    // var options = table.bootstrapTable('getOptions');
                    // 默认操作按钮
                    var buttons = $.extend([], this.buttons || []);
                    
                    // 添加绑定游戏按钮
                    buttons.push({
                        name: 'bind',
                        text: '绑定游戏',
                        title: '绑定游戏',
                        classname: 'btn btn-xs btn-primary btn-dialog',
                        icon: 'fa fa-link',
                        url: 'game/category/data/index?category2_id=' + row.id
                    });
                    
                    // 调用默认的操作按钮渲染
                    return Table.api.formatter.operate.call(table, value, row, index, buttons);
                }
            }
        }
    };
    return Controller;
});