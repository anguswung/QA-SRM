<header class="navbar navbar-expand-lg navbar-light d-print-none">
  <div class="container-xl">
    <button class="btn btn-icon btn-ghost sidebar-collapse-toggle" type="button" title="折叠/展开侧边栏" aria-label="折叠/展开侧边栏">
      <i class="fas fa-bars"></i>
    </button>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-mobile" aria-controls="navbar-mobile" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-nav flex-row order-md-last">
      <div class="nav-item d-none d-md-flex me-2">
        <div class="nav-link d-flex align-items-center gap-2">
          <i class="fas fa-user-circle"></i>
          <span><?=h(isSupplier() ? ($_SESSION['supplier_name'] ?? userRealname()) : userRealname())?></span>
        </div>
        <span class="badge bg-<?=isAdmin() ? 'danger' : 'primary'?> text-white align-self-center"><?=isAdmin() ? '管理员' : '供应商'?></span>
      </div>
      <a href="/password-change" class="nav-link px-2 d-flex align-items-center gap-1" title="修改密码">
        <i class="fas fa-key"></i><span>修改密码</span>
      </a>
      <a href="/logout" class="nav-link px-2 d-flex align-items-center gap-1 text-danger" title="退出登录">
        <i class="fas fa-sign-out-alt"></i><span>退出登录</span>
      </a>
    </div>
    <div class="collapse navbar-collapse" id="navbar-mobile">
      <div class="navbar-nav">
        <span class="navbar-text topbar-title"><?=h($title ?? '')?></span>
      </div>
    </div>
  </div>
</header>
