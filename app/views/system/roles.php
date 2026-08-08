<div class="page-header">
  <h4><i class="fas fa-user-tag"></i> 角色管理</h4>
  <div>
    <?php if (can('system.roles')): ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal"><i class="fas fa-plus"></i> 新增角色</button><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/system/roles?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:14%">名称</th><th>描述</th><th style="width:10%">权限数</th><th style="width:12%">操作</th></tr></thead>
      <tbody>
        <tr><td colspan="5" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php $allPerms = require __DIR__ . '/../../../config/permissions.php'; ?>

<!-- Create Modal -->
<div class="modal fade" id="roleModal">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">新增角色</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="row g-3">
          <div class="mb-3 col-md-6"><label>角色名称</label><input type="text" name="name" class="form-control" required></div>
          <div class="mb-3 col-md-6"><label>描述</label><input type="text" name="description" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="fw-bold">权限设置</label>
          <div class="border rounded p-3 scrollbox">
            <?php foreach ($allPerms as $group => $g): ?>
            <div class="mb-2">
              <label class="fw-bold text-primary small"><?=h($g['label'])?></label>
              <div class="me-2">
                <?php foreach ($g['perms'] as $key => $label): ?>
                <label class="ms-3 mb-1 fw-normal" style="cursor:pointer">
                  <input type="checkbox" name="perms[]" value="<?=$key?>" class="ms-1"> <?=h($label)?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">创建</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editRoleModal">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">编辑角色</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editId">
        <div class="row g-3">
          <div class="mb-3 col-md-6"><label>角色名称</label><input type="text" name="name" id="editName" class="form-control" required></div>
          <div class="mb-3 col-md-6"><label>描述</label><input type="text" name="description" id="editDesc" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="fw-bold">权限设置</label>
          <div class="border rounded p-3 scrollbox">
            <?php foreach ($allPerms as $group => $g): ?>
            <div class="mb-2">
              <label class="fw-bold text-primary small"><?=h($g['label'])?></label>
              <div class="me-2">
                <?php foreach ($g['perms'] as $key => $label): ?>
                <label class="ms-3 mb-1 fw-normal" style="cursor:pointer">
                  <input type="checkbox" name="perms[]" value="<?=$key?>" class="perm-check ms-1"> <?=h($label)?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">保存</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>

<script>
$(function() {
  $('#editRoleModal').on('show.bs.modal', function(e) {
    var btn = $(e.relatedTarget);
    $('#editId').val(btn.data('id'));
    $('#editName').val(btn.data('name'));
    $('#editDesc').val(btn.data('desc'));
    var perms = btn.data('perms') || '[]';
    if (typeof perms === 'string') perms = JSON.parse(perms);
    $(this).find('.perm-check').each(function() {
      $(this).prop('checked', perms.indexOf($(this).val()) !== -1 || perms.indexOf('*') !== -1);
    });
  });
});
</script>
