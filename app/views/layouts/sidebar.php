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
        ]],
    'compliance' => ['icon' => 'fa-balance-scale', 'label' => '合规分析', 'url' => '/compliance', 'perm' => 'compliance.view',
        'children' => [
            ['label' => '分析看板', 'url' => '/compliance', 'perm' => 'compliance.view'],
            ['label' => '合规评估', 'url' => '/compliance/analyze', 'perm' => 'compliance.analyze'],
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
if (can('system.users') || can('system.roles') || can('system.config') || can('system.logs') || can('system.std_limit')) {
    $sysChildren = [];
    if (can('system.users')) $sysChildren[] = ['label' => '用户管理', 'url' => '/system/users'];
    if (can('system.roles')) $sysChildren[] = ['label' => '角色管理', 'url' => '/system/roles'];
    if (can('system.config')) $sysChildren[] = ['label' => '系统参数', 'url' => '/system/config'];
    if (can('system.logs')) $sysChildren[] = ['label' => '操作日志', 'url' => '/system/logs'];
    if (can('system.std_limit')) $sysChildren[] = ['label' => '内部标准限值', 'url' => '/system/internal-standard'];
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
<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <h1 class="navbar-brand navbar-brand-autodark">
      <a href="/dashboard">
        <i class="icon-zh-w"></i>
        <span class="sidebar-title">合规管理系统</span>
      </a>
    </h1>
    <div class="collapse navbar-collapse" id="navbar-menu">
      <ul class="navbar-nav pt-lg-3">
        <?php $cPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'); ?>
        <?php foreach ($menu as $k => $m): ?>
        <?php $hasChildren = !empty($m['children']); ?>
        <?php
        $mActive = menuOpen($m['url'])
            || ($k === 'material' && menuOpen('/homogeneous'))
            || ($k === 'regulation' && menuOpen('/substance'))
            || ($k === 'task' && menuOpen('/bpm'))
            || ($k === 'query' && menuOpen('/query'));
        $cActive = '';
        if ($hasChildren) {
            foreach ($m['children'] as $c) {
                if ($cPath === rtrim($c['url'], '/')) { $cActive = $c['url']; break; }
            }
            if ($cActive !== '' && $cActive === $m['url']) $mActive = true;
        }
        ?>
        <?php if ($hasChildren): ?>
        <li class="nav-item dropdown <?=$mActive ? 'active' : ''?>" data-sidebar-sub>
          <a class="nav-link dropdown-toggle" href="#navbar-<?=$k?>" data-bs-toggle="sidebar-sub" role="button" aria-expanded="<?=$mActive ? 'true' : 'false'?>" aria-haspopup="true">
            <span class="nav-link-icon d-md-none d-lg-inline-block">
              <i class="fas <?=h($m['icon'])?>"></i>
            </span>
            <span class="nav-link-title"><?=h($m['label'])?></span>
          </a>
          <div class="dropdown-menu <?=$mActive ? 'show' : ''?>">
            <?php foreach ($m['children'] as $c): ?>
            <a href="<?=h($c['url'])?>" class="dropdown-item <?=$cActive !== '' && $cActive === $c['url'] ? 'active' : ''?>"><?=h($c['label'])?></a>
            <?php endforeach; ?>
          </div>
        </li>
        <?php else: ?>
        <li class="nav-item <?=$mActive ? 'active' : ''?>">
          <a class="nav-link" href="<?=h($m['url'])?>">
            <span class="nav-link-icon d-md-none d-lg-inline-block">
              <i class="fas <?=h($m['icon'])?>"></i>
            </span>
            <span class="nav-link-title"><?=h($m['label'])?></span>
          </a>
        </li>
        <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</aside>
