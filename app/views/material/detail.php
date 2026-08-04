<div class="page-header">
  <h5><i class="fas fa-boxes"></i> <?=h($item['material_name'])?> <small class="text-muted">[<?=h($item['material_no'])?>]</small></h5>
  <div>
    <?php if (can('material.edit')): ?><a href="/material?action=edit&id=<?=$item['id']?>" class="btn btn-outline-primary"><i class="fas fa-edit"></i> 编辑</a><?php endif; ?>
    <a href="/compliance/analyze?material_id=<?=$item['id']?>" class="btn btn-outline-info"><i class="fas fa-balance-scale"></i> 合规分析</a>
    <a href="/material" class="btn btn-outline-secondary"><i class="fas fa-list"></i> 返回列表</a>
  </div>
</div>
<div class="card">
  <div class="card-body py-3">
    <div class="row">
      <div class="col-md-2 text-center">
        <div class="circle-icon-wrap">
          <i class="fas fa-box fa-2x text-primary"></i>
        </div>
        <span class="status-tag <?=$item['env_status'] === 'compliant' ? 'success' : ($item['env_status'] === 'non_compliant' ? 'danger' : 'warning')?>"><?=$item['env_status'] === 'compliant' ? '合规' : ($item['env_status'] === 'non_compliant' ? '不合规' : '待评估')?></span>
      </div>
      <div class="col-md-7">
        <table class="table table-sm mb-0" style="table-layout:fixed">
          <tr>
            <td class="td-label">编号</td>
            <td class="w-200"><?=h($item['material_no'])?></td>
            <td class="td-label">供应商</td>
            <td><?=h($item['supplier_name'] ?? '-')?></td>
          </tr>
          <tr>
            <td style="color:#64748b">审核状态</td>
            <td><?=statusBadge($item['audit_status'])?></td>
            <td style="color:#64748b">单重</td>
            <td><?=h($item['unit_weight'] ?? 0)?> g</td>
          </tr>
          <tr>
            <td style="color:#64748b">创建时间</td>
            <td><?=h($item['created_at'])?></td>
          </tr>
        </table>
      </div>
      <div class="col-md-3 border-left">
        <div class="d-flex justify-content-around text-center pt-2">
          <div>
            <div class="text-success font-weight-bold h5 mb-0"><?=count($composition)?></div>
            <small class="text-muted">成分</small>
          </div>
          <div>
            <div class="text-info font-weight-bold h5 mb-0"><?=$totalReports?></div>
            <small class="text-muted">报告</small>
          </div>
          <div>
            <div class="text-warning font-weight-bold h5 mb-0"><?=$totalElements?></div>
            <small class="text-muted">检测项</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-header">
    <ul class="nav nav-tabs card-header-tabs" id="materialTabs">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabComposition">均质材料</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabReports">检测报告</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabElements">检测结果</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAudit">审核记录</a></li>
    </ul>
  </div>
  <div class="card-body tab-content">
    

<div class="tab-pane active" id="tabComposition">
      <div class="card">
        <div class="card-header">
          均质物料
          <div class="card-actions"><?php if (can('material.edit')): ?><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#hmPickerModal"><i class="fas fa-plus"></i> 选择均质材料</button><?php endif; ?></div>
        </div>
        <div class="card-body p-0">
          <form method="post" id="compForm"><?=csrf_field()?>
            <input type="hidden" name="action" value="composition_batch_update">
            <input type="hidden" name="material_id" value="<?=$item['id']?>">
          <table class="table table-sm table-bordered mb-0" id="compTable">
            <thead class="thead-light">
              <tr><th style="width:22%">均质材料名称</th><th width="70">RoHS</th><th width="70">REACH</th><th width="70">HF</th><th width="70">MSDS</th><th style="width:9%">含量%</th><th style="width:9%">重量(g)</th><th style="width:8%">最小%</th><th style="width:8%">最大%</th><th style="width:8%">主成分</th><th style="width:8%">操作</th></tr>
            </thead>
            <tbody>
