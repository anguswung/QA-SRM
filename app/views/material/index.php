<div class="page-header">
  <h4><i class="fas fa-boxes"></i> 物料管理</h4>
  <div>
     <?php if (can('material.create')): ?><a href="/material?action=create&type=part" class="btn btn-primary"><i class="fas fa-plus"></i> 新增物料</a><?php endif; ?>
     <?php if (can('material.create')): ?><button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-upload"></i> 批量导入</button><?php endif; ?>
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
    <form method="get" class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
      <div class="d-flex align-items-center flex-wrap gap-2">
        <div class="input-group input-group-sm w-220">
          <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
          <input type="text" name="search" class="form-control" placeholder="搜索编号/名称" value="<?=h($_GET['search'] ?? '')?>" oninput="this.form.submit()">
        </div>

        <select name="env_status" class="form-control form-control-sm w-auto" onchange="this.form.submit()">
          <option value="">环保状态</option>
          <option value="compliant" <?=($_GET['env_status'] ?? '') === 'compliant' ? 'selected' : ''?>>合规</option>
          <option value="non_compliant" <?=($_GET['env_status'] ?? '') === 'non_compliant' ? 'selected' : ''?>>不合规</option>
          <option value="pending" <?=($_GET['env_status'] ?? '') === 'pending' ? 'selected' : ''?>>待评估</option>
        </select>
        <select name="audit_status" class="form-control form-control-sm w-auto" onchange="this.form.submit()">
          <option value="">审核状态</option>
          <option value="N" <?=($_GET['audit_status'] ?? '') === 'N' ? 'selected' : ''?>>待提交</option>
          <option value="S" <?=($_GET['audit_status'] ?? '') === 'S' ? 'selected' : ''?>>已提交</option>
          <option value="A" <?=($_GET['audit_status'] ?? '') === 'A' ? 'selected' : ''?>>已认证</option>
          <option value="R" <?=($_GET['audit_status'] ?? '') === 'R' ? 'selected' : ''?>>已驳回</option>
        </select>
        <?php if (!isSupplier()): ?>
        <select name="supplier_id" class="form-control form-control-sm w-auto" onchange="this.form.submit()">
          <option value="">按供应商筛选</option>
          <?php foreach ($suppliers ?? [] as $s): ?>
            <option value="<?=(int)$s['id']?>" <?=(string)($_GET['supplier_id'] ?? '') === (string)(int)$s['id'] ? 'selected' : ''?>><?=h($s['name'])?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <a href="/material" class="btn btn-sm btn-outline-secondary btn-icon" title="重置"><i class="fas fa-undo"></i></a>
      </div>
      <small class="text-muted">共 <span class="server-total">0</span> 条记录</small>
    </form>
  </div>
</div>
<div class="card mt-3">
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" data-server="1" data-url="/material?action=data">
      <thead>
        <tr>
          
          <th width="40"><input type="checkbox" id="checkAll" onclick="toggleAll(this)"></th>
          <th style="width:40px" class="col-id">ID</th>
          <th width="150"><?php $cs=''; foreach($configs as $c){if($c['config_key']==='company_short_name'){$cs=$c['config_value'];break;}} echo h($cs.'物料编号'); ?></th>
          <th class="text-start">名称</th>
          <?php if (!isSupplier()): ?><th width="80">供应商</th><?php endif; ?>
          <th width="80">单重(g)</th>
          <th width="100">环保状态</th>
          <th width="100">审核</th>
          <th width="180">操作</th>
        </tr>
      </thead>
      <tbody>
        <tr><td colspan="9" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span id="selectedCount" class="text-muted"></span>
      <button type="button" id="batchSubmitBtn" class="btn btn-success" onclick="batchSubmitAudit()" disabled><i class="fas fa-paper-plane ms-1"></i> 批量提交审核</button>
    </div>
  </div>
</div>
<div class="modal fade" id="importModal">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">批量导入物料</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="import">
        <div class="alert alert-info py-2">
          <div class="text-secondary">
            <i class="fas fa-info-circle me-1"></i> 下载 <a href="/material?action=import_template" target="_blank">导入模板</a>，填写后上传
            <div class="mt-1"><small class="text-muted">模板表头：物料编号、物料名称、供应商编号/名称、MPN、单位重量(g)、单位。仅需填写物料基础信息，成分与均质材料请通过物料编辑页添加。供应商支持填编号或名称（如"001 / 艾迈斯"）。</small></div>
          </div>
        </div>
        <div class="mb-3">
          <label>上传Excel文件</label>
          <input type="file" name="import_file" class="form-control" accept=".xls,.xlsx" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">导入</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="auditModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">物料审核</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="audit">
        <input type="hidden" name="id" id="auditId">
        <input type="hidden" name="audit_status" id="auditStatus">
        <div class="mb-3"><label>审核意见</label><textarea name="audit_comment" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">确认</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
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

document.addEventListener('change', function(e) {
  if (!e.target || e.target.id === 'checkAll') return;
  if (e.target.classList && e.target.classList.contains('mat-check')) {
    var all = document.querySelectorAll('.mat-check');
    var checked = document.querySelectorAll('.mat-check:checked');
    var checkAll = document.getElementById('checkAll');
    if (checkAll) checkAll.checked = all.length > 0 && all.length === checked.length;
    updateSelectedCount();
  }
});

var _matBody = document.querySelector('table[data-server="1"] tbody');
if (_matBody) {
  new MutationObserver(function() {
    if (!document.querySelectorAll('.mat-check:checked').length) updateSelectedCount();
  }).observe(_matBody, { childList: true, subtree: true });
}

function updateSelectedCount() {
  var n = document.querySelectorAll('.mat-check:checked').length;
  var el = document.getElementById('selectedCount');
  if (el) el.textContent = n > 0 ? '已选择 ' + n + ' 项' : '';
  var btn = document.getElementById('batchSubmitBtn');
  if (btn) btn.disabled = n === 0;
}

function batchSubmitAudit() {
  var ids = [];
  document.querySelectorAll('.mat-check:checked').forEach(function(cb) { ids.push(cb.value); });
  if (ids.length === 0) { alert('请先选择物料'); return; }
  if (!confirm('确定提交选中的 ' + ids.length + ' 项物料审核？')) return;
  var f = document.createElement('form');
  f.method = 'post';
  f.innerHTML = '<input name="action" value="batch_submit_audit"><input name="ids" value="' + ids.join(',') + '"><input name="_csrf_token" value="' + getCsrfToken() + '">';
  document.body.appendChild(f);
  f.submit();
}
</script>
