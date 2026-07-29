<div class="page-header">
  <h5><i class="fas fa-boxes"></i> 物料管理</h5>
  <div>
     <?php if (can('material.create')): ?><a href="/material?action=create&type=part" class="btn btn-primary"><i class="fas fa-plus"></i> 新增物料</a><?php endif; ?>
     <?php if (can('material.create')): ?><button class="btn btn-success" data-toggle="modal" data-target="#importModal"><i class="fas fa-upload"></i> 批量导入</button><?php endif; ?>
  </div>
</div>
<div class="row mb-3">
  <div class="col">
    <div class="card stat-card py-2 px-2 text-center">
      <div class="stat-value h5 mb-0 text-primary"><?=h($total ?? 0)?></div>
      <div class="stat-label text-muted">物料总数</div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card py-2 px-2 text-center">
      <div class="stat-value h5 mb-0 text-success"><?=h($compliantCount ?? 0)?></div>
      <div class="stat-label text-muted">合规</div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card py-2 px-2 text-center">
      <div class="stat-value h5 mb-0 text-danger"><?=h($nonCompliantCount ?? 0)?></div>
      <div class="stat-label text-muted">不合规</div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card py-2 px-2 text-center">
      <div class="stat-value h5 mb-0 text-warning"><?=h($pendingCount ?? 0)?></div>
      <div class="stat-label text-muted">待评估</div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-body py-2">
    <form method="get" class="form-inline justify-content-between">
      <div class="d-flex align-items-center flex-wrap">
        <div class="input-group input-group-sm mr-2" style="width:220px">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search text-muted"></i></span></div>
          <input type="text" name="search" class="form-control" placeholder="搜索编号/名称" value="<?=h($_GET['search'] ?? '')?>">
        </div>

        <select name="env_status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
          <option value="">环保状态</option>
          <option value="compliant" <?=($_GET['env_status'] ?? '') === 'compliant' ? 'selected' : ''?>>合规</option>
          <option value="non_compliant" <?=($_GET['env_status'] ?? '') === 'non_compliant' ? 'selected' : ''?>>不合规</option>
          <option value="pending" <?=($_GET['env_status'] ?? '') === 'pending' ? 'selected' : ''?>>待评估</option>
        </select>
        <select name="audit_status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
          <option value="">审核状态</option>
          <option value="N" <?=($_GET['audit_status'] ?? '') === 'N' ? 'selected' : ''?>>待提交</option>
          <option value="S" <?=($_GET['audit_status'] ?? '') === 'S' ? 'selected' : ''?>>已提交</option>
          <option value="A" <?=($_GET['audit_status'] ?? '') === 'A' ? 'selected' : ''?>>已认证</option>
          <option value="R" <?=($_GET['audit_status'] ?? '') === 'R' ? 'selected' : ''?>>已驳回</option>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter"></i> 筛选</button>
        <a href="/material" class="btn btn-sm btn-outline-secondary ml-1"><i class="fas fa-undo"></i></a>
      </div>
      <small class="text-muted">共<?=count($materials)?> 条记录</small>
    </form>
  </div>