<?php foreach ($composition as $c):
  $wt = round(($item['unit_weight'] ?? 0) * ($c['min_percentage'] ?? 0) / 100, 4);
?>
              <tr class="comp-row" data-substance="<?=h($c['substance_name'])?>" data-hm-id="<?=$c['substance_id'] ?: 0?>">
                <td class="font-weight-bold word-break"><a href="javascript:void(0)" class="text-primary" onclick="toggleHmDetail(<?=$c['substance_id'] ?: 0?>, this)"><?=h($c['substance_name'])?></a></td>
                <?php foreach (['RoHS','REACH','HF','MSDS'] as $rt):
                  $hasId = $c[strtolower($rt).'_id'] ?? '';
                  $expiry = $c[strtolower($rt).'_expiry'] ?? '';
                ?>
                <td class="text-center small px-1"><?php
                  if (!$hasId || !$expiry): ?><span class="text-muted">无</span><?php
                  elseif ($rt === 'MSDS'): ?><span class="text-success" title="有MSDS">✓</span><?php
                  elseif (strtotime($expiry) < time()): ?><a href="/homogeneous?action=edit&id=<?=$c['substance_id']?>&step=3" class="badge badge-danger text-white" title="点击重新上传">过期</a><?php
                  elseif (strtotime($expiry) <= strtotime('+30 days')): ?><a href="/homogeneous?action=edit&id=<?=$c['substance_id']?>&step=3" class="badge badge-warning text-white" title="点击更新">将到期</a><?php
                  else: ?><span class="badge badge-success" title="<?=h($expiry)?>"><?=h(substr($expiry,5))?></span><?php
                  endif; ?>
                </td>
                <?php endforeach; ?>
                <td>
                  <input type="hidden" name="comp_id[]" value="<?=$c['id']?>">
                  <input type="number" name="comp_pct[]" class="form-control form-control-sm comp-input" step="0.01" value="<?=$c['min_percentage']?>">
                </td>
                <td class="comp-weight"><?=$wt?></td>
                <td><?=h($c['min_percentage'])?></td>
                <td><?=h($c['max_percentage'])?></td>
                <td><?=$c['is_main_component'] ? '<span class="status-tag success">主成分</span>' : ''?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('确定删除？')){deleteComposition(<?=$c['id']?>,this)}"><i class="fas fa-trash"></i></button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="bg-light font-weight-bold">
                <td></td>
                <td></td><td></td><td></td><td></td>
                <td class="text-center py-2">合计: <span id="totalPct">0.00</span>% <span id="totalStatus"></span></td>
                <td class="text-center py-2"><span id="totalWt">0</span> g</td>
                <td></td><td></td><td></td><td></td>
              </tr>
            </tfoot>
          </table>
          <div class="card-footer text-right">
          </div>
          </form>
        </div>
      </div>
    </div>
