<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white py-3">
        <h5 class="mb-0">
          <i class="fas fa-<?=$id ? 'edit' : 'plus'?> mr-2"></i>
          <?php if (!$id): $btnType = $_GET['type'] ?? ''; ?><?=$btnType === 'hm' ? '新增均质原材料' : ($btnType === 'part' ? '新增零部件' : h($title))?><?php else: ?><?=h($title)?><?php endif; ?>
        </h5>
      </div>
      <div class="card-body">
        <form method="post" id="materialForm" enctype="multipart/form-data" action="/material"><?=csrf_field()?>
          <input type="hidden" name="action" value="<?=$id ? 'update' : 'create'?>">
          <?php if ($id): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
          <input type="hidden" name="step" id="formStep" value="<?=$currentStep ?? 1?>">
          <?php $isHM = ($id ? ($item['spec'] ?? '') === '均质原材料' : (($_GET['type'] ?? '') === 'hm')); ?>
          <?php $fixedSpec = $id ? ($item['spec'] ?? '均质原材料') : (($_GET['type'] ?? '') === 'hm' ? '均质原材料' : '零部件'); ?>
          <ul class="steps" id="stepIndicator">
            <li data-step="1"><div class="step-circle">1</div><div class="step-label">填写基本信息</div></li>
            <li data-step="2"><div class="step-circle">2</div><div class="step-label">配置成分组成</div></li>
            <li data-step="3"><div class="step-circle">3</div><div class="step-label">完成创建</div></li>
          </ul>
          <script>var initStep = <?=$currentStep ?? 1?>;</script>
          <!-- 步骤1: 基本信息 -->
          <div class="step-panel <?=($currentStep ?? 1) !== 1 ? 'd-none' : ''?>" id="step1">
            <div class="form-row">
              <div class="form-group col-md-12">
                <label>物料名称 <span class="text-danger">*</span></label>
                <input type="text" name="material_name" class="form-control" value="<?=h($item['material_name'] ?? '')?>" required placeholder="请输入物料名称">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label>物料编号 <?=!$isHM ? '<span class="text-danger">*</span>' : ''?></label>
                <div class="input-group">
                  <?php
                  $companyShort = '';
                  foreach ($configs as $c) { if ($c['config_key'] === 'company_short_name') { $companyShort = $c['config_value']; break; } }
                  ?>
                  <?php if ($companyShort): ?>
                  <div class="input-group-prepend"><span class="input-group-text"><?=h($companyShort)?></span></div>
                  <?php endif; ?>
                  <input type="text" name="material_no" class="form-control" value="<?=h($item['material_no'] ?? '')?>" <?=!$isHM ? 'required' : ''?> placeholder="<?=$isHM ? '均质材料无需编号' : ''?>" <?php if (!empty($isImported)): ?>readonly title="批量导入的物料编号不可修改"<?php endif; ?>>
                </div>
                <?php if (!empty($isImported)): ?><small class="text-muted">批量导入的物料编号不可修改</small><?php endif; ?>
              </div>
              <div class="form-group col-md-4">
                <label>制造商料号(MPN)</label>
                <input type="text" name="mpn" class="form-control" value="<?=h($item['mpn'] ?? '')?>">
              </div>
              <div class="form-group col-md-4">
                <label>单重(g)</label>
                <input type="number" name="unit_weight" class="form-control" step="0.001" value="<?=h($item['unit_weight'] ?? '')?>" id="unitWeight">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>性质</label>
                <input type="text" class="form-control" value="<?=h($fixedSpec)?>" readonly>
                <input type="hidden" name="spec" value="<?=h($fixedSpec)?>">
              </div>
              <div class="form-group col-md-6">
                <label>供应商</label>
                <?php if (isSupplier()): ?>
                <?php $spName = DB::getInstance()->single("SELECT name FROM supplier WHERE id=?", [userSupplierId()]); ?>
                <input type="text" class="form-control" value="<?=h($spName)?>" readonly>
                <input type="hidden" name="supplier_id" value="<?=userSupplierId()?>">
                <?php else: ?>
                <select name="supplier_id" class="form-control">
                  <option value="">请选择供应商</option>
                  <?php foreach ($suppliers as $s): ?>
                  <option value="<?=$s['id']?>" <?=($item['supplier_id'] ?? ($isHM ? 2 : 0)) == $s['id'] ? 'selected' : ''?>><?=h($s['code'])?> - <?=h($s['name'])?></option>
                  <?php endforeach; ?>
                </select>
                <?php endif; ?>
              </div>
            </div>
            <div class="text-right mt-3">
              <button type="submit" class="btn btn-primary px-4" onclick="document.getElementById('formStep').value=2">下一步<i class="fas fa-arrow-right ml-1"></i></button>
            </div>
          </div>

          <!-- 步骤2: 成分组成 -->
          <div class="step-panel <?=($currentStep ?? 1) !== 2 ? 'd-none' : ''?>" id="step2">
            <div class="border rounded p-3 bg-light">
              <h6 class="mb-3"><i class="fas fa-flask text-success mr-1"></i> 成分组成</h6>
              <div class="table-responsive">
                <table class="table table-bordered table-sm mb-2" id="compositionTable">
                  <thead class="thead-light">
                    <tr>
                      <th style="width:30%">均质材料</th>
                      <th style="width:15%">供应商</th>
                      <th style="width:15%">用量 <small class="text-muted">(%)</small></th>
                      <th style="width:15%">重量 <small class="text-muted">(g)</small></th>
                      <th style="width:10%">操作</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($composition)): foreach ($composition as $c):
                      $selHm = array_filter($hmMaterials, fn($h) => $h['id'] == $c['substance_id']);
                      $selHm = reset($selHm);
                      $wt = round(($item['unit_weight'] ?? 0) * ($c['min_percentage'] ?? 0) / 100, 4);
                    ?>
                    <tr data-hmid="<?=$c['substance_id']?>">
                      <td><span class="font-weight-bold"><?=h($selHm['material_name'] ?? '')?></span><input type="hidden" name="hm_id[]" value="<?=$c['substance_id']?>"></td>
                      <td class="text-muted small"><?=h(($selHm['supplier_name'] ?? '') ?: (DB::getInstance()->single("SELECT name FROM supplier WHERE id=?", [$selHm['supplier_id'] ?? 2]) ?: '第三方'))?></td>
                      <td><input type="number" name="min_percentage[]" class="form-control form-control-sm" step="0.01" min="0" max="100" value="<?=h($c['min_percentage'])?>" oninput="calcWeight(this);updateQuantityTotal()"></td>
                      <td class="comp-weight"><?=$wt?></td>
                      <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr class="empty-row">
                      <td colspan="5" class="text-center text-muted py-3">
                        <i class="fas fa-plus-circle mr-1"></i> 点击下方按钮从均质材料库中添加成分                      </td>
                    </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#hmPickerModal">
                  <i class="fas fa-plus"></i> 从库中选择均质材料
                </button>
                <div class="text-right">
                  <small class="text-muted">累计占比: </small>
                  <strong id="totalQuantity" class="<?=abs(0) < 0.01 ? '' : 'text-danger'?>">0%</strong>
                  <small class="text-muted"> / 100%</small>
                  <div class="progress" style="height:4px;width:120px;display:inline-block;vertical-align:middle;margin-left:8px">
                    <div class="progress-bar" id="quantityProgress" role="progressbar" style="width:0%"></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="text-right mt-3">
              <button type="button" class="btn btn-outline-secondary px-4 mr-2" onclick="location.href='?action=edit&id=<?=$id?>&step=1'"><i class="fas fa-arrow-left mr-1"></i> 上一步</button>
              <button type="submit" class="btn btn-primary px-4" onclick="document.getElementById('formStep').value=3">提交 <i class="fas fa-check ml-1"></i></button>
            </div>
          </div>

          <!-- 步骤3: 完成 -->
          <div class="step-panel <?=($currentStep ?? 1) !== 3 ? 'd-none' : ''?>" id="step3">
            <div class="text-center py-4">
              <div style="width:80px;height:80px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                <i class="fas fa-check-circle fa-3x text-success"></i>
              </div>
              <h5>确认提交</h5>
              <p class="text-muted mb-4">请确认以下信息无误后提交</p>
              <table class="table table-sm text-left" id="summaryTable">
                <tr><td style="width:100px">名称</td><td id="s_name"></td></tr>
                <tr><td>编号</td><td id="s_no"></td></tr>
                <tr><td>性质</td><td id="s_spec"></td></tr>
                <tr><td>供应商</td><td><?=h($supplierName ?? '-')?></td></tr>
                <tr><td>成分数</td><td><?=count($composition ?? [])?> 项</td></tr>
              </table>
            </div>
            <div class="text-right mt-3">
              <button type="button" class="btn btn-outline-secondary px-4 mr-2" onclick="location.href='?action=edit&id=<?=$id?>&step=2'"><i class="fas fa-arrow-left mr-1"></i> 上一步</button>
              <button type="submit" class="btn btn-success px-5" onclick="document.getElementById('formStep').value=4"><i class="fas fa-check mr-1"></i> 提交</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="hmPickerModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-flask text-success mr-1"></i> 选择均质材料</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="text" class="form-control mb-3" id="hmFilter" placeholder="搜索均质材料名称..." onkeyup="filterHM(this.value)">
        <table class="table table-hover table-sm mb-0" id="hmPickerTable">
          <thead><tr><th>名称</th><th>供应商</th><th>重量(mg)</th><th style="width:80px">操作</th></tr></thead>
          <tbody>
            <?php foreach ($hmMaterials as $hm): $spName = $hm['supplier_name'] ?: (DB::getInstance()->single("SELECT name FROM supplier WHERE id=?", [$hm['supplier_id']]) ?: '第三方'); ?>
            <tr data-hmid="<?=$hm['id']?>" data-name="<?=h($hm['material_name'])?>" data-supplier="<?=h($spName)?>">
              <td class="font-weight-bold"><?=h($hm['material_name'])?></td>
              <td class="small"><?=h($spName)?></td>
              <td class="small"><?=h($hm['weight_mg'] ?? 0)?></td>
              <td><button class="btn btn-sm btn-success" onclick="pickHM(<?=$hm['id']?>,'<?=h(addslashes($hm['material_name']))?>','', '<?=h(addslashes($spName))?>', <?=$hm['weight_mg'] ?? 0?>)">选择</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">取消</button>
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
if (currentStep === 3) {
  var map = {s_name:'material_name', s_no:'material_no', s_spec:'spec'};
  for (var id in map) {
    var el = document.getElementById(id);
    if (!el) continue;
    var name = map[id];
    var input = document.querySelector('[name="' + name + '"]');
    if (!input) continue;
    if (input.tagName === 'SELECT') {
      el.textContent = input.options[input.selectedIndex]?.text || input.value;
    } else {
      el.textContent = input.value || '-';
    }
  }
}
if (currentStep === 2) { setTimeout(updateQuantityTotal, 100); }
function updateQuantityTotal() {
  var total = 0;
  document.querySelectorAll('#compositionTable tbody tr:not(.empty-row) [name="min_percentage[]"]').forEach(function(inp) {
    total += parseFloat(inp.value) || 0;
  });
  var totalEl = document.getElementById('totalQuantity');
  totalEl.textContent = total.toFixed(2) + '%';
  totalEl.className = 'font-weight-bold ' + (Math.abs(total - 100) < 0.01 ? 'text-success' : (total > 100 ? 'text-danger' : ''));
  var bar = document.getElementById('quantityProgress');
  var pct = Math.min(total, 100);
  bar.style.width = pct + '%';
  bar.className = 'progress-bar ' + (total > 100 ? 'bg-danger' : (Math.abs(total - 100) < 0.01 ? 'bg-success' : 'bg-warning'));
}
document.getElementById('compositionTable').addEventListener('input', function(e) {
  if (e.target && e.target.name === 'min_percentage[]') updateQuantityTotal();
});
function pickHM(id, name, code, supplier, weightMg) {
  var exists = document.querySelector('#compositionTable tbody tr[data-hmid="' + id + '"]');
  if (exists) { toastr.warning('该均质材料已添加'); return; }
  var unitWt = parseFloat(document.querySelector('[name="unit_weight"]')?.value) || 0;
  var html = '<tr data-hmid="' + id + '">';
  html += '<td><span class="font-weight-bold">' + name + '</span><input type="hidden" name="hm_id[]" value="' + id + '"></td>';
  html += '<td class="text-muted small">' + (supplier || '第三方') + '</td>';
  html += '<td><input type="number" name="min_percentage[]" class="form-control form-control-sm" step="0.01" min="0" max="100" placeholder="%" oninput="calcWeight(this);updateQuantityTotal()"></td>';
  html += '<td class="comp-weight">0</td>';
  html += '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove();updateQuantityTotal()"><i class="fas fa-trash"></i></button></td>';
  html += '</tr>';
  var $tbody = $('#compositionTable tbody');
  $tbody.find('.empty-row').remove();
  $tbody.append(html);
  $('#hmPickerModal').modal('hide');
  setTimeout(updateQuantityTotal, 50);
}
function calcWeight(inp) {
  var unitWt = parseFloat(document.querySelector('[name="unit_weight"]')?.value) || 0;
  var pct = parseFloat(inp.value) || 0;
  var wt = (unitWt * pct / 100).toFixed(4);
  inp.closest('tr').querySelector('.comp-weight').textContent = wt;
}
function filterHM(val) {
  document.querySelectorAll('#hmPickerTable tbody tr').forEach(function(r) {
    r.style.display = r.getAttribute('data-name').toLowerCase().indexOf(val.toLowerCase()) > -1 ? '' : 'none';
  });
}
</script>
