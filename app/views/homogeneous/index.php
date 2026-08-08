<div class="page-header">
  <h4><i class="fas fa-flask"></i> 均质材料库</h4>
  <div>
    <?php if (can('homogeneous.create')): ?><a href="/homogeneous?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> 新建均质</a><?php endif; ?>
    <?php if (can('homogeneous.create')): ?><button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importHmModal"><i class="fas fa-upload"></i> 批量导入</button><?php endif; ?>
  </div>
</div>
<div class="row mb-3">
  <div class="col">
    <div class="card stat-card py-2 px-2 text-center">
      <div class="stat-value h5 mb-0 text-success"><?=h($total ?? 0)?></div>
      <div class="stat-label text-muted">均质原材料总数</div>
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
    <form method="get" class="d-flex flex-wrap align-items-center gap-2">
      <div class="input-group input-group-sm ms-2 w-260">
        <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
        <input type="text" name="search" class="form-control" placeholder="搜索名称..." value="<?=h($_GET['search'] ?? '')?>">
      </div>
      <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> 搜索</button>
      <a href="/homogeneous" class="btn-icon btn btn-sm btn-outline-secondary me-1" title="重置"><i class="fas fa-undo"></i></a>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/homogeneous?action=data">
      <thead>
        <tr>
          <th style="width:40px" class="col-id">ID</th>
          <th class="text-start">名称</th>
          <th width="100">性质</th>
          <?php if (!isSupplier()): ?><th width="80">供应商</th><?php endif; ?>
          <th width="70">用量</th>
          <th width="70">RoHS</th>
          <th width="70">REACH</th>
          <th width="70">HF</th>
          <th width="70">MSDS</th>
          <th width="70">环保状态</th>
          <th width="100" class="nowrap">审核状态</th>
          <th width="120">更新日期</th>
          <th width="200">操作</th>
        </tr>
      </thead>
      <tbody>
        <tr><td colspan="13" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="copyHmModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title"><i class="fas fa-copy ms-1"></i>复制均质材料</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="copy">
        <input type="hidden" name="source_id" id="copySourceId" value="">
        <div class="mb-3">
          <label>新均质名称 <span class="text-danger">*</span></label>
          <input type="text" name="new_name" id="copyNewName" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success"><i class="fas fa-copy ms-1"></i>复制</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<script>
function openCopyModal(id, name) {
  document.getElementById('copySourceId').value = id;
  document.getElementById('copyNewName').value = name + '(副本)';
  $('#copyHmModal').modal('show');
  setTimeout(function() { document.getElementById('copyNewName').focus(); }, 300);
}
document.getElementById('copyNewName').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    this.form.submit();
  }
});
</script>
<div class="modal fade" id="importHmModal">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">批量导入均质材料</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="import">
        <div class="alert alert-info py-2">
          <i class="fas fa-info-circle ms-1"></i> 下载 <a href="/material?action=hm_template" target="_blank">物料均质模板</a>，填写后上传
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
