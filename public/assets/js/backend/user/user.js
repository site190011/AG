define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'money', title: '余额', operate: 'BETWEEN', sortable: true},
                        {field: 'pid', title: '上级ID', visible: false},
                        {field: 'email', title: __('Email'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'birthday', title: __('Birthday'), operate: 'LIKE'},
                        {field: 'viplevel', title: __('Level'), operate: 'BETWEEN', sortable: true},
                        {field: 'successions', title: __('Successions'), visible: false, operate: 'BETWEEN', sortable: true},
                        {field: 'maxsuccessions', title: __('Maxsuccessions'), visible: false, operate: 'BETWEEN', sortable: true},
                        {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},
                        {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
                        
                        {field: 'agent_promotion', title: '是否合营代理', formatter: Table.api.formatter.toggle, events: Table.api.events.toggle},

                        {field: 'agent_promotion', title: '合营代理配置', table: table, formatter: Table.api.formatter.buttons, buttons: [
                            {
                                name: 'agent_promotion_config',
                                text: '配置',
                                title: '配置',
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                icon: 'fa fa-list',
                                url: function(row) {
                                    return 'user/user/promotion_user_config?uid=' + row.id;
                                },
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                },
                                visible: function (row) {
                                    //返回true时按钮显示,返回false隐藏
                                    return row.agent_promotion;
                                }
                            },
                            {
                                name: 'agent_promotion_subuser',
                                text: '下级',
                                title: '下级',
                                classname: 'btn btn-xs btn-primary',
                                icon: 'fa fa-list',
                                url: function(row) {
                                    return '?pid=' + row.id;
                                },
                                callback: function (data) {
                                },
                                visible: function (row) {
                                    //返回true时按钮显示,返回false隐藏
                                    return row.agent_promotion;
                                }
                            }
                        ]},

                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons:  [
                                {
                                    name: 'edit_money',
                                    text: __('加减款'),
                                    title: __('加减款'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'user/user/edit_money?id={id}',
                                    // refresh: true,
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        return true;
                                    }
                                },
                                  {
                                    name: 'tikuan',
                                    text: __('提款账号'),
                                    title: __('提款账号'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'user/bank.card/index?user_id={id}',
                                    // refresh: true,
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        return true;
                                    }
                                },
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
        edit_money: function () {
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