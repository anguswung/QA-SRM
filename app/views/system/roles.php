<div class="page-header">
  <h5><i class="fas fa-user-tag"></i> 角色管理</h5>
  <div>
    <?php if (can('system.roles')): ?><button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#roleModal"><i class="fas fa-plus"></i> 新增角色</button><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th>名称</th><th>描述</th><th>权限数</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($roles as $r): $p = json_decode($r['permissions'] ?: '[]', true); $isSuper = in_array('*', $p); ?>
        <tr>
          <td><span class="badge badge-soft-info"><?=h($r['name'])?></span></td>
          <td><?=h($r['description'])?></td>
          <td><?=$isSuper ? '所有权限' : count($p) . ' 项'?></td>
          <td>
            <?php if (can('system.roles')): ?>
            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editRoleModal"
              data-id="<?=$r['id']?>" data-name="<?=h($r['name'])?>" data-desc="<?=h($r['description'])?>"
              data-perms='<?=$r['permissions']?>'><i class="fas fa-edit"></i></button>
            <?php endif; ?>
            <?php if (can('system.roles')): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>">
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

<?php $allPerms = require __DIR__ . '/../../../config/permissions.php'; ?>

<!-- Create Modal -->
<div class="modal fade" id="roleModal">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">新增角色</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-row">
          <div class="form-group col-md-6"><label>角色名称</label><input type="text" name="name" class="form-control" required></div>
          <div class="form-group col-md-6"><label>描述</label><input type="text" name="description" class="form-control"></div>
        </div>
        <div class="form-group"><label class="font-weight-bold">权限设置</label>
          <div class="border rounded p-3" style="max-height:400px;overflow-y:auto">
            <?php foreach ($allPerms as $group => $g): ?>
            <div class="mb-2">
              <label class="font-weight-bold text-primary small"><?=h($g['label'])?></label>
              <div class="ml-2">
                <?php foreach ($g['perms'] as $key => $label): ?>
                <label class="mr-3 mb-1" style="font-weight:400;cursor:pointer">
                  <input type="checkbox" name="perms[]" value="<?=$key?>" class="mr-1"> <?=h($label)?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">创建</button><button class="btn btn-secondary" data-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editRoleModal">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">编辑角色</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editId">
        <div class="form-row">
          <div class="form-group col-md-6"><label>角色名称</label><input type="text" name="name" id="editName" class="form-control" required></div>
          <div class="form-group col-md-6"><label>描述</label><input type="text" name="description" id="editDesc" class="form-control"></div>
        </div>
        <div class="form-group"><label class="font-weight-bold">权限设置</label>
          <div class="border rounded p-3" style="max-height:400px;overflow-y:auto">
            <?php foreach ($allPerms as $group => $g): ?>
            <div class="mb-2">
              <label class="font-weight-bold text-primary small"><?=h($g['label'])?></label>
              <div class="ml-2">
                <?php foreach ($g['perms'] as $key => $label): ?>
                <label class="mr-3 mb-1" style="font-weight:400;cursor:pointer">
                  <input type="checkbox" name="perms[]" value="<?=$key?>" class="perm-check mr-1"> <?=h($label)?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">保存</button><button class="btn btn-secondary" data-dismiss="modal">取消</button></div>
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