<div class="tab-pane" id="tabReports">
      <div class="card">
        <div class="card-header">
          检测报告
          <div class="card-actions"><?php if (can('report.create')): ?><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="fas fa-plus"></i> 上传报告</button><?php endif; ?></div>
        </div>
        <div class="card-body p-0">
          <table class="table table-hover datatable">
            <thead><tr><th>报告编号</th><th>类型</th><th>检测机构</th><th>检测日期</th><th>有效期</th><th>审核</th><th>文件</th></tr></thead>
            <tbody>
              <?php
              $reportSubstances = [];
              foreach ($elements as $e) { $reportSubstances[$e['report_id']][] = $e['substance_name']; }
              ?>
              <?php foreach ($reports as $r): $subsList = array_unique($reportSubstances[$r['id']] ?? []); ?>
              <tr class="<?=strtotime($r['expiry_date'] ?? '') < time() && $r['expiry_date'] ? 'table-danger' : ''?>" data-report-id="<?=$r['id']?>" data-substances="<?=h(implode('||', $subsList))?>">
                <td><?=h($r['report_no'])?></td>
                <td><span class="badge badge-soft-info"><?=h($r['report_type'])?></span></td>
                <td><?=h($r['test_agency'])?></td>
                <td><?=h($r['test_date'])?></td>
                <td>
                  <?=h($r['expiry_date'] ?? '-')?>
                  <?php if ($r['expiry_date'] && strtotime($r['expiry_date']) < time()): ?>
                  <span class="status-tag danger">已过期</span>
                  <?php elseif ($r['expiry_date'] && strtotime($r['expiry_date']) < strtotime('+30 days')): ?>
                  <span class="status-tag warning">即将到期</span>
                  <?php endif; ?>
                </td>
                <td><?=statusBadge($r['audit_status'])?></td>
                <td>
                  <?php if ($r['file_path']): ?>
                  <a href="<?=h(fileUrl($r['file_path']))?>" class="btn btn-sm btn-outline-info" target="_blank" title="下载文件"><i class="fas fa-download"></i></a>
                  <?php endif; ?>
                  <a href="/report/elements?report_id=<?=$r['id']?>" class="btn btn-sm btn-outline-success" title="查看含量"><i class="fas fa-flask"></i></a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
<div class="tab-pane" id="tabElements">
    <div class="card">
      <div class="card-header">
        检测结果 - 物质含量
        <div class="card-actions">
          <a href="/report/elements" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i> 管理</a>
        </div>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover datatable">
          <thead>
            <tr><th>报告编号</th><th>物质</th><th>CAS</th><th>含量</th><th>单位</th><th>限值</th><th>超标</th></tr>
          </thead>
          <tbody>
            <?php foreach ($elements as $e): ?>
            <tr class="<?=$e['is_over_limit'] ? 'table-danger' : ''?>" data-substance="<?=h($e['substance_name'])?>">
              <td><small><?=h($e['report_no'])?></small></td>
              <td class="font-weight-bold"><?=h($e['substance_name'])?></td>
              <td><?=h($e['cas_no'])?></td>
              <td class="font-weight-bold <?=$e['is_over_limit'] ? 'text-danger' : ''?>"><?=h($e['content_value'])?></td>
              <td><?=h($e['unit'])?></td>
              <td><?=h($e['limit_value'] ?? '-')?> <?=h($e['limit_unit'] ?? '')?></td>
              <td><?=$e['is_over_limit'] ? '<span class="status-tag danger">超标</span>' : '<span class="status-tag success">正常</span>'?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-header">超标物质汇总</div>
      <div class="card-body p-0">
        <table class="table table-sm">
          <thead><tr><th>物质</th><th>超标次数</th><th>最高含量</th><th>限值</th></tr></thead>
          <tbody>
            <?php
            $overLimit = array_filter($elements, fn($e) => $e['is_over_limit']);
            $grouped = [];
            foreach ($overLimit as $e) {
              $key = $e['substance_name'];
              if (!isset($grouped[$key])) $grouped[$key] = ['count' => 0, 'max' => 0, 'limit' => $e['limit_value']];
              $grouped[$key]['count']++;
              $grouped[$key]['max'] = max($grouped[$key]['max'], $e['content_value']);
            }
            ?>
            <?php if ($grouped): foreach ($grouped as $name => $g): ?>
            <tr>
              <td><span class="badge badge-soft-danger"><?=h($name)?></span></td>
              <td><?=$g['count']?>次</td>
              <td class="text-danger font-weight-bold"><?=$g['max']?></td>
              <td><?=h($g['limit'])?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" class="text-center text-muted py-3">暂无超标数据</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<div class="tab-pane" id="tabAudit">
    <div class="card">
      <div class="card-header">审核记录</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th>操作</th><th>意见</th><th>操作人</th><th>时间</th></tr></thead>
          <tbody>
            <?php foreach ($auditLogs as $l): ?>
            <tr>
              <td><span class="status-tag <?=$l['action'] === 'approve' ? 'success' : 'danger'?>"><?=h($l['action'])?></span></td>
              <td><?=h($l['comment'] ?? '-')?></td>
              <td><?=h($l['operator_id'])?></td>
              <td><small><?=h($l['operated_at'])?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($auditLogs)): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">暂无审核记录</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>
