<div class="page-header">
  <h5><i class="fas fa-table"></i> 物质含量管理</h5>
  <div>
    <a href="/report" class="btn btn-sm btn-outline-secondary">报告列表</a>
  </div>
</div>
<div class="search-bar mb-3">
  <form method="get" class="form-inline">
    <div class="input-group input-group-sm mr-2 w-220">
      <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search text-muted"></i></span></div>
      <input type="text" name="search" class="form-control" placeholder="搜索报告编号/文件名..." value="<?=h($_GET['search'] ?? '')?>">
    </div>
    <select name="report_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">选择报告</option>
      <?php foreach ($reports as $r): ?>
      <option value="<?=$r['id']?>" <?=($reportId ?? 0) == $r['id'] ? 'selected' : ''?>><?=h($r['report_no'])?><?=$r['file_path'] ? ' (' . basename($r['file_path']) . ')' : ''?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter"></i> 筛选</button>
    <a href="/report/elements" class="btn btn-sm btn-outline-secondary ml-1"><i class="fas fa-undo"></i></a>
  </form>
</div>
<?php if (!empty($reportId)): ?>
<div class="card">
  <div class="card-header">
    元素含量列表
    <div class="card-actions">
      <?php if (can('report.edit')): ?><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#elemModal"><i class="fas fa-plus"></i></button><?php endif; ?>
      <?php if (can('report.edit')): ?><button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#batchModal"><i class="fas fa-edit"></i> 批量</button><?php endif; ?>
    </div>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th>物质名称</th><th>CAS号</th><th>含量</th><th>单位</th><th>限值</th><th>限值单位</th><th>超标</th><th>检测方法</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($elements as $e): ?>
        <tr>
          <td><?=h($e['substance_name'])?></td>
          <td><?=h($e['cas_no'])?></td>
          <td class="font-weight-bold"><?=h($e['content_value'])?></td>
          <td><?=h($e['unit'])?></td>
          <td><?=h($e['limit_value'] ?? '-')?></td>
          <td><?=h($e['limit_unit'] ?? '-')?></td>
          <td><?=$e['is_over_limit'] ? '<span class="status-tag danger">超标</span>' : '<span class="status-tag success">正常</span>'?></td>
          <td><small><?=h($e['detection_method'] ?? '-')?></small></td>
          <td>
            <?php if (can('report.edit')): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')"><?=csrf_field()?>
              <input type="hidden" name="action" value="element_delete">
              <input type="hidden" name="id" value="<?=$e['id']?>">
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
<div class="modal fade" id="elemModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">添加检测元素</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="element_add">
        <input type="hidden" name="report_id" value="<?=$reportId?>">
        <div class="form-group"><label>物质名称</label>
          <select name="substance_name" class="form-control" required>
            <?php foreach (['Cd','Pb','Hg','Cr6+','PBB','PBDE','DBP','BBP','DEHP','DIBP','Cl','Br','F','PFOS','PFOA','Be','SCCPs','HBCDD','Sb','As','VOC','P'] as $s): ?>
            <option value="<?=$s?>"><?=$s?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>CAS号</label><input type="text" name="cas_no" class="form-control"></div>
        <div class="row">
          <div class="col-4">
            <div class="form-group"><label>含量值</label><input type="number" name="content_value" class="form-control" step="0.001" required></div>
          </div>
          <div class="col-4">
            <div class="form-group"><label>单位</label>
              <select name="unit" class="form-control">
                <option value="mg/kg">mg/kg</option>
                <option value="ppm">ppm</option>
                <option value="%">%</option>
              </select>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group"><label>限值</label><input type="number" name="limit_value" class="form-control" step="0.001"></div>
          </div>
        </div>
        <div class="form-group"><label>检测方法</label><input type="text" name="detection_method" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">添加</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="batchModal">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">批量编辑元素含量</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="element_batch">
        <input type="hidden" name="report_id" value="<?=$reportId?>">
        <p class="text-muted small">填写常用元素的含量值，留空或填0表示未检测</p>
        <table class="table table-sm">
          <thead><tr><th>元素</th><th>CAS</th><th>含量值</th><th>单位</th><th>限值</th><th>方法</th></tr></thead>
          <tbody>
            <?php $standardElements = [['Cd','7440-43-9',100],['Pb','7439-92-1',1000],['Hg','7439-97-6',1000],['Cr6+','18540-29-9',1000],['PBB','',1000],['PBDE','',1000],['DBP','84-74-2',1000],['BBP','85-68-7',1000],['DEHP','117-81-7',1000],['DIBP','84-69-5',1000]]; ?>
            <?php foreach ($standardElements as $i => $se): ?>
            <tr>
              <td><?=$se[0]?></td>
              <td><input type="hidden" name="substances[]" value="<?=$se[0]?>"><input type="text" name="cas_list[]" class="form-control form-control-sm" value="<?=$se[1]?>" readonly style="width:120px"></td>
              <td><input type="number" name="values[]" class="form-control form-control-sm" step="0.01" style="width:100px"></td>
              <td><select name="units[]" class="form-control form-control-sm" style="width:80px"><option value="mg/kg">mg/kg</option></select></td>
              <td><input type="number" name="limits[]" class="form-control form-control-sm" step="0.01" value="<?=$se[2]?>" style="width:100px"></td>
              <td><input type="text" name="methods[]" class="form-control form-control-sm" style="width:80px"></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">保存</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<div class="card"><div class="card-body text-center text-muted py-5"><i class="fas fa-hand-pointer fa-3x mb-3"></i><br>请先选择一个报告</div></div>
<?php endif; ?>
