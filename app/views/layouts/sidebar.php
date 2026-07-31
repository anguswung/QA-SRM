<?php
$menu = [
    'dashboard' => ['icon' => 'fa-chart-pie', 'label' => '仪表盘', 'url' => '/dashboard', 'perm' => 'dashboard.view'],
    'material' => ['icon' => 'fa-boxes', 'label' => '物料管理', 'url' => '/material', 'perm' => 'material.view',
        'children' => [
            ['label' => '物料列表', 'url' => '/material', 'perm' => 'material.view'],
            ['label' => '均质材料库', 'url' => '/homogeneous', 'perm' => 'homogeneous.view'],
        ]],
    'report' => ['icon' => 'fa-file-alt', 'label' => '检测报告', 'url' => '/report', 'perm' => 'report.view',
        'children' => [
            ['label' => '报告列表', 'url' => '/report', 'perm' => 'report.view'],
            ['label' => '报告审核', 'url' => '/report/audit', 'perm' => 'report.audit'],
            ['label' => '物质含量', 'url' => '/report/elements', 'perm' => 'report.view'],
        ]],
    'compliance' => ['icon' => 'fa-balance-scale', 'label' => '合规分析', 'url' => '/compliance', 'perm' => 'compliance.view',
        'children' => [
            ['label' => '分析看板', 'url' => '/compliance', 'perm' => 'compliance.view'],
            ['label' => '合规评估', 'url' => '/compliance/analyze', 'perm' => 'compliance.analyze'],
            ['label' => '合规矩阵', 'url' => '/compliance/matrix', 'perm' => 'compliance.view'],
        ]],
    'document' => ['icon' => 'fa-file-alt', 'label' => '文档管理', 'url' => '/document/categories', 'perm' => 'document.view',
        'children' => [
            ['label' => '文档分类', 'url' => '/document/categories', 'perm' => 'document.view'],
        ]],
    'regulation' => ['icon' => 'fa-gavel', 'label' => '法规管理', 'url' => '/regulation', 'perm' => 'regulation.view',
        'children' => [
            ['label' => '法规库', 'url' => '/regulation', 'perm' => 'regulation.view'],
            ['label' => '物质库', 'url' => '/substance', 'perm' => 'substance.view'],
        ]],
    'supplier' => isSupplier()
        ? ['icon' => 'fa-truck', 'label' => '我的评级', 'url' => '/supplier/rating']
        : ['icon' => 'fa-truck', 'label' => '供应商管理', 'url' => '/supplier', 'perm' => 'supplier.view',
            'children' => [
                ['label' => '供应商列表', 'url' => '/supplier', 'perm' => 'supplier.view'],
                ['label' => '供应商评级', 'url' => '/supplier/rating', 'perm' => 'supplier.rating'],
            ]],
    'task' => ['icon' => 'fa-tasks', 'label' => '任务中心', 'url' => '/task', 'perm' => 'task.view',
        'children' => [
            ['label' => '我的任务', 'url' => '/task', 'perm' => 'task.view'],
        ]],
    'notice' => ['icon' => 'fa-bullhorn', 'label' => '通知公告', 'url' => '/notice', 'perm' => 'notice.view'],
    'survey' => ['icon' => 'fa-poll', 'label' => '问卷调查', 'url' => '/survey', 'perm' => 'survey.view'],
];
if (can('rule.view')) $menu['rule'] = ['icon' => 'fa-gavel', 'label' => '合规规则', 'url' => '/rule', 'perm' => 'rule.view'];
if (can('warning.view')) $menu['warning'] = ['icon' => 'fa-exclamation-triangle', 'label' => '预警中心', 'url' => '/warning', 'perm' => 'warning.view'];
if (can('query.view')) $menu['query'] = ['icon' => 'fa-search', 'label' => '查询报表', 'url' => '/query/material', 'perm' => 'query.view',
    'children' => [
        ['label' => '物料查询', 'url' => '/query/material', 'perm' => 'query.view'],
        ['label' => '到期查询', 'url' => '/query/expired', 'perm' => 'query.view'],
        ['label' => '变更历史', 'url' => '/query/history', 'perm' => 'query.view'],
    ]];
