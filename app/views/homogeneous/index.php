<div class="page-header">
  <h4><i class="fas fa-flask"></i> 均质材料库</h4>
  <div>
    <?php if (can('homogeneous.create')): ?><a href="/homogeneous?action=create" class="btn btn-success"><i class="fas fa-plus"></i> 新建均质</a><?php endif; ?>
    <?php if (can('homogeneous.create')): ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importHmModal"><i class="fas fa-upload"></i> 批量导入</button><?php endif; ?>
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
      <a href="/homogeneous" class="btn-icon btn btn-sm btn-outline-secondary me-1"><i class="fas fa-undo"></i></a>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($materials)): ?>
    <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>暂无均质原材料</div>
    <?php else: ?>
    <table class="table table-hover datatable mb-0" data-options='{"order":[[1,"desc"]]}'>
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
<?php $__rnum = 0; ?>
        <?php foreach ($materials as $m): ?>
        <tr>
          <td class="text-muted col-id"><?=h($m['id'])?></td>
          <td class="text-start"><a class="fw-bold text-primary" href="/homogeneous?action=usage&id=<?=$m['id']?>" title="查看使用该均质材料的物料"><?=h($m['material_name'])?></a></td>
          <td><span class="badge badge-soft-success">均质原材料</span></td>
          <?php if (!isSupplier()): ?><td><small><?=h($m['supplier_name_short'] ?: ($m['supplier_name'] ?? '-'))?></small></td><?php endif; ?>
          <td><?=h($m['weight_mg'] ?? '-')?> <?=h($m['weight_unit'] ?? 'g')?></td>
          <?php foreach (['RoHS','REACH','HF','MSDS'] as $rt):
            $hasId = $m[strtolower($rt).'_id'] ?? '';
            $expiry = $m[strtolower($rt).'_expiry'] ?? '';
          ?>
          <td class="small px-1"><?php
            if (!$hasId): ?><span class="text-muted">无</span><?php
            elseif ($rt === 'MSDS'): ?><span class="text-success" title="有MSDS">✓</span><?php
            elseif (!$expiry): ?><span class="text-muted" title="无有效期">-</span><?php
            elseif (strtotime($expiry) < time()): ?><a href="/homogeneous?action=edit&id=<?=$m['id']?>&step=3" class="badge bg-danger text-white" title="点击重新上传">过期</a><?php
            elseif (strtotime($expiry) <= strtotime('+30 days')): ?><a href="/homogeneous?action=edit&id=<?=$m['id']?>&step=3" class="badge bg-warning" title="点击更新">将到期</a><?php
            else: ?><span class="badge bg-success text-white" title="<?=h($expiry)?>"><?=h(substr($expiry,5))?></span><?php
            endif; ?>
          </td>
          <?php endforeach; ?>
          <td><?php
            if ($m['env_status'] === 'compliant'): ?><span class="badge badge-soft-success">合规</span><?php
            elseif ($m['env_status'] === 'non_compliant'): ?><span class="badge badge-soft-danger">不合规</span><?php
            else: ?><span class="badge badge-soft-warning">待评估</span><?php endif; ?></td>
          <td class="nowrap"><?=statusBadge($m['audit_status'] ?? 'N')?></td>
          <td><small class="text-muted"><?=h(substr($m['updated_at'] ?: $m['created_at'], 0, 10))?></small></td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn-icon btn btn-sm btn-outline-success" title="复制" onclick="openCopyModal(<?=$m['id']?>,'<?=h(addslashes($m['material_name']))?>')"><i class="fas fa-copy"></i></button>
              <a href="/homogeneous?action=reports&id=<?=$m['id']?>" class="btn-icon btn btn-sm btn-outline-info" title="检测报告"><i class="fas fa-file-alt"></i></a>
              <a href="/homogeneous?action=compliance&id=<?=$m['id']?>" class="btn-icon btn btn-sm btn-outline-info" title="合规分析"><i class="fas fa-balance-scale"></i></a>
              <?php if (can('homogeneous.edit') && ($m['audit_status'] !== 'A' || isAdmin())): ?><a href="/homogeneous?action=edit&id=<?=$m['id']?>" class="btn-icon btn btn-sm btn-outline-primary" title="编辑"><i class="fas fa-edit"></i></a><?php endif; ?>
              <?php if (can('homogeneous.delete') && ($m['audit_status'] !== 'A' || isAdmin())): ?><form method="post" style="display:contents"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$m['id']?>"><?=csrf_field()?><button type="submit" class="btn-icon btn btn-sm btn-outline-danger" title="删除" onclick="return confirm('确认删除？')"><i class="fas fa-trash"></i></button></form><?php endif; ?>
              <?php if ($m['audit_status'] === 'S' && isAdmin()): ?>
               <form method="post" style="display:contents"><input type="hidden" name="action" value="audit_hm"><input type="hidden" name="id" value="<?=$m['id']?>"><input type="hidden" name="audit_status" value="A"><?=csrf_field()?><button type="submit" class="btn-icon btn btn-sm btn-outline-success" title="批准" onclick="return confirm('确认批准？')"><i class="fas fa-check"></i></button></form>
               <form method="post" style="display:contents"><input type="hidden" name="action" value="audit_hm"><input type="hidden" name="id" value="<?=$m['id']?>"><input type="hidden" name="audit_status" value="R"><?=csrf_field()?><button type="submit" class="btn-icon btn btn-sm btn-outline-danger" title="驳回" onclick="return confirm('确认驳回？')"><i class="fas fa-times"></i></button></form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
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