<div class="modal fade" id="reportModal">
  <div class="modal-dialog modal-lg">
    <form method="post" enctype="multipart/form-data" class="modal-content" action="/report"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">上传检测报告</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="material_id" value="<?=$item['id']?>">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>报告编号</label><input type="text" name="report_no" class="form-control" id="reportNo"></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>报告类型</label>
              <select name="report_type" class="form-control">
                <option value="RoHS">RoHS</option><option value="REACH">REACH</option>
                <option value="HF">HF</option><option value="MSDS">MSDS</option>
                <option value="包装指令">包装指令</option><option value="电池指令">电池指令</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group"><label>检测机构</label><input type="text" name="test_agency" class="form-control" id="testAgency"></div>
          </div>
          <div class="col-md-4">
            <div class="form-group"><label>检测日期</label><input type="date" name="test_date" class="form-control" id="testDate"></div>
          </div>
          <div class="col-md-4">
            <div class="form-group"><label>有效期</label><input type="date" name="expiry_date" class="form-control" id="expiryDate"></div>
          </div>
        </div>
        <div class="form-group">
          <label>报告文件 <small class="text-muted">(PDF/Excel，自动识别报告内容)</small></label>
          <input type="file" name="report_file" class="form-control-file" accept=".pdf,.xls,.xlsx" id="reportFile">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">上传</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="hmPickerModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-flask text-success mr-1"></i> 选择均质材料</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <form method="post" class="mb-0"><?=csrf_field()?>
        <div class="modal-body">
          <input type="hidden" name="action" value="composition_add">
          <input type="hidden" name="material_id" value="<?=$item['id']?>">
          <input type="hidden" name="hm_id" id="pickHmId" value="">
          <input type="text" class="form-control mb-3" id="hmFilter" placeholder="搜索均质材料名称..." onkeyup="filterHM(this.value)">
          <table class="table table-hover table-sm mb-0" id="hmPickerTable">
            <thead><tr><th>名称</th><th>供应商</th><th class="w-80">操作</th></tr></thead>
            <tbody>
              <?php foreach ($hmMaterials as $hm): $spName = $hm['supplier_name'] ?: (QueryRegistry::supplierNameMap()[$hm['supplier_id']] ?? '第三方'); ?>
              <tr data-hmid="<?=$hm['id']?>" data-name="<?=h($hm['material_name'])?>" data-supplier="<?=h($spName)?>">
                <td class="font-weight-bold"><?=h($hm['material_name'])?></td>
                <td class="small"><?=h($spName)?></td>
                <td><button type="submit" class="btn btn-sm btn-success" onclick="document.getElementById('pickHmId').value=<?=$hm['id']?>">选择</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          </table>
          <div id="hmPagination" class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted" id="hmPageInfo"></small>
            <nav><ul class="pagination pagination-sm mb-0" id="hmPageNav"></ul></nav>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById("hmFilter").addEventListener("keydown", function(e) {
  if (e.key === "Enter") { e.preventDefault(); return false; }
});

var hmPageSize = 10;
var hmCurrentPage = 1;
var hmFilteredRows = [];

function filterHM(val) {
  var rows = document.querySelectorAll("#hmPickerTable tbody tr");
  hmFilteredRows = [];
  rows.forEach(function(r) {
    var match = r.getAttribute("data-name").toLowerCase().indexOf(val.toLowerCase()) > -1;
    r.style.display = match ? "" : "none";
    if (match) hmFilteredRows.push(r);
  });
  hmCurrentPage = 1;
  renderHmPage();
}

