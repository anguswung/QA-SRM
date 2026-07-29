<div class="row justify-content-center">
  <div class="col-md-10">
    <div class="card shadow-sm">
      <div class="card-header bg-white py-3">
        <h5 class="mb-0"><i class="fas fa-<?=$id ? 'edit' : 'plus'?> mr-2"></i> <?=h($title)?></h5>
      </div>
      <div class="card-body">
        <ul class="steps" id="stepIndicator">
          <li data-step="1"><div class="step-circle">1</div><div class="step-label">填写基本信息</div></li>
          <li data-step="2"><div class="step-circle">2</div><div class="step-label">成分录入</div></li>
          <li data-step="3"><div class="step-circle">3</div><div class="step-label">上传环保文档</div></li>
          <li data-step="4"><div class="step-circle">4</div><div class="step-label">完成创建</div></li>
        </ul>
        <script>var initStep = <?=$currentStep ?? 1?>;</script>
        <form method="post" id="hmForm" action="/homogeneous" enctype="multipart/form-data" onkeydown="if(event.key==='Enter'&&event.target.type!=='submit'&&event.target.name!=='substanceQuery')event.preventDefault()"><?=csrf_field()?>
          <input type="hidden" name="action" value="<?=$id ? 'update' : 'create'?>">
          <?php if ($id): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
          <input type="hidden" name="step" id="formStep" value="<?=$currentStep ?? 1?>">

          <!-- 步骤1: 基本信息 -->
          <div class="step-panel <?=($currentStep ?? 1) !== 1 ? 'd-none' : ''?>" id="step1">
            <div class="form-row">
              <div class="form-group col-md-12">
                <label>均质名称 <span class="text-danger">*</span></label>
                <input type="text" name="material_name" class="form-control" value="<?=h($item['material_name'] ?? '')?>" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>供应商</label>
                <input type="text" name="supplier_name" class="form-control" value="<?=h($item['supplier_name'] ?? '')?>" placeholder="输入供应商名称">
              </div>
              <div class="form-group col-md-3">
                <label>用量</label>
                <input type="number" name="weight_mg" class="form-control" step="0.001" value="<?=h($item['weight_mg'] ?? '')?>">
              </div>
              <div class="form-group col-md-3">
                <label>单位</label>
                <select name="weight_unit" class="form-control">
                  <option value="g" <?=($item['weight_unit'] ?? 'g') === 'g' ? 'selected' : ''?>>g</option>
                  <option value="M" <?=($item['weight_unit'] ?? '') === 'M' ? 'selected' : ''?>>M</option>
                  <option value="Pcs" <?=($item['weight_unit'] ?? '') === 'Pcs' ? 'selected' : ''?>>Pcs</option>
                </select>
              </div>
            </div>
            <div class="text-right mt-3">
              <button type="submit" class="btn btn-primary px-4" onclick="document.getElementById('formStep').value=2">下一步<i class="fas fa-arrow-right ml-1"></i></button>
            </div>
          </div>

          <!-- 步骤2: 成分录入 -->
          <div class="step-panel <?=($currentStep ?? 1) !== 2 ? 'd-none' : ''?>" id="step2">
            <div class="alert alert-info py-2">
              <i class="fas fa-search mr-1"></i> 输入CAS号或中英文关键字搜索物质，从 爱化学/PubChem 数据库获取信息            </div>
            <div class="input-group mb-3">
              <input type="text" id="substanceQuery" class="form-control" placeholder="输入CAS号或物质名称..." onkeydown="if(event.key==='Enter'){event.preventDefault();searchSubstance()}">
              <div class="input-group-append">
                <button class="btn btn-primary" onclick="searchSubstance()"><i class="fas fa-search"></i> 搜索</button>
              </div>
            </div>
            <div id="substanceResults" class="mb-3"></div>
            <div class="mt-2 mb-3 p-2 border rounded bg-light d-flex align-items-center">
              <div class="flex-grow-1"><i class="fas fa-question-circle text-muted mr-1"></i><strong>其他杂项物质</strong> <span class="text-muted">(保密成分或未知项，无CAS号，一般在添加完均质材料后最后一项插入。)</span></div>
              <button type="button" class="btn btn-sm btn-outline-success" onclick="addOthers()"><i class="fas fa-plus mr-1"></i>添加至成分表</button>
            </div>
            <div class="border rounded p-3 bg-light">
                <h6 class="mb-3"><i class="fas fa-list mr-1"></i> 已添加均质材料</h6>
              <table class="table table-sm table-bordered mb-0" id="compTable">
                <thead class="thead-light">
                  <tr><th style="width:14%">CAS号</th><th style="width:18%">中文名</th><th style="width:18%">英文名</th><th style="width:12%">含量%</th><th style="width:12%">最小</th><th style="width:12%">最大</th><th style="width:14%">操作</th></tr>
                </thead>
                <tbody>
                  <?php if (!empty($composition)): foreach ($composition as $c): ?>
                    <tr>
                      <td><?=h($c['cas_no'] === '__others__' ? '-' : $c['cas_no'])?><input type="hidden" name="comp_cas[]" value="<?=h($c['cas_no'])?>"></td>
                      <td><?=h($c['name_cn'] ?? '')?><input type="hidden" name="comp_name_cn[]" value="<?=h($c['name_cn'] ?? '')?>"></td>
                      <td><?=h($c['name_en'] ?: $c['substance_name'])?><input type="hidden" name="comp_name[]" value="<?=h($c['substance_name'])?>"></td>
                    <td><input type="number" name="comp_pct[]" class="form-control form-control-sm comp-input" step="0.01" value="<?=h($c['percentage'] ?? $c['min_percentage'] ?? '')?>"></td>
                    <td><input type="number" name="comp_min[]" class="form-control form-control-sm comp-input" step="0.01" value="<?=h($c['min_pct'] ?? '')?>"></td>
                    <td><input type="number" name="comp_max[]" class="form-control form-control-sm comp-input" step="0.01" value="<?=h($c['max_pct'] ?? '')?>"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove();calcTotal()"><i class="fas fa-trash"></i></button></td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                  <tr class="bg-light font-weight-bold">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center py-2">合计: <span id="totalPct">0.00</span>% <span id="totalStatus"></span></td>
                    <td></td>
                    <td></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="text-right mt-3">
              <button type="submit" class="btn btn-outline-secondary px-4 mr-2" onclick="document.getElementById('formStep').value=1"><i class="fas fa-arrow-left mr-1"></i> 上一步</button>
              <button type="submit" class="btn btn-primary px-4" onclick="var t=calcTotal();if(t<99.99||t>100.01){event.preventDefault();toastr.error('含量合计为'+t.toFixed(2)+'%，须等于100%才能继续')}else{document.getElementById('formStep').value=3}">下一步<i class="fas fa-arrow-right ml-1"></i></button>
            </div>
          </div>

          <!-- 步骤3: 上传环保文档 -->
          <div class="step-panel <?=($currentStep ?? 1) !== 3 ? 'd-none' : ''?>" id="step3">
            <?php if (!empty($reports)):
              // group by version_group
              $groups = [];
              foreach ($reports as $r) {
                $g = $r['version_group'] ?: ($r['report_type'] . '_0');
                $groups[$g][] = $r;
              }
            ?>
            <div class="border rounded p-3 bg-light mb-3">
              <h6 class="mb-3"><i class="fas fa-file-alt text-info mr-1"></i> 已上传报告(<span class="text-muted">版本</span>)</h6>
              <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                  <tr><th>报告编号</th><th>类型</th><th>版本</th><th>检测机构</th><th>检测日期</th><th>有效期</th><th>文件</th><th>操作</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($groups as $vg => $gReports): ?>
                  <?php $chainVersions = array_map(fn($x) => $x['version'], $gReports); sort($chainVersions); ?>
                  <tr class="bg-light border-bottom-0"><td colspan="8" class="small text-muted py-1">版本链 <?=implode(' → ', $chainVersions)?> <?=h($gReports[0]['report_type'])?></td></tr>
                  <?php foreach ($gReports as $r): ?>
                  <tr class="<?=$r['status'] === 'superseded' ? 'text-muted' : ''?>">
                    <td><?=h($r['report_no'])?></td>
                    <td><span class="badge badge-soft-info"><?=h($r['report_type'])?></span></td>
                    <td>
                      v<?=(int)$r['version']?>
                      <?php if ($r['status'] === 'current'): ?><span class="badge badge-success badge-compact">当前</span><?php endif; ?>
                      <?php if ($r['status'] === 'superseded'): ?><span class="badge badge-secondary badge-compact">旧版</span><?php endif; ?>
                    </td>
                    <td><?=h($r['test_agency'] ?: '-')?></td>
                    <td><?=h($r['test_date'] ?: '-')?></td>
                    <td><?=h($r['expiry_date'] ?: '-')?></td>
                    <td>
                      <?php if ($r['file_path']): ?>
                      <a href="<?=h(fileUrl($r['file_path']))?>" target="_blank" class="text-info" title="<?=h(basename($r['file_path']))?>"><i class="fas fa-file-pdf fa-lg"></i></a>
                      <?php else: ?>
                      <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (can('homogeneous.edit')): ?><a href="/homogeneous?action=delete_report&report_id=<?=$r['id']?>&id=<?=$item['id']?>&step=3" class="btn btn-sm btn-outline-danger" onclick="return confirm('确认删除该报告？')"><i class="fas fa-trash"></i></a><?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
            <div class="border rounded p-3 bg-light mb-3">
              <h6 class="mb-3"><i class="fas fa-upload text-success mr-1"></i> 上传新报告</h6>
              <div class="row">
                <?php $reportTypes = ['RoHS', 'REACH', 'HF', 'MSDS'];
                $currentByType = [];
                foreach ($reports as $r) {
                  if ($r['status'] === 'current') $currentByType[$r['report_type']] = $r;
                }
                ?>
                <?php foreach ($reportTypes as $rt):
                  $cur = $currentByType[$rt] ?? null;
                  $nextVer = $cur ? ((int)$cur['version'] + 1) : 1;
                ?>
                <div class="col-md-3 mb-3">
                  <div class="border rounded p-2 bg-white h-100">
                    <div class="text-center mb-2">
                      <span class="badge badge-primary badge-lg"><?=h($rt)?></span>
                      <?php if ($cur): ?>
                      <br><small class="text-muted">当前v<?=(int)$cur['version']?> → v<?=$nextVer?></small>
                      <?php endif; ?>
                    </div>
                    <div class="form-group mb-2">
                      <label class="small mb-0">报告编号</label>
                      <input type="text" name="hm_report_no[<?=h($rt)?>]" class="form-control form-control-sm" placeholder="编号">
                    </div>
                    <div class="form-group mb-2">
                      <label class="small mb-0">检测机构</label>
                      <input type="text" name="hm_test_agency[<?=h($rt)?>]" class="form-control form-control-sm" placeholder="机构名称">
                    </div>
                    <div class="form-group mb-2">
                      <label class="small mb-0">检测日期</label>
                      <input type="date" name="hm_test_date[<?=h($rt)?>]" class="form-control form-control-sm">
                    </div>
                    <div class="form-group mb-2">
                      <label class="small mb-0">有效期</label>
                      <input type="date" name="hm_expiry_date[<?=h($rt)?>]" class="form-control form-control-sm">
                    </div>
                    <div class="form-group mb-0">
                      <label class="small mb-0">报告文件</label>
                      <div class="custom-file">
                        <input type="file" name="hm_report_file[<?=h($rt)?>]" class="custom-file-input hm-report-file" accept=".pdf,.xls,.xlsx" data-type="<?=h($rt)?>">
                        <label class="custom-file-label custom-file-label-sm">选择文件</label>
                      </div>
                    </div>
                    <small class="text-muted hm-ocr-status d-block mt-1"></small>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="text-right mt-3">
              <button type="submit" class="btn btn-outline-secondary px-4 mr-2" onclick="document.getElementById('formStep').value=2"><i class="fas fa-arrow-left mr-1"></i> 上一步</button>
              <button type="submit" class="btn btn-primary px-4" onclick="document.getElementById('formStep').value=4" id="step3NextBtn">下一步<i class="fas fa-arrow-right ml-1"></i></button>
            </div>
          </div>

          <script>
          document.querySelectorAll('.hm-report-file').forEach(function(input) {
            input.addEventListener('change', function() {
              var file = this.files[0];
              if (!file) return;
              var label = this.nextElementSibling;
              if (label) label.textContent = file.name;
              var ext = file.name.split('.').pop().toLowerCase();
              if (ext !== 'pdf') return;
              var status = this.closest('.col-md-3').querySelector('.hm-ocr-status');
              if (!status) return;
              var fd = new FormData();
              fd.append('file', file);
              status.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>正在识别...';
              status.className = 'text-info';
              fetch('/report-recognize.php', {method:'POST', body:fd})
                .then(function(r) { return r.json(); })
                .then(function(d) {
                  if (d.success && d.data) {
                    var box = status.closest('.col-md-3');
                    var filled = 0;
                    if (d.data.report_no) { box.querySelector('[name^="hm_report_no"]').value = d.data.report_no; filled++; }
                    if (d.data.test_date) { box.querySelector('[name^="hm_test_date"]').value = d.data.test_date; filled++; }
                    if (d.data.test_agency) { box.querySelector('[name^="hm_test_agency"]').value = d.data.test_agency; filled++; }
                    if (d.data.expiry_date) { box.querySelector('[name^="hm_expiry_date"]').value = d.data.expiry_date; filled++; }
                    status.innerHTML = '<i class="fas fa-check-circle mr-1"></i>识别成功，已填充' + filled + '个字段';
                    status.className = 'text-success';
                  } else {
                    status.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>' + (d.message || '识别失败');
                    status.className = 'text-danger';
                  }
                })
                .catch(function() {
                  status.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>网络错误';
                  status.className = 'text-danger';
                });
            });
          });
          </script>

          <!-- 步骤4: 确认提交 -->
          <div class="step-panel <?=($currentStep ?? 1) !== 4 ? 'd-none' : ''?>" id="step4">
            <div class="py-4">
              <div class="circle-success">
                <i class="fas fa-check-circle fa-3x text-success"></i>
              </div>
              <h5 class="text-center">确认提交</h5>
              <p class="text-muted text-center mb-4">请确认以下信息无误后提交</p>
              <table class="table table-sm text-left">
                <tr><td class="td-label">名称</td><td><?=h($item['material_name'] ?? '-')?></td></tr>
                <tr><td>供应商</td><td><?=h($item['supplier_name'] ?? '-')?></td></tr>
                <tr><td>用量</td><td><?=h($item['weight_mg'] ?? '0')?> <?=h($item['weight_unit'] ?? 'g')?></td></tr>
                <tr><td class="align-top">成分</td><td>
                  <?php if (empty($composition)): ?>
                    <span class="text-muted">-</span>
                  <?php else: ?>
                    <?php foreach ($composition as $c): ?>
                    <span class="badge badge-soft-secondary mr-1 mb-1 badge-label">
                      <?=h($c['name_cn'] ?: $c['substance_name'])?>
                      <?php if ($c['name_en'] ?? $c['substance_name']): ?><small class="text-muted"><?=h($c['name_en'] ?: $c['substance_name'])?></small><?php endif; ?>
                      <small class="text-muted ml-2"><?=h($c['cas_no'] === '__others__' ? '-' : ($c['cas_no'] ?? ''))?></small>
                      <span class="ml-3 font-weight-bold"><?=h($c['percentage'] ?? '')?>%</span>
                    </span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </td></tr>
                <tr><td class="align-top">环保报告</td><td>
                  <?php
                  $currentReports = array_filter($reports, fn($r) => $r['status'] === 'current');
                  if (empty($currentReports)): ?>
                    <span class="text-muted">-</span>
                  <?php else: ?>
                    <?php foreach ($currentReports as $r): ?>
                    <div class="mb-2">
                      <span class="badge badge-soft-info"><?=h($r['report_type'])?></span>
                      <strong><?=h($r['report_no'] ?: '-')?></strong>
                      <small class="text-muted ml-2"><?=h($r['test_agency'] ?: '')?> <?=h($r['test_date'] ?: '')?></small>
                      <?php if ($r['file_path']): ?>
                      <div class="small text-muted" style="word-break:break-all"><i class="fas fa-file mr-1"></i><?=h(basename($r['file_path']))?></div>
                      <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </td></tr>
              </table>
            </div>
            <div class="text-right mt-3">
              <button type="submit" class="btn btn-outline-secondary px-4 mr-2" onclick="document.getElementById('formStep').value=3"><i class="fas fa-arrow-left mr-1"></i> 上一步</button>
              <button type="submit" class="btn btn-success px-5" onclick="document.getElementById('formStep').value=5"><i class="fas fa-check mr-1"></i> 提交</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
