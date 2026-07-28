<header class="app-topbar">
  <div class="topbar-left">
    <button class="btn btn-link sidebar-toggle" id="sidebarToggle">
      <i class="fas fa-bars"></i>
    </button>
    <span class="topbar-title"><?=h($title ?? '')?></span>
  </div>
  <div class="topbar-right">
    <span class="mr-2"><i class="fas fa-user-circle"></i> <?=h(isSupplier() ? ($_SESSION['supplier_name'] ?? userRealname()) : userRealname())?></span>
    <span class="badge badge-<?=isAdmin() ? 'danger' : 'info'?> mr-2"><?=isAdmin() ? '管理员' : '供应商'?></span>
    <a href="/password-change" class="btn btn-sm btn-outline-secondary" title="修改密码"><i class="fas fa-key"></i></a>
    <a href="/logout" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i> 退出</a>
  </div>
</header>
