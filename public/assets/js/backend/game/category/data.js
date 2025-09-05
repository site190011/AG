define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'game/category/data/index' + location.search,
                    // add_url: 'game/category/data/add',
                    // edit_url: 'game/category/data/edit',
                    // del_url: 'game/category/data/del',
                    multi_url: 'game/category/data/multi',
                    // import_url: 'game/category/data/import',
                    table: 'game_category_data',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'game_id',
                sortName: 'fa_games.id',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'game_id', title: __('Id')},
                        {field: 'plat_type', 
                            title: '平台', 
                            operate: '=', 
                            class: 'selectpage', 
                            extend: [
                                'data-source="/api/games/getPlatTypeList"',
                                'data-field="name"',
                                'data-primary-key="key"',
                                'data-select-only="true"',
                                'data-pagination="true"'
                            ].join(' '),
                            formatter: function(value, row) {
                                return value + "|" + row.game_name;
                            }},
                        {field: 'game_type', title: '类型', operate: 'LIKE', formatter: function(volue, row) {
                            const typeList= {
                                1: '真人',
                                2: '电子',
                                3: '彩票',
                                4: '体育',
                                5: '电竞',
                                6: '捕鱼',
                                7: '棋牌'
                            };
                            return volue + "|" + typeList[volue];
                        }},
                        {field: 'game_code', title: 'CODE', operate: 'LIKE'},
                        {field: 'ingress', title: 'Ingress', operate: 'LIKE', formatter: function(volue, row) {
                            const ingressList= {
                                1: '电脑网页',
                                2: '手机网页',
                                3: '电脑/手机网页'
                            };
                            return volue + "|" + ingressList[volue];
                        }},
                        {field: 'game_name', title: '游戏名', operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'bind', title: '绑定', formatter: Table.api.formatter.toggle},
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
