<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-key"></i> 修改密码</div>
      <div class="card-body">
        <form method="post"><?=csrf_field()?>
          <div class="form-group">
            <label>原密码</label>
            <input type="password" name="old_password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>新密码</label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
          </div>
          <div class="form-group">
            <label>确认新密码</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
          </div>
          <button type="submit" class="btn btn-primary">确认修改</button>
          <a href="/dashboard" class="btn btn-secondary ml-2">返回</a>
        </form>
      </div>
    </div>
  </div>
</div>