function renderHmPage() {
  var total = hmFilteredRows.length;
  var totalPages = Math.ceil(total / hmPageSize) || 1;
  if (hmCurrentPage > totalPages) hmCurrentPage = totalPages;
  var start = (hmCurrentPage - 1) * hmPageSize;
  var end = start + hmPageSize;
  hmFilteredRows.forEach(function(r, i) {
    r.style.display = (i >= start && i < end) ? "" : "none";
  });
  document.getElementById("hmPageInfo").textContent = "共" + total + "条，第" + hmCurrentPage + "/" + totalPages + "页";
  var nav = document.getElementById("hmPageNav");
  var h = '';
  h += '<li class="page-item ' + (hmCurrentPage <= 1 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="hmGoPage(' + (hmCurrentPage - 1) + ');return false;">上一页</a></li>';
  var startPage = Math.max(1, hmCurrentPage - 2);
  var endPage = Math.min(totalPages, startPage + 4);
  if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);
  for (var p = startPage; p <= endPage; p++) {
    h += '<li class="page-item ' + (p === hmCurrentPage ? 'active' : '') + '"><a class="page-link" href="#" onclick="hmGoPage(' + p + ');return false;">' + p + '</a></li>';
  }
  h += '<li class="page-item ' + (hmCurrentPage >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="hmGoPage(' + (hmCurrentPage + 1) + ');return false;">下一页</a></li>';
  nav.innerHTML = h;
}

function hmGoPage(p) {
  hmCurrentPage = p;
  renderHmPage();
}

document.getElementById("hmPickerModal").addEventListener("show.bs.modal", function() {
  hmFilteredRows = Array.from(document.querySelectorAll("#hmPickerTable tbody tr"));
  hmCurrentPage = 1;
  renderHmPage();
});

var _hmCache = {};
var _activeHmId = 0;
var _origReportsHtml = "";
var _origElementsHtml = "";
var _origSummaryHtml = "";
var _csrf = '<?=csrf_token()?>';

function calcTotal() {
  var total = 0;
  var totalWt = 0;
  var unitWt = <?=($item['unit_weight'] ?? 0)?>;
  var inputs = document.querySelectorAll('#compTable tbody input[name="comp_pct[]"]');
  for (var i = 0; i < inputs.length; i++) {
    var pct = parseFloat(inputs[i].value) || 0;
    total += pct;
    var wt = (unitWt * pct / 100).toFixed(4);
    var row = inputs[i].closest('tr');
    var wtTd = row.querySelector('.comp-weight');
    if (wtTd) wtTd.textContent = wt;
    totalWt += parseFloat(wt);
  }
  var el = document.getElementById('totalPct');
  if (el) el.textContent = total.toFixed(2);
  var tw = document.getElementById('totalWt');
  if (tw) tw.textContent = totalWt.toFixed(4);
  var st = document.getElementById('totalStatus');
  if (st) {
    if (total >= 99.99 && total <= 100.01) {
      st.innerHTML = '<span class="badge badge-success">正确</span>';
    } else {
      st.innerHTML = '<span class="badge badge-danger">需100%</span>';
    }
  }
}


function deleteComposition(id, btn) {
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/material', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    if (xhr.status === 200) {
      var tr = btn.closest('tr');
      if (tr) tr.remove();
      calcTotal();
    }
  };
  xhr.send('action=composition_delete&_csrf_token=' + encodeURIComponent(window._csrf || '') + '&id=' + id);
}



