<div class="login-page">
  <div class="login-card">
    <div class="login-header">
      <i class="icon-zh-w"></i>
      <h3><?=h(DB::getInstance()->single("SELECT config_value FROM system_config WHERE config_key='system_name'", [], false) ?: '绿色产品管理系统GPM')?></h3>
      <p>Supplier Material Compliance Management System</p>
    </div>
    <div class="login-body">
      <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-auto"><?=h($error)?></div>
      <?php endif; ?>
      <?php $loginError = $_COOKIE['login_error'] ?? ''; if ($loginError): ?>
      <div class="alert alert-danger alert-auto"><?=h($loginError)?><script>setTimeout(function(){document.cookie='login_error=; path=/; max-age=0'},3000)</script></div>
      <?php endif; ?>
      <form method="post">
        <?=csrf_field()?>
        <div class="mb-3">
          <label><i class="fas fa-user"></i> 用户名</label>
          <input type="text" name="username" class="form-control" placeholder="请输入用户名" required autofocus>
        </div>
        <div class="mb-3">
          <label><i class="fas fa-lock"></i> 密码</label>
          <input type="password" name="password" class="form-control" placeholder="请输入密码" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg d-block w-100">登 录</button>
      </form>
      <div class="text-center mt-3 text-muted-light">
        <p class="mb-0">默认管理员 admin / 123456</p>
        <p class="mb-0">供应商登录 使用三位编码作为账号和密码</p>
      </div>
    </div>
  </div>
</div>
