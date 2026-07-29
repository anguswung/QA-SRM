function calcPageLength() {
    return 20;
}

function paginateTable(table, perPage) {
    if (typeof $ === 'undefined') return;
    var $table = $(table);
    if ($table.find('tbody tr').length <= perPage) return;
    if ($table.closest('.dataTables_wrapper').length) return;
    $table.data('perPage', perPage);
    var $nav = $('<div class="table-pagination d-flex justify-content-center align-items-center mt-2"></div>');
    $table.after($nav);
    goPage($table, 0);
}

function goPage($table, page) {
    var perPage = $table.data('perPage');
    var $rows = $table.find('tbody tr');
    var total = $rows.length;
    var pages = Math.ceil(total / perPage);
    if (page < 0) page = 0;
    if (page >= pages) page = pages - 1;
    $rows.hide().slice(page * perPage, (page + 1) * perPage).show();
    var html = '<div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px">';
    html += '<div class="text-muted small">共 ' + total + ' 条</div>';
    html += '<nav><ul class="pagination pagination-sm mb-0">';
    html += '<li class="page-item' + (page === 0 ? ' disabled' : '') + '"><a class="page-link" href="javascript:;" onclick="goPageClick(this,' + perPage + ',' + (page - 1) + ')">上一页</a></li>';
    var start = Math.max(0, page - 2);
    var end = Math.min(pages - 1, page + 2);
    if (start > 0) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    for (var i = start; i <= end; i++)
        html += '<li class="page-item' + (i === page ? ' active' : '') + '"><a class="page-link" href="javascript:;" onclick="goPageClick(this,' + perPage + ',' + i + ')">' + (i + 1) + '</a></li>';
    if (end < pages - 1) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    html += '<li class="page-item' + (page >= pages - 1 ? ' disabled' : '') + '"><a class="page-link" href="javascript:;" onclick="goPageClick(this,' + perPage + ',' + (page + 1) + ')">下一页</a></li>';
    html += '</ul></nav>';
  html += '<div class="d-flex align-items-center" style="gap:6px;flex-shrink:0">';
  html += '<span style="white-space:nowrap;font-size:13px">跳转 <input type="number" min="1" max="' + pages + '" value="' + (page + 1) + '" style="width:48px;text-align:center;padding:2px 4px;border:1px solid #d1d5db;border-radius:3px;font-size:13px" onchange="gotoPage(this,' + perPage + ',' + pages + ')"> 页</span>';
    html += '<select class="form-control form-control-sm" onchange="changePerPage(this)" style="width:auto">';
    [10, 20, 30, 50, 100].forEach(function(v) {
        html += '<option value="' + v + '"' + (v === perPage ? ' selected' : '') + '>每页 ' + v + ' 条</option>';
    });
    html += '</select></div></div>';
    $table.next('.table-pagination').html(html);
}

function goPageClick(el, perPage, page) {
    var $table = $(el).closest('.table-pagination').prev('table');
    $table.data('perPage', perPage);
    goPage($table, page);
}

function changePerPage(el) {
    var val = parseInt($(el).val());
    var $table = $(el).closest('.table-pagination').prev('table');
    $table.data('perPage', val);
    goPage($table, 0);
}

function gotoPage(el, perPage, totalPages) {
    var p = parseInt($(el).val()) - 1;
    if (p < 0) p = 0;
    if (p >= totalPages) p = totalPages - 1;
    var $table = $(el).closest('.table-pagination').prev('table');
    $table.data('perPage', perPage);
    goPage($table, p);
}

