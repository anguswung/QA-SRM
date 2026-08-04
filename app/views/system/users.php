<div class="page-header">
  <h5><i class="fas fa-users"></i> 用户管理</h5>
  <div>
    <?php if (can('system.users')): ?><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal"><i class="fas fa-plus"></i> 新增用户</button><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th>用户名</th><th>姓名</th><th>角色</th><th>邮箱</th><th>供应商</th><th>状态</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?=h($u['username'])?></td>
          <td><?=h($u['realname'])?></td>
          <td><span class="badge badge-soft-info"><?=h($u['role'])?></span></td>
          <td><?=h($u['email'] ?? '-')?></td>
          <td><small><?=h($u['supplier_name'] ?? '-')?></small></td>
          <td><?=statusBadge($u['status'])?></td>
          <td>
            <?php if (can('system.users')): ?>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal"
              data-id="<?=$u['id']?>" data-username="<?=h($u['username'])?>" data-realname="<?=h($u['realname'])?>"
              data-role="<?=$u['role']?>" data-email="<?=h($u['email'])?>" data-phone="<?=h($u['phone'])?>"
              data-supplier="<?=$u['supplier_id']?>" data-status="<?=$u['status']?>"><i class="fas fa-edit"></i></button>
            <?php endif; ?>
            <?php if (can('system.users') && $u['id'] !== userId()): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')"><?=csrf_field()?>
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$u['id']?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="userModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">新增用户</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label>用户名</label><input type="text" name="username" class="form-control" required></div>
        <div class="form-group"><label>密码</label><input type="password" name="password" class="form-control" value="123456"></div>
        <div class="form-group"><label>姓名</label><input type="text" name="realname" class="form-control" required></div>
        <div class="row">
          <div class="col-6">
            <div class="form-group"><label>角色</label>
              <select name="role" class="form-control">
                <?php foreach ($roles as $r): ?>
                <option value="<?=h($r['role'])?>"><?=h($r['role'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group"><label>状态</label>
              <select name="status" class="form-control">
              <option value="active">启用</option>
              <option value="locked">锁定</option>
              <option value="inactive">停用</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-group"><label>关联供应商</label>
          <select name="supplier_id" class="form-control">
            <option value="">无</option>
            <?php foreach ($suppliers as $s): ?>
            <option value="<?=$s['id']?>"><?=h($s['code'])?> - <?=h($s['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>邮箱</label><input type="email" name="email" class="form-control"></div>
        <div class="form-group"><label>电话</label><input type="text" name="phone" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">创建</button><button class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<div class="modal fade" id="editUserModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">编辑用户</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editId">
        <div class="form-group"><label>用户名</label><input type="text" id="editUsername" class="form-control" readonly></div>
        <div class="form-group"><label>新密码(留空不修改)</label><input type="password" name="password" class="form-control" placeholder="不修改请留空"></div>
        <div class="form-group"><label>姓名</label><input type="text" name="realname" id="editRealname" class="form-control" required></div>
        <div class="row">
          <div class="col-6">
            <div class="form-group"><label>角色</label>
              <select name="role" id="editRole" class="form-control">
                <?php foreach ($roles as $r): ?>
                <option value="<?=h($r['role'])?>"><?=h($r['role'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group"><label>状态</label>
              <select name="status" id="editStatus" class="form-control">
                <option value="active">启用</option>
                <option value="locked">锁定</option>
                <option value="inactive">停用</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-group"><label>关联供应商</label>
          <select name="supplier_id" id="editSupplier" class="form-control">
            <option value="">无</option>
            <?php foreach ($suppliers as $s): ?>
            <option value="<?=$s['id']?>"><?=h($s['code'])?> - <?=h($s['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>邮箱</label><input type="email" name="email" id="editEmail" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">保存</button><button class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<script>
$('#editUserModal').on('show.bs.modal', function(e){
  var btn = $(e.relatedTarget);
  $(this).find('#editId').val(btn.data('id'));
  $(this).find('#editUsername').val(btn.data('username'));
  $(this).find('#editRealname').val(btn.data('realname'));
  $(this).find('#editRole').val(btn.data('role'));
  $(this).find('#editEmail').val(btn.data('email'));
  $(this).find('#editSupplier').val(btn.data('supplier'));
  $(this).find('#editStatus').val(btn.data('status'));
});
</script>
