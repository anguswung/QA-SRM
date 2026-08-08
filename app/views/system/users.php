<div class="page-header">
  <h4><i class="fas fa-users"></i> 用户管理</h4>
  <div>
    <?php if (can('system.users')): ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"><i class="fas fa-plus"></i> 新增用户</button><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
<table id="usersTable" class="table table-hover mb-0" data-server="1" data-url="/system/users?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:12%">用户名</th><th class="col-realname">姓名</th><th style="width:9%">角色</th><th>邮箱</th><th style="width:14%">供应商</th><th style="width:8%">状态</th></tr></thead>
      <tbody>
        <tr><td colspan="7" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="userModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">新增用户</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="mb-3"><label>用户名</label><input type="text" name="username" class="form-control" required></div>
        <div class="mb-3"><label>密码</label><input type="password" name="password" class="form-control" value="123456"></div>
        <div class="mb-3"><label>姓名</label><input type="text" name="realname" class="form-control" required></div>
        <div class="row">
          <div class="col-6">
            <div class="mb-3"><label>角色</label>
              <select name="role" class="form-control">
                <?php foreach ($roles as $r): ?>
                <option value="<?=h($r['role'])?>"><?=h($r['role'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="mb-3"><label>状态</label>
              <select name="status" class="form-control">
              <option value="active">启用</option>
              <option value="locked">锁定</option>
              <option value="inactive">停用</option>
              </select>
            </div>
          </div>
        </div>
        <div class="mb-3"><label>关联供应商</label>
          <select name="supplier_id" class="form-control">
            <option value="">无</option>
            <?php foreach ($suppliers as $s): ?>
            <option value="<?=$s['id']?>"><?=h($s['code'])?> - <?=h($s['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label>邮箱</label><input type="email" name="email" class="form-control"></div>
        <div class="mb-3"><label>电话</label><input type="text" name="phone" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">创建</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<div class="modal fade" id="editUserModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">编辑用户</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editId">
        <div class="mb-3"><label>用户名</label><input type="text" id="editUsername" class="form-control" readonly></div>
        <div class="mb-3"><label>新密码(留空不修改)</label><input type="password" name="password" class="form-control" placeholder="不修改请留空"></div>
        <div class="mb-3"><label>姓名</label><input type="text" name="realname" id="editRealname" class="form-control" required></div>
        <div class="row">
          <div class="col-6">
            <div class="mb-3"><label>角色</label>
              <select name="role" id="editRole" class="form-control">
                <?php foreach ($roles as $r): ?>
                <option value="<?=h($r['role'])?>"><?=h($r['role'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="mb-3"><label>状态</label>
              <select name="status" id="editStatus" class="form-control">
                <option value="active">启用</option>
                <option value="locked">锁定</option>
                <option value="inactive">停用</option>
              </select>
            </div>
          </div>
        </div>
        <div class="mb-3"><label>关联供应商</label>
          <select name="supplier_id" id="editSupplier" class="form-control">
            <option value="">无</option>
            <?php foreach ($suppliers as $s): ?>
            <option value="<?=$s['id']?>"><?=h($s['code'])?> - <?=h($s['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label>邮箱</label><input type="email" name="email" id="editEmail" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">保存</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<script>
$('#usersTable').on('click', 'tbody tr', function(e){
  if ($(e.target).closest('a').length) return;
  var $t = $(this);
  var m = $('#editUserModal');
  m.find('#editId').val($t.data('id'));
  m.find('#editUsername').val($t.data('username'));
  m.find('#editRealname').val($t.data('realname'));
  m.find('#editRole').val($t.data('role'));
  m.find('#editEmail').val($t.data('email'));
  m.find('#editSupplier').val($t.data('supplier'));
  m.find('#editStatus').val($t.data('status'));
  m.modal('show');
});
</script>
