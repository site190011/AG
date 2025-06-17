const { RFC_2822 } = require("moment");

define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'selectpage'], function ($, undefined, Backend, Table, Form) {

    jQuery("#c-game_name_orgin").data("params", function () {
        return {platType: jQuery("#c-plat_type").val()};
    });

    $("#c-game_name_orgin").data("eSelect", function(data){
        $('#c-game_type').val(data.gameType);
        $('#c-game_code').val(data.gameCode);
        $('#c-ingress').val(data.ingress);
        $('#c-game_name').val(data.gameName['zh-hans']);
    });

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'games/index' + location.search,
                    add_url: 'games/add',
                    edit_url: 'games/edit',
                    del_url: 'games/del',
                    multi_url: 'games/multi',
                    import_url: 'games/import',
                    table: 'games',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'sort',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'plat_type', 
                            title: __('Plat_type'), 
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
                        {field: 'game_type', title: __('Game_type'), operate: 'LIKE', formatter: function(volue, row) {
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
                        {field: 'game_code', title: __('Game_code'), operate: 'LIKE'},
                        {field: 'ingress', title: __('Ingress'), operate: 'LIKE', formatter: function(volue, row) {
                            const ingressList= {
                                1: '电脑网页',
                                2: '手机网页',
                                3: '电脑/手机网页'
                            };
                            return volue + "|" + ingressList[volue];
                        }},
                        {field: 'game_name', title: __('Game_name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'is_enable', title: __('Is_enable'), formatter: Table.api.formatter.toggle, events: Table.api.events.toggle},
                        {field: 'sort', title: __('Sort'), operate: false, formatter: function(value, row) {
                            return '<input type="number" style="min-width:50px" class="form-control input-sm" value="' + value + '" data-id="' + row.id + '">';
                        }, events: {
                            'change input[type="number"]': function(e, value, row) {
                                var params = {
                                    row: {
                                        id: row.id,
                                        sort: $(e.target).val()
                                    }
                                };
                                $.post('games/edit/ids/' + row.id, params, function(res) {
                                    if (res.code) {
                                        Toastr.success(res.msg || '已保存');
                                    }
                                });
                            }
                        }},
                        {field: 'is_recommend', title: __('Is_recommend'), formatter: Table.api.formatter.toggle, events: Table.api.events.toggle},
                        {field: 'created_at', title: __('Created_at')},
                        {field: 'updated_at', title: __('Updated_at')},
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
