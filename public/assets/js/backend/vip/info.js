define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vip/info/index' + location.search,
                    add_url: '',
                    edit_url: 'vip/info/edit',
                    del_url: '',
                    multi_url: '',
                    import_url: '',
                    table: 'vip_config',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'level',
                sortOrder: 'asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'level', title: __('Level')},
                        {field: 'upgradeConsumed', title: __('UpgradeConsumed'), operate:'BETWEEN'},
                        {field: 'dailyWithdrawalCount', title: __('DailyWithdrawalCount')},
                        {field: 'dailyWithdrawalLimit', title: __('DailyWithdrawalLimit'), operate:'BETWEEN'},
                        {field: 'upgradeBonus', title: __('UpgradeBonus'), operate:'BETWEEN'},
                        {field: 'birthdayBonus', title: __('BirthdayBonus'), operate:'BETWEEN'},
                        {field: 'monthlyRedPacket', title: __('MonthlyRedPacket'), operate:'BETWEEN'},
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
