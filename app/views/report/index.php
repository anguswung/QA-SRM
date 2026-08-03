<div class="page-header">
  <h5><i class="fas fa-file-alt"></i> 检测报告</h5>
  <div>
    <?php if (can('report.create')): ?><button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#reportModal"><i class="fas fa-plus"></i> 上传报告</button><?php endif; ?>
  </div>
</div>
<div class="search-bar mb-3">
  <form method="get" class="form-inline">
    <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="搜索编号/机构" value="<?=h($_GET['search'] ?? '')?>">
    <select name="report_type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部类型</option>
      <option value="RoHS" <?=($_GET['report_type'] ?? '') === 'RoHS' ? 'selected' : ''?>>RoHS</option>
      <option value="REACH" <?=($_GET['report_type'] ?? '') === 'REACH' ? 'selected' : ''?>>REACH</option>
      <option value="HF" <?=($_GET['report_type'] ?? '') === 'HF' ? 'selected' : ''?>>HF</option>
      <option value="MSDS" <?=($_GET['report_type'] ?? '') === 'MSDS' ? 'selected' : ''?>>MSDS</option>
    </select>
    <select name="audit_status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部状态</option>
      <option value="pending" <?=($_GET['audit_status'] ?? '') === 'pending' ? 'selected' : ''?>>待审核</option>
      <option value="approved" <?=($_GET['audit_status'] ?? '') === 'approved' ? 'selected' : ''?>>已通过</option>
      <option value="rejected" <?=($_GET['audit_status'] ?? '') === 'rejected' ? 'selected' : ''?>>已退回</option>
    </select>
    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr>
        <th style="width:140px">报告编号</th>
        <?php if (!$isSupplier): ?><th style="width:120px">供应商</th><?php endif; ?>
        <th style="width:80px">来源</th>
        <th>关联项</th>
        <th style="width:80px">类型</th>
        <th>检测机构</th>
        <th style="width:100px">检测日期</th>
        <th style="width:100px">有效期</th>
        <th style="width:80px">审核状态</th>
        <th style="width:100px">操作</th>
      </tr></thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td><?=h($r['report_no'])?></td>
          <?php if (!$isSupplier): ?><td><?=h($r['supplier_name'] ?? '-')?></td><?php endif; ?>
          <td><span class="badge badge-soft-<?=$r['source_type'] === 'material' ? 'primary' : 'success'?>"><?=$r['source_type'] === 'material' ? '物料' : '均质'?></span></td>
          <td><?=h($r['item_name'] ?? '-')?></td>
          <td><span class="badge badge-soft-info"><?=h($r['report_type'])?></span></td>
          <td><?=h($r['test_agency'])?></td>
          <td><?=h($r['test_date'])?></td>
          <td><small class="<?=strtotime($r['expiry_date'] ?? '') < time() ? 'text-danger' : ''?>"><?=h($r['expiry_date'] ?? '-')?></small></td>
          <td><?=statusBadge($r['audit_status'])?></td>
          <td>
            <?php if ($r['source_type'] === 'material'): ?>
            <?php if (can('report.edit')): ?><a href="/report?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a><?php endif; ?>
            <?php if (can('report.delete')): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
            <?php endif; ?>
            <?php else: ?>
            <a href="/homogeneous?action=detail&id=<?=$r['source_id']?>" class="btn btn-sm btn-outline-success" title="查看均质材料"><i class="fas fa-eye"></i></a>
            <?php endif; ?>
            <?php if ($r['file_path']): ?>
            <a href="<?=h(fileUrl($r['file_path']))?>" class="btn btn-sm btn-outline-info" target="_blank"><i class="fas fa-download"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="reportModal">
  <div class="modal-dialog modal-lg">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">上传检测报告</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>关联均质材料</label>
              <input type="text" id="hmSearch" class="form-control form-control-sm mb-1" placeholder="输入名称或供应商搜索...">
              <select name="material_id" id="hmSelect" class="form-control" size="1">
                <option value="">请选择均质材料</option>
                <?php $db = DB::getInstance(); $hms = $db->query("SELECT hm.id, hm.material_name, s.name AS supplier_name FROM homogeneous_material hm LEFT JOIN supplier s ON hm.supplier_id=s.id ORDER BY hm.material_name"); ?>
                <?php foreach ($hms as $h): ?>
                <option value="<?=$h['id']?>" data-search="<?=h(strtolower($h['material_name'] . ' ' . ($h['supplier_name'] ?? '')))?>"><?=h($h['material_name'])?><?=($h['supplier_name'] ?? '') ? ' (' . h($h['supplier_name']) . ')' : ''?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>报告编号</label><input type="text" name="report_no" class="form-control" id="modReportNo"></div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group"><label>报告类型</label>
              <select name="report_type" class="form-control">
                <option value="RoHS">RoHS</option><option value="REACH">REACH</option>
                <option value="HF">HF</option><option value="MSDS">MSDS</option>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group"><label>检测机构</label><input type="text" name="test_agency" class="form-control" id="modTestAgency"></div>
          </div>
          <div class="col-md-4">
            <div class="form-group"><label>检测日期</label><input type="date" name="test_date" class="form-control" id="modTestDate"></div>
          </div>
        </div>
        <div class="form-group">
          <label>报告文件 <small class="text-muted">(支持PDF自动识别)</small></label>
          <input type="file" name="report_file" class="form-control-file" accept=".pdf,.xls,.xlsx" id="modReportFile">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">上传</button>
        <button class="btn btn-secondary" data-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<script>
document.getElementById('hmSearch').addEventListener('input', function() {
  var keyword = this.value.toLowerCase();
  var select = document.getElementById('hmSelect');
  var options = select.options;
  var firstMatch = null;
  for (var i = 1; i < options.length; i++) {
    var match = options[i].getAttribute('data-search').indexOf(keyword) !== -1;
    options[i].style.display = match ? '' : 'none';
    if (match && !firstMatch) firstMatch = options[i];
  }
  if (firstMatch && keyword) { select.value = firstMatch.value; }
});
</script>