var currentStep = initStep || 1;
document.querySelectorAll('#stepIndicator li').forEach(function(el) {
  var s = parseInt(el.dataset.step);
  el.classList.toggle('active', s === currentStep);
  el.classList.toggle('done', s < currentStep);
});
function calcTotal() {
  var total = 0;
  document.querySelectorAll('#compTable tbody [name="comp_pct[]"]').forEach(function(inp) {
    total += parseFloat(inp.value) || 0;
  });
  document.getElementById('totalPct').textContent = total.toFixed(2);
  var status = document.getElementById('totalStatus');
  if (total >= 99.99 && total <= 100.01) {
    status.innerHTML = '<span class="badge badge-success">正确</span>';
  } else {
    status.innerHTML = '<span class="badge badge-danger">需100%</span>';
  }
  return total;
}
document.getElementById('compTable')?.addEventListener('input', function(e) {
  if (e.target && e.target.name === 'comp_pct[]') calcTotal();
});
if (currentStep === 2) { setTimeout(calcTotal, 100); }

function searchSubstance() {
  var q = document.getElementById('substanceQuery').value.trim();
  if (!q) return;
  var btn = event.target && event.target.tagName === 'BUTTON' ? event.target : document.querySelector('.input-group-append .btn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>搜索中..'; }
  document.getElementById('substanceResults').innerHTML = '<div class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>正在搜索...</div>';
  fetch('/substance-lookup.php?q=' + encodeURIComponent(q))
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var html = '';
      if (data.error) {
        html = '<div class="alert alert-danger py-2">' + data.error + '</div>';
      } else if (data.length === 0) {
        html = '<div class="alert alert-warning py-2">未找到相关物质</div>';
      } else {
        html = '<table class="table table-sm table-bordered"><thead class="thead-light"><tr><th style="width:18%">CAS</th><th style="width:20%">中文名</th><th style="width:30%">英文名</th><th style="width:18%">分子式</th><th style="width:14%">操作</th></tr></thead><tbody>';
        for (var i = 0; i < data.length; i++) {
          var cn = data[i].name_cn || '';
          html += '<tr><td>' + data[i].cas + '</td><td>' + cn + '</td><td>' + data[i].name + '</td><td>' + (data[i].formula || '-') + '</td>';
          var nameCn = (data[i].name_cn || '').replace(/'/g,"\\'");
          html += '<td><button type="button" class="btn btn-sm btn-success" onclick="addSubstance(\'' + data[i].name.replace(/'/g,"\\'") + '\',\'' + nameCn + '\',\'' + data[i].cas + '\')">选择</button></td></tr>';
        }
        html += '</tbody></table>';
      }
      document.getElementById('substanceResults').innerHTML = html;
    })
    .catch(function() {
      document.getElementById('substanceResults').innerHTML = '<div class="alert alert-danger py-2">网络错误，请稍后重试</div>';
    })
    .finally(function() {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-search"></i> 搜索'; }
    });
}
function addSubstance(name, nameCn, cas) {
  var exists = document.querySelector('#compTable tbody input[value="' + cas + '"]');
  if (exists && exists.closest('tr')) { toastr.warning('该物质已添加'); return; }
  var safe = function(s) { return (s||'').replace(/'/g,"&#39;"); };
  var html = '<tr>';
  html += '<td>' + cas + '<input type="hidden" name="comp_cas[]" value="' + safe(cas) + '"></td>';
  html += '<td>' + (nameCn || '') + '<input type="hidden" name="comp_name_cn[]" value="' + safe(nameCn) + '"></td>';
  html += '<td>' + name + '<input type="hidden" name="comp_name[]" value="' + safe(name) + '"></td>';
  html += '<td><input type="number" name="comp_pct[]" class="form-control form-control-sm" step="0.01" style="width:80px"></td>';
  html += '<td><input type="number" name="comp_min[]" class="form-control form-control-sm" step="0.01" style="width:80px"></td>';
  html += '<td><input type="number" name="comp_max[]" class="form-control form-control-sm" step="0.01" style="width:80px"></td>';
  html += '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()"><i class="fas fa-trash"></i></button></td>';
  html += '</tr>';
  var tbody = document.querySelector('#compTable tbody');
  tbody.insertAdjacentHTML('beforeend', html);
  document.getElementById('substanceResults').innerHTML = '';
  document.getElementById('substanceQuery').value = '';
  toastr.success('已添加 ' + (nameCn || name));
  setTimeout(calcTotal, 50);
}
function addOthers() {
  var exists = document.querySelector('#compTable tbody input[value="__others__"]');
  if (exists && exists.closest('tr')) { toastr.warning('其他项已存在，请直接修改含量'); return; }
  var html = '<tr>';
  html += '<td>-<input type="hidden" name="comp_cas[]" value="__others__"></td>';
  html += '<td>其他项<input type="hidden" name="comp_name_cn[]" value="其他项"></td>';
  html += '<td>Others<input type="hidden" name="comp_name[]" value="Others"></td>';
  html += '<td><input type="number" name="comp_pct[]" class="form-control form-control-sm" step="0.01" style="width:80px"></td>';
  html += '<td><input type="number" name="comp_min[]" class="form-control form-control-sm" step="0.01" style="width:80px"></td>';
  html += '<td><input type="number" name="comp_max[]" class="form-control form-control-sm" step="0.01" style="width:80px"></td>';
  html += '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove();calcTotal()"><i class="fas fa-trash"></i></button></td>';
  html += '</tr>';
  document.querySelector('#compTable tbody').insertAdjacentHTML('beforeend', html);
  document.getElementById('substanceResults').innerHTML = '';
  toastr.success('已添加 其他项');
  setTimeout(calcTotal, 50);
}
</script>