document.addEventListener("DOMContentLoaded", function() {
  _origReportsHtml = document.querySelector("#tabReports .card-body").innerHTML;
  _origElementsHtml = document.querySelector("#tabElements .card-body").innerHTML;
  var summaryCard = document.querySelector("#tabElements .card.mt-3");
  if (summaryCard) _origSummaryHtml = summaryCard.innerHTML;

  // Real-time total update + blur auto-save (homogeneous-style)
    calcTotal();
    document.addEventListener('input', function(e) {
      if (e.target && e.target.name === 'comp_pct[]') calcTotal();
    });
    document.addEventListener('change', function(e) {
      if (e.target && e.target.name === 'comp_pct[]') {
        document.getElementById('compForm').submit();
      }
    });
  });

function toggleHmDetail(hmId, linkEl) {
  var tab = document.querySelector('#materialTabs .nav-link[href="#tabReports"]');
  if (tab) tab.click();
  if (!hmId) return;
  var row = linkEl.closest("tr");
  if (_activeHmId === hmId) {
    _activeHmId = 0;
    document.querySelectorAll(".comp-row").forEach(function(r) { r.classList.remove("table-primary"); });
    restoreMaterialData();
    return;
  }
  _activeHmId = hmId;
  document.querySelectorAll(".comp-row").forEach(function(r) { r.classList.remove("table-primary"); });
  row.classList.add("table-primary");
  loadHmData(hmId);
}

function loadHmData(hmId) {
  if (_hmCache[hmId]) { renderHmData(hmId, _hmCache[hmId]); return; }
  var xhr = new XMLHttpRequest();
  xhr.open("GET", "/material?action=hm_data&hm_id=" + hmId, true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);
      _hmCache[hmId] = data;
      renderHmData(hmId, data);
    }
  };
  xhr.send();
}

function renderHmData(hmId, data) {
  renderHmReports(data.reports);
  renderHmAnalysis(data.analysis, data.hm);
  var tab = document.querySelector('#materialTabs .nav-link[href="#tabReports"]');
  if (tab) tab.click();
}

function renderHmReports(reports) {
  var html = '<table class="table table-hover datatable"><thead><tr><th>报告编号</th><th>类型</th><th>检测机构</th><th>检测日期</th><th>有效期</th><th>版本</th><th>文件</th></tr></thead><tbody>';
  for (var i = 0; i < reports.length; i++) {
    var r = reports[i];
    var expired = r.expiry_date && new Date(r.expiry_date) < new Date();
    var expiring = r.expiry_date && !expired && new Date(r.expiry_date) < new Date(Date.now() + 30*86400000);
    var rowClass = expired ? "table-danger" : "";
    html += "<tr class=\"" + rowClass + "\">";
    html += "<td>" + escHtml(r.report_no) + "</td>";
    html += "<td><span class=\"badge badge-soft-info\">" + escHtml(r.report_type) + "</span></td>";
    html += "<td>" + escHtml(r.test_agency || "-") + "</td>";
    html += "<td>" + escHtml(r.test_date || "-") + "</td>";
    html += "<td>" + escHtml(r.expiry_date || "-");
    if (expired) html += " <span class=\"status-tag danger\">已过期</span>";
    else if (expiring) html += " <span class=\"status-tag warning\">即将到期</span>";
    html += "</td>";
    html += "<td>v" + r.version + "</td>";
    html += "<td>";
    if (r.file_path) html += "<a href=\"/" + r.file_path.replace(/\\/g, "/") + "\" class=\"btn btn-sm btn-outline-info\" target=\"_blank\" title=\"下载\"><i class=\"fas fa-download\"></i></a>";
    html += "</td></tr>";
  }
  html += "</tbody></table>";
  var body = document.querySelector("#tabReports .card-body");
  if (body) body.innerHTML = html;
  destroyDatatable("#tabReports table");
  initDatatable("#tabReports table");
}