if (can('system.users') || can('system.roles') || can('system.config') || can('system.logs')) {
    $sysChildren = [];
    if (can('system.users')) $sysChildren[] = ['label' => '用户管理', 'url' => '/system/users'];
    if (can('system.roles')) $sysChildren[] = ['label' => '角色管理', 'url' => '/system/roles'];
    if (can('system.config')) $sysChildren[] = ['label' => '系统参数', 'url' => '/system/config'];
    if (can('system.logs')) $sysChildren[] = ['label' => '操作日志', 'url' => '/system/logs'];
    $menu['system'] = ['icon' => 'fa-cog', 'label' => '系统管理', 'url' => '/system', 'children' => $sysChildren];
}
// Filter out items user has no permission to view
foreach ($menu as $k => &$m) {
    if (isset($m['perm']) && !can($m['perm'])) { unset($menu[$k]); continue; }
    if (!empty($m['children'])) {
        foreach ($m['children'] as $ck => $c) {
            if (!empty($c['admin']) && isSupplier()) { unset($m['children'][$ck]); continue; }
            if (isset($c['perm']) && !can($c['perm'])) { unset($m['children'][$ck]); }
        }
        $m['children'] = array_values($m['children']);
        if (empty($m['children'])) { unset($menu[$k]); continue; }
    }
}
unset($m);
?>
<aside class="app-sidebar">
  <div class="sidebar-header">
    <i class="icon-zh-w"></i>
    <span class="sidebar-title">合规管理系统</span>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($menu as $k => $m): ?>
    <?php $hasChildren = !empty($m['children']); ?>
    <?php $isOpen = $hasChildren && (menuOpen($m['url']) || ($k === 'material' && menuOpen('/homogeneous')) || ($k === 'regulation' && menuOpen('/substance')) || ($k === 'document' && menuOpen('/document')) || ($k === 'task' && menuOpen('/bpm')) || ($k === 'query' && menuOpen('/query'))); ?>
    <div class="nav-item <?=$isOpen ? 'menu-open' : ''?>">
      <?php if ($hasChildren): ?>
      <span class="nav-link <?=menuOpen($m['url']) || ($k === 'material' && menuOpen('/homogeneous')) || ($k === 'regulation' && menuOpen('/substance')) || ($k === 'task' && menuOpen('/bpm')) || ($k === 'query' && menuOpen('/query')) ? 'active' : ''?> has-children">
        <i class="fas <?=h($m['icon'])?>"></i>
        <span><?=h($m['label'])?></span>
        <i class="fas fa-chevron-down arrow"></i>
      </span>
      <?php else: ?>
      <a href="<?=h($m['url'])?>" class="nav-link <?=menuOpen($m['url']) ? 'active' : ''?>">
        <i class="fas <?=h($m['icon'])?>"></i>
        <span><?=h($m['label'])?></span>
      </a>
      <?php endif; ?>
      <?php if ($hasChildren): ?>
      <div class="submenu <?=$isOpen ? 'open' : ''?>">
        <?php foreach ($m['children'] as $c): ?>
        <a href="<?=h($c['url'])?>" class="subnav-link <?=menuOpen($c['url']) ? 'active' : ''?>"><?=h($c['label'])?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </nav>
</aside>
<script>!function(){var s=document.querySelector('.sidebar-nav');if(!s)return;function p(e){while(e&&e.nodeType&&!e.classList.contains('nav-item'))e=e.parentNode;return e&&e.classList.contains('nav-item')?e:null}function c(e){var sub=e.querySelector('.submenu'),arr=e.querySelector('.nav-link .arrow');sub&&(sub.classList.remove('open'),sub.style.maxHeight=''),e.classList.remove('menu-open'),arr&&arr.classList.remove('rotated')}s.addEventListener('mousedown',function(e){var t=e.target.closest('.nav-link.has-children');if(!t)return;e.preventDefault();var n=p(t);if(!n)return;var o=n.classList.contains('menu-open'),al=s.querySelectorAll('.nav-item.menu-open');[].forEach.call(al,function(e){e!==n&&c(e)});if(o)c(n);else{n.classList.add('menu-open');var sb=n.querySelector('.submenu');sb&&(sb.classList.add('open'),sb.style.maxHeight=sb.scrollHeight+'px');var ar=t.querySelector('.arrow');ar&&ar.classList.add('rotated')}});s.addEventListener('click',function(e){e.target.closest('.nav-link.has-children')&&e.preventDefault()})}();</script>
