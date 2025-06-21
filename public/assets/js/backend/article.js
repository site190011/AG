define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    const params = new URLSearchParams(location.search);
    const typeValue = params.get('type');

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'article/index' + location.search,
                    add_url: 'article/add?type=' + typeValue,
                    edit_url: 'article/edit',
                    del_url: 'article/del',
                    multi_url: 'article/multi',
                    import_url: 'article/import',
                    table: 'article',
                }
            });

            var table = $("#table");
            var typeSet = {
                'notice': '常规公告',
                'noticeRoll': '滚动公告',
                'events': '活动',
                'news' : '新闻',
                'manual' : '教程',
                'poster' : '海报',
            };
            var statusSet = {
                'draft': '未发布',
                'published': '已发布',
            };

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'author', title: __('Author'), operate: 'LIKE'},
                        {field: 'type', title: __('Type'), searchList: typeSet, formatter: Table.api.formatter.normal},
                        {field: 'views', title: __('Views')},
                        {field: 'status', title: __('Status'), searchList: statusSet, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'publishtime', title: __('Publishtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'coverimage', title: __('Coverimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'tags', title: __('Tags'), operate: 'LIKE', formatter: function (value, row, index) {
                        //     return Table.api.formatter.flag.call(this, value, row, index);
                        // }},
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