function renderHmAnalysis(analysis, hm) {
  if (!analysis.length) {
    var body = document.querySelector("#tabElements .card-body");
    if (body) body.innerHTML = "<div class=\"text-center text-muted py-4\">暂无检测数据</div>";
    var summary = document.querySelector("#tabElements .card.mt-3");
    if (summary) summary.style.display = "none";
    return;
  }
  var html = "<table class=\"table table-hover datatable\"><thead><tr><th>物质</th><th>CAS</th><th>检测值</th><th>限值</th><th>结果</th><th>法规</th></tr></thead><tbody>";
  var overMap = {};
  for (var i = 0; i < analysis.length; i++) {
    var a = analysis[i];
    var over = parseFloat(a.detected_value) > parseFloat(a.limit_value || 0);
    var rowClass = over ? "table-danger" : "";
    html += "<tr class=\"" + rowClass + "\">";
    html += "<td class=\"font-weight-bold\">" + escHtml(a.substance_name || "-") + "</td>";
    html += "<td>" + escHtml(a.cas_no || "-") + "</td>";
    html += "<td class=\"" + (over ? "text-danger font-weight-bold" : "") + "\">" + escHtml(a.detected_value || "-") + "</td>";
    html += "<td>" + escHtml(a.limit_value || "-") + "</td>";
    html += "<td>" + (over ? "<span class=\"status-tag danger\">超标</span>" : "<span class=\"status-tag success\">正常</span>") + "</td>";
    html += "<td><small>" + escHtml(a.regulation_name || a.regulation_no || "-") + "</small></td>";
    html += "</tr>";
    if (over) {
      var k = a.substance_name || "-";
      if (!overMap[k]) overMap[k] = {count:0, max:0, limit:a.limit_value};
      overMap[k].count++;
      overMap[k].max = Math.max(overMap[k].max, parseFloat(a.detected_value || 0));
    }
  }
  html += "</tbody></table>";
  var body = document.querySelector("#tabElements .card-body");
  if (body) body.innerHTML = html;

  var keys = Object.keys(overMap);
  var summaryHtml = "<div class=\"card-header\">超标物质汇总</div><div class=\"card-body p-0\"><table class=\"table table-sm\"><thead><tr><th>物质</th><th>超标次数</th><th>最高含量</th><th>限值</th></tr></thead><tbody>";
  if (keys.length) {
    for (var k in overMap) {
      var g = overMap[k];
      summaryHtml += "<tr><td><span class=\"badge badge-soft-danger\">" + escHtml(k) + "</span></td><td>" + g.count + "次</td><td class=\"text-danger font-weight-bold\">" + g.max + "</td><td>" + escHtml(g.limit) + "</td></tr>";
    }
  } else {
    summaryHtml += "<tr><td colspan=\"4\" class=\"text-center text-muted py-3\">暂无超标数据</td></tr>";
  }
  summaryHtml += "</tbody></table></div>";
  var summaryCard = document.querySelector("#tabElements .card.mt-3");
  if (summaryCard) { summaryCard.innerHTML = summaryHtml; summaryCard.style.display = ""; }

  destroyDatatable("#tabElements table.datatable");
  initDatatable("#tabElements table.datatable");
}

function restoreMaterialData() {
  var body1 = document.querySelector("#tabReports .card-body");
  if (body1) body1.innerHTML = _origReportsHtml;
  var body2 = document.querySelector("#tabElements .card-body");
  if (body2) body2.innerHTML = _origElementsHtml;
  var summaryCard = document.querySelector("#tabElements .card.mt-3");
  if (summaryCard && _origSummaryHtml) summaryCard.innerHTML = _origSummaryHtml;
  destroyDatatable("#tabReports table");
  destroyDatatable("#tabElements table.datatable");
  initDatatable("#tabReports table");
  initDatatable("#tabElements table.datatable");
}

function escHtml(s) {
  if (!s) return "";
  return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

function initDatatable(sel) {
  var el = document.querySelector(sel);
  if (el && window.jQuery && jQuery.fn.DataTable) jQuery(el).DataTable({pageLength: 25, ordering: true});
}

function destroyDatatable(sel) {
  var el = document.querySelector(sel);
  if (el && window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(el)) jQuery(el).DataTable().destroy();
}
</script>
