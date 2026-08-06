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
            lengthChange: false,
            lengthMenu: [
                [10, 20, 30, 40, 50, 100],
                [10, 20, 30, 40, 50, 100]
            ],
            stateSave: true,
            stateDuration: -1,
            stateSaveParams: function(settings, data) {
                // 不持久化排序状态：避免旧排序干扰，保证点击表头第一次即生效
                data.order = [];
            },
            stateLoadParams: function(settings, data) {
                data.start = 0;
                data.length = settings._iDisplayLength;
                if (data.columns && settings.aoColumns && data.columns.length !== settings.aoColumns.length) {
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
                // 首列若为多选框（th 内含 checkbox input）则禁止排序，避免勾选/点击触发排序
                var $firstTh = $(this).find('thead th:first');
                if ($firstTh.find('input[type=checkbox]').length) {
                    opts.columnDefs = opts.columnDefs || [];
                    opts.columnDefs.push({ targets: 0, orderable: false });
                }
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
    $('.alert-auto').delay(3000).fadeOut(500);

    // ===== 即时搜索：输入即客户端过滤 DataTables，无需刷新页面 =====
    $('input[name="search"]').each(function() {
        var $input = $(this);
        var $form = $input.closest('form');
        var timer = null;
        function getTable() {
            var $scope = $input.closest('.container-fluid');
            if (!$scope.length) $scope = $(document.body);
            var $t = $scope.find('table.datatable').filter(':visible').first();
            if (!$t.length) $t = $scope.find('table.datatable').first();
            return $t;
        }
        function apply() {
            var $t = getTable();
            if (!$t.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($t[0])) return;
            $t.DataTable().search($input.val().trim()).draw();
        }
        // 中文输入法组合期间不实时过滤，避免拼音片段干扰
        $input.on('compositionstart', function() { $(this).data('composing', true); });
        $input.on('compositionend', function() { $(this).data('composing', false); apply(); });
        $input.on('input', function() {
            if ($(this).data('composing')) return;
            clearTimeout(timer);
            timer = setTimeout(apply, 250);
        });
        // 纯搜索表单（无筛选下拉/隐藏项）拦截提交，回车/按钮不再整页刷新
        $form.on('submit', function(e) {
            if (!$form.find('select, input[type="hidden"]').length) {
                e.preventDefault();
                clearTimeout(timer);
                apply();
            }
        });
    });
});

// ===== 侧边栏二级菜单（accordion）与整体折叠（独立 ready，页面其他 JS 异常不影响侧边栏） =====
$(function() {
    var $body = $('body');
    var $sidebar = $('.navbar-vertical');

    function sidebarCollapsed() { return $body.hasClass('sidebar-collapsed'); }

    function resetSidebarMenu($menu) {
        $menu.css({ position: '', left: '', top: '', margin: '', zIndex: '' });
    }

    function closeAllSidebarSubs() {
        $sidebar.find('.dropdown-menu.show').removeClass('show').each(function() { resetSidebarMenu($(this)); });
        $sidebar.find('.nav-item.dropdown').find('[aria-expanded]').attr('aria-expanded', 'false');
    }

    function openSidebarSub($li) {
        var $menu = $li.find('.dropdown-menu').first();
        $menu.addClass('show');
        $li.find('[aria-expanded]').attr('aria-expanded', 'true');
        if (sidebarCollapsed()) {
            var rect = $li[0].getBoundingClientRect();
            $menu.css({
                position: 'fixed',
                left: rect.right + 'px',
                top: rect.top + 'px',
                margin: 0,
                zIndex: 1045
            });
        } else {
            resetSidebarMenu($menu);
        }
    }

    // 点击切换：展开态 = 手风琴；折叠态 = 点击总是打开飞出菜单（hover/移出负责关闭）
    $(document).on('click', '.navbar-vertical .dropdown-toggle[data-bs-toggle="sidebar-sub"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $li = $(this).closest('.nav-item.dropdown');
        var wasOpen = $li.find('.dropdown-menu').first().hasClass('show');
        if (sidebarCollapsed()) {
            closeAllSidebarSubs();
            openSidebarSub($li);
        } else {
            closeAllSidebarSubs();
            if (!wasOpen) openSidebarSub($li);
        }
    });

    // 折叠态：hover 打开飞出菜单，移开关闭（避免固定的飞出面板遮挡其他一级菜单图标）
    // 加 hover-intent 延迟：鼠标从图标移到飞出菜单要跨过间隙，直接 mouseleave 关闭会来不及到达
    var closeTimer = null;
    if ($sidebar.length) {
        $sidebar.on('mouseenter', '.nav-item.dropdown', function() {
            if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            if (!sidebarCollapsed()) return;
            closeAllSidebarSubs();
            openSidebarSub($(this));
        });
        $sidebar.on('mouseleave', '.nav-item.dropdown', function() {
            if (!sidebarCollapsed()) return;
            var $menu = $(this).find('.dropdown-menu').first();
            if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            closeTimer = setTimeout(function() { $menu.removeClass('show'); }, 250);
        });
        // 进入飞出菜单即取消关闭，鼠标可顺畅从图标移动到菜单
        $sidebar.on('mouseenter', '.dropdown-menu', function() {
            if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
        });
        $sidebar.on('mouseleave', '.dropdown-menu', function() {
            if (!sidebarCollapsed()) return;
            var $menu = $(this);
            if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            closeTimer = setTimeout(function() { $menu.removeClass('show'); }, 250);
        });
    }

    $(document).on('click', '.sidebar-collapse-toggle', function() {
        $body.toggleClass('sidebar-collapsed');
        closeAllSidebarSubs();
        try { localStorage.setItem('sidebar-collapsed', sidebarCollapsed() ? '1' : '0'); } catch (e) {}
    });
    try { if (localStorage.getItem('sidebar-collapsed') === '1') $body.addClass('sidebar-collapsed'); } catch (e) {}
    if (sidebarCollapsed()) closeAllSidebarSubs();
    $(window).on('resize', closeAllSidebarSubs);
});