$(function() {
    if (typeof $ === 'undefined') return;
    var pl = calcPageLength();
    if ($.fn.dataTable) {
        $.extend($.fn.dataTable.defaults, {
            language: {
                info: '共 _TOTAL_ 条，第 _START_-_END_ 条',
                infoEmpty: '共 0 条',
                infoFiltered: '（从 _MAX_ 条中筛选）',
                paginate: { first: '首页', last: '末页', next: '下一页', previous: '上一页' },
                zeroRecords: '没有找到匹配数据',
                emptyTable: '没有数据'
            },
            pageLength: pl,
            searching: false,
            lengthChange: false,
            lengthMenu: [
                [10, 20, 30, 40, 50, 100],
                [10, 20, 30, 40, 50, 100]
            ],
            stateSave: true,
            stateDuration: -1,
            stateLoadParams: function(settings, data) {
                data.start = 0;
                data.length = settings._iDisplayLength;
                if (data.columns && data.columns.length !== settings.aoColumns.length) {
                    data.columns = [];
                    data.order = [];
                }
            },
            drawCallback: function(settings) {
                var api = new $.fn.dataTable.Api(settings);
                var pageInfo = api.page.info();
                if (pageInfo.pages <= 1) return;
                var $table = $(settings.nTable);
                var paginate = $table.closest('.dataTables_wrapper').find('.dataTables_paginate');
                if (paginate.length && !paginate.find('.dt-goto-page').length) {
                    $('<span class="dt-goto-page" style="margin-left:12px;white-space:nowrap;display:inline-block;vertical-align:middle">跳转 <input type="number" min="1" max="' + pageInfo.pages + '" value="' + (pageInfo.page + 1) + '" style="width:48px;text-align:center;padding:2px 4px;border:1px solid #d1d5db;border-radius:3px;font-size:13px"> 页</span>').appendTo(paginate);
                    paginate.find('.dt-goto-page input').on('change keyup', function(e) {
                        if (e.type === 'keyup' && e.key !== 'Enter') return;
                        var p = parseInt($(this).val()) - 1;
                        if (p >= 0 && p < pageInfo.pages) api.page(p).draw(false);
                    });
                }
                paginate.find('.dt-goto-page input').val(pageInfo.page + 1).attr('max', pageInfo.pages);
            }
        });
        $('.datatable').each(function() {
            if ($.fn.DataTable && !$.fn.DataTable.isDataTable(this)) {
                var opts = $.extend({}, $(this).data('options'));
                opts.pageLength = pl;
                $(this).DataTable(opts);
            }
        });
    }
    $('table.table').each(function() {
        if ($(this).find('tbody tr').length > pl) paginateTable(this, pl);
    });
    $(window).on('resize', function() {
        if ($.fn.dataTable) {
            $('.datatable').each(function() {
                var dt = $.fn.DataTable && $.fn.DataTable.isDataTable(this) ? $(this).DataTable() : null;
                if (dt) dt.page.len(pl).draw(false);
            });
        }
        $('table.table').each(function() {
            if ($(this).closest('.dataTables_wrapper').length) return;
            if ($(this).find('tbody tr').length > pl) {
                var oldPage = 0;
                var $active = $(this).next('.table-pagination').find('.page-item.active .page-link');
                if ($active.length) oldPage = parseInt($active.text()) - 1;
                goPage($(this), oldPage);
            }
        });
    });
    $(document).on('click', '[data-confirm]', function(e) {
        if (!confirm($(this).data('confirm') || '确定执行此操作？')) e.preventDefault();
    });
    $('.alert-auto').delay(3000).fadeOut(500);
    $('#sidebarToggle').on('click', function() {
        $('body').toggleClass('sidebar-collapsed');
    });
    $(document).on('click', '.tree-toggle', function() {
        $(this).toggleClass('expanded');
        $(this).closest('.tree-node').find('.tree-children:first').toggleClass('open');
    });
    $(document).on('change', '.auto-submit', function() { $(this).closest('form').submit(); });
    $(document).on('click', '.tab-item', function() {
        $(this).closest('.tab-bar').find('.tab-item').removeClass('active');
        $(this).addClass('active');
        var target = $(this).data('target');
        if (target) $(target).show().siblings('.tab-pane').hide();
    });
    if ($.fn.select2) {
        $('.select2').each(function() {
            if ($(this).data('select2')) return;
            $(this).select2({ width: '100%', placeholder: $(this).attr('placeholder') || '请选择' });
        });
    }
});