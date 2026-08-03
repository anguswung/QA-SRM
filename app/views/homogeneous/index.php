<div class="page-header">
  <h5><i class="fas fa-flask"></i> 均质材料库</h5>
  <div>
    <?php if (can('homogeneous.create')): ?><a href="/homogeneous?action=create" class="btn btn-success"><i class="fas fa-plus"></i> 新建均质</a><?php endif; ?>
    <?php if (can('homogeneous.create')): ?><button class="btn btn-primary" data-toggle="modal" data-target="#importHmModal"><i class="fas fa-upload"></i> 批量导入</button><?php endif; ?>
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
    <form method="get" class="form-inline">
      <div class="input-group input-group-sm mr-2 w-260">
        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search text-muted"></i></span></div>
        <input type="text" name="search" class="form-control" placeholder="搜索名称..." value="<?=h($_GET['search'] ?? '')?>">
      </div>
      <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> 搜索</button>
      <a href="/homogeneous" class="btn btn-sm btn-outline-secondary ml-1"><i class="fas fa-undo"></i></a>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($materials)): ?>
    <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>暂无均质原材料</div>
    <?php else: ?>
    <table class="table table-hover datatable mb-0" data-options='{"order":[[0,"desc"]]}'>
      <thead class="thead-light">
        <tr>
          <th width="50">ID</th>
          <th>名称</th>
          <th width="100">性质</th>
          <?php if (!isSupplier()): ?><th width="80">供应商</th><?php endif; ?>
          <th width="70">用量</th>
          <th width="70">RoHS</th>
          <th width="70">REACH</th>
          <th width="70">HF</th>
          <th width="70">MSDS</th>
          <th width="70">环保状态</th>
          <th width="70">审核状态</th>
          <th width="120">更新日期</th>
          <th width="150">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($materials as $m): ?>
        <tr>
          <td class="text-muted"><?=h($m['id'])?></td>
          <td><span class="font-weight-bold"><?=h($m['material_name'])?></span></td>
          <td><span class="badge badge-soft-success">均质原材料</span></td>
          <?php if (!isSupplier()): ?><td><small><?=h($m['supplier_name_short'] ?: ($m['supplier_name'] ?? '-'))?></small></td><?php endif; ?>
          <td><?=h($m['weight_mg'] ?? '-')?> <?=h($m['weight_unit'] ?? 'g')?></td>
          <?php foreach (['RoHS','REACH','HF','MSDS'] as $rt):
            $hasId = $m[strtolower($rt).'_id'] ?? '';
            $expiry = $m[strtolower($rt).'_expiry'] ?? '';
          ?>
          <td class="text-center small px-1"><?php
            if (!$hasId): ?><span class="text-muted">无</span><?php
            elseif ($rt === 'MSDS'): ?><span class="text-success" title="有MSDS">✓</span><?php
            elseif (!$expiry): ?><span class="text-muted" title="无有效期">-</span><?php
            elseif (strtotime($expiry) < time()): ?><a href="/homogeneous?action=edit&id=<?=$m['id']?>&step=3" class="badge badge-danger text-white" title="点击重新上传">过期</a><?php
            elseif (strtotime($expiry) <= strtotime('+30 days')): ?><a href="/homogeneous?action=edit&id=<?=$m['id']?>&step=3" class="badge badge-warning text-white" title="点击更新">将到期</a><?php
            else: ?><span class="badge badge-success" title="<?=h($expiry)?>"><?=h(substr($expiry,5))?></span><?php
            endif; ?>
          </td>
          <?php endforeach; ?>
          <td><?php
            $envTypes = ['RoHS','REACH','HF'];
            $allValid = true;
            $hasEnvReport = false;
            foreach ($envTypes as $rt) {
              $hasId = $m[strtolower($rt).'_id'] ?? '';
              $expiry = $m[strtolower($rt).'_expiry'] ?? '';
              if ($expiry) {
                $hasEnvReport = true;
                if (strtotime($expiry) < time()) { $allValid = false; break; }
              } elseif ($hasId) {
                $hasEnvReport = true;
                $allValid = false;
              } else {
                $allValid = false;
              }
            }
            if ($hasEnvReport && $allValid): ?><span class="badge badge-soft-success">合规</span><?php
            elseif ($hasEnvReport && !$allValid): ?><span class="badge badge-soft-danger">不合规</span><?php
            else: ?><span class="badge badge-soft-warning">待评估</span><?php endif; ?></td>
          <td class="text-center"><?=statusBadge($m['audit_status'] ?? 'N')?></td>
          <td><small class="text-muted"><?=h(substr($m['updated_at'] ?: $m['created_at'], 0, 10))?></small></td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-success" title="复制" onclick="openCopyModal(<?=$m['id']?>,'<?=h(addslashes($m['material_name']))?>')"><i class="fas fa-copy"></i></button>
              <a href="/homogeneous?action=reports&id=<?=$m['id']?>" class="btn btn-outline-info" title="检测报告"><i class="fas fa-file-alt"></i></a>
              <a href="/homogeneous?action=compliance&id=<?=$m['id']?>" class="btn btn-outline-info" title="合规分析"><i class="fas fa-balance-scale"></i></a>
              <?php if (can('homogeneous.edit') && ($m['audit_status'] !== 'A' || isAdmin())): ?><a href="/homogeneous?action=edit&id=<?=$m['id']?>" class="btn btn-outline-primary" title="编辑"><i class="fas fa-edit"></i></a><?php endif; ?>
              <?php if (can('homogeneous.delete') && ($m['audit_status'] !== 'A' || isAdmin())): ?><button class="btn btn-outline-danger" title="删除" onclick="if(confirm('确认删除？')){var f=document.createElement('form');f.method='post';f.innerHTML='<input name=action value=delete><input name=id value=<?=$m['id']?>>';document.body.appendChild(f);f.submit()}"><i class="fas fa-trash"></i></button><?php endif; ?>
              <?php if ($m['audit_status'] === 'S' && isAdmin()): ?>
              <button class="btn btn-outline-success" title="批准" onclick="if(confirm('确认批准？')){var f=document.createElement('form');f.method='post';f.innerHTML='<input name=action value=audit_hm><input name=id value=<?=$m['id']?>><input name=audit_status value=A>';document.body.appendChild(f);f.submit()}"><i class="fas fa-check"></i></button>
              <button class="btn btn-outline-danger" title="驳回" onclick="if(confirm('确认驳回？')){var f=document.createElement('form');f.method='post';f.innerHTML='<input name=action value=audit_hm><input name=id value=<?=$m['id']?>><input name=audit_status value=R>';document.body.appendChild(f);f.submit()}"><i class="fas fa-times"></i></button>
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
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="fas fa-copy mr-1"></i>复制均质材料</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="copy">
        <input type="hidden" name="source_id" id="copySourceId" value="">
        <div class="form-group">
          <label>新均质名称 <span class="text-danger">*</span></label>
          <input type="text" name="new_name" id="copyNewName" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success"><i class="fas fa-copy mr-1"></i>复制</button>
        <button class="btn btn-secondary" data-dismiss="modal">取消</button>
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
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">批量导入均质材料</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="import">
        <div class="alert alert-info py-2">
          <i class="fas fa-info-circle mr-1"></i> 下载 <a href="/material?action=hm_template" target="_blank">物料均质模板</a>，填写后上传
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