</div>
<div class="card mt-0">
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover datatable mb-0" data-options='{"order":[[0,"desc"]]}'>
      <thead class="thead-light">
        <tr>
          <?php if (isSupplier()): ?><th width="40"><input type="checkbox" id="checkAll" onclick="toggleAll(this)"></th><?php endif; ?>
          <th width="50">ID</th>
          <th width="150"><?php $cs=''; foreach($configs as $c){if($c['config_key']==='company_short_name'){$cs=$c['config_value'];break;}} echo h($cs.'物料编号'); ?></th>
          <th>名称</th>
          <th>供应商</th>
          <th width="80">单重(g)</th>
          <th width="100">环保状态</th>
          <th width="100">审核</th>
          <th width="120">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($materials as $m): ?>
        <tr>
          <?php if (isSupplier()): ?><td><input type="checkbox" class="mat-check" value="<?=$m['id']?>" <?=in_array($m['audit_status'], ['N','R']) ? '' : 'disabled'?>></td><?php endif; ?>
          <td class="text-muted"><?=h($m['id'])?></td>
          <td><?=h($m['material_no'])?></td>
          <td>
            <a href="/material?action=detail&id=<?=$m['id']?>" class="font-weight-bold"><?=h($m['material_name'])?></a>
          </td>
          <td><small><?=h($m['supplier_name'] ? ($m['supplier_name_short'] ?? $m['supplier_name']) : '-')?></small></td>
          <td><?=h($m['unit_weight'] ?? 0)?> g</td>
          <td><?=statusBadge($m['env_status'])?></td>
          <td class="text-center"><?=statusBadge($m['audit_status'])?></td>
          <td>
            <div class="btn-group btn-group-sm">
              <a href="/material?action=detail&id=<?=$m['id']?>" class="btn btn-outline-info" title="查看"><i class="fas fa-eye"></i></a>
              <?php if (can('material.edit')): ?><a href="/material?action=edit&id=<?=$m['id']?>" class="btn btn-outline-primary" title="编辑"><i class="fas fa-pen"></i></a><?php endif; ?>
              <?php if (isSupplier() && in_array($m['audit_status'], ['N','R'])): ?>
              <button class="btn btn-outline-success" title="提交审核" onclick="if(confirm('确定提交审核？')){var f=document.createElement('form');f.method='post';f.innerHTML='<input name=action value=submit_audit><input name=id value=<?=$m['id']?>>';document.body.appendChild(f);f.submit()}"><i class="fas fa-paper-plane"></i></button>
              <?php endif; ?>
              <?php if (isAdmin() && $m['audit_status'] === 'S' && can('material.audit')): ?>
              <button class="btn btn-outline-success" title="通过" data-toggle="modal" data-target="#auditModal" data-id="<?=$m['id']?>" data-action="A"><i class="fas fa-check"></i></button>
              <button class="btn btn-outline-danger" title="驳回" data-toggle="modal" data-target="#auditModal" data-id="<?=$m['id']?>" data-action="R"><i class="fas fa-times"></i></button>
              <?php endif; ?>
              <?php if (can('material.delete')): ?><button class="btn btn-outline-danger" title="删除" onclick="if(confirm('确认删除？')){var f=document.createElement('form');f.method='post';f.innerHTML='<input name=action value=delete><input name=id value=<?=$m['id']?>>';document.body.appendChild(f);f.submit()}"><i class="fas fa-trash"></i></button><?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($materials)): ?>
        <tr><td colspan="7" class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>暂无物料数据</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <?php if (isSupplier()): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span id="selectedCount" class="text-muted"></span>
      <button type="button" class="btn btn-success" onclick="batchSubmitAudit()"><i class="fas fa-paper-plane mr-1"></i> 批量提交审核</button>
    </div>
    <?php endif; ?>
  </div>
</div>
<div class="modal fade" id="importModal">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">批量导入物料</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="import">
        <div class="alert alert-info py-2">
          <i class="fas fa-info-circle mr-1"></i> 下载 <a href="/material?action=import_template" target="_blank">导入模板</a>，填写后上传<br>
          <small class="text-muted">层级模板：同一物料下多个均质材料，同一均质材料下多个元素。空白物料行表示延续上一物料</small>
        </div>
        <div class="form-group">
          <label>上传Excel文件</label>
          <input type="file" name="import_file" class="form-control-file" accept=".xls,.xlsx" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">导入</button>
        <button class="btn btn-secondary" data-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="auditModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">物料审核</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="audit">
        <input type="hidden" name="id" id="auditId">
        <input type="hidden" name="audit_status" id="auditStatus">
        <div class="form-group"><label>审核意见</label><textarea name="audit_comment" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">确认</button>
        <button class="btn btn-secondary" data-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<script>
$('#auditModal').on('show.bs.modal', function(e){
  var btn = $(e.relatedTarget);
  $(this).find('#auditId').val(btn.data('id'));
  $(this).find('#auditStatus').val(btn.data('action'));
});

function toggleAll(el) {
  document.querySelectorAll('.mat-check:not(:disabled)').forEach(function(cb) { cb.checked = el.checked; });
  updateSelectedCount();
}

document.querySelectorAll('.mat-check').forEach(function(cb) {
  cb.addEventListener('change', function() {
    var all = document.querySelectorAll('.mat-check');
    var checked = document.querySelectorAll('.mat-check:checked');
    document.getElementById('checkAll').checked = all.length === checked.length;
    updateSelectedCount();
  });
});

function updateSelectedCount() {
  var n = document.querySelectorAll('.mat-check:checked').length;
  var el = document.getElementById('selectedCount');
  if (el) el.textContent = n > 0 ? '已选择 ' + n + ' 项' : '';
}

function batchSubmitAudit() {
  var ids = [];
  document.querySelectorAll('.mat-check:checked').forEach(function(cb) { ids.push(cb.value); });
  if (ids.length === 0) { alert('请先选择物料'); return; }
  if (!confirm('确定提交选中的 ' + ids.length + ' 项物料审核？')) return;
  var f = document.createElement('form');
  f.method = 'post';
  f.innerHTML = '<input name="action" value="batch_submit_audit"><input name="ids" value="' + ids.join(',') + '">';
  document.body.appendChild(f);
  f.submit();
}
</script>
