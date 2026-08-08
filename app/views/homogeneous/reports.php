<div class="page-header">
  <h4><i class="fas fa-file-alt"></i> 检测报告 - <?=h($item['material_name'])?></h4>
  <div>
    <a href="/homogeneous" class="btn btn-success"><i class="fas fa-list"></i> 返回列表</a>
  </div>
</div>

<?php if (empty($reports)): ?>
<div class="card">
  <div class="card-body text-center text-muted py-4">
    暂无报告，点击上传报告添加
    <?php if (!empty($noUploadData)): ?>
    <div class="mt-3">
      <a href="/homogeneous?action=edit&id=<?=h($item['id'])?>&step=3" class="btn btn-primary"><i class="fas fa-upload"></i> 上传资料</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="card-header">
    <ul class="nav nav-tabs card-header-tabs" id="reportTabs" role="tablist">
      <?php foreach ($reports as $i => $r): ?>
      <li class="nav-item">
        <a class="nav-link <?=($activeReportId ?? 0) == $r['id'] ? 'active' : ($i === 0 && !$activeReportId ? 'active' : '')?>" id="tab-<?=$r['id']?>" data-bs-toggle="tab" href="#report-<?=$r['id']?>" role="tab">
          <?=h($r['report_type'] ?: '报告')?> v<?=(int)($r['version'] ?? 1)?>
          <?php if (($r['status'] ?? 'current') === 'superseded'): ?><span class="badge bg-secondary text-white">旧版</span><?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card-body tab-content" id="reportTabContent">
    <?php foreach ($reports as $i => $r): ?>
    <?php $expired = $r['expiry_date'] && strtotime($r['expiry_date']) < time(); ?>
    <?php $elems = $reportElements[$r['id']] ?? []; ?>
    <?php if (in_array($r['report_type'], ['RoHS', 'REACH', 'HF'])):
      $defaults = stdOfficialLimits($r['report_type']);
    else:
    if (!empty($defaults)):
      $merged = [];
      foreach ($defaults as $def) {
        $found = null;
        foreach ($elems as $e) {
          if (isset($e['cas_no']) && $e['cas_no'] === $def['cas_no']) { $found = $e; break; }
        }
        if ($found) {
          $found['substance_name'] = $def['substance_name'];
          $merged[] = $found;
        } else {
          $merged[] = $def;
        }
      }
      foreach ($elems as $e) {
        $exists = false;
        foreach ($merged as $m) { if (($m['cas_no'] ?? '') === ($e['cas_no'] ?? '')) { $exists = true; break; } }
        if (!$exists) $merged[] = $e;
      }
      $elems = $merged;
    endif;
    endif;
    ?>
    <div class="tab-pane fade <?=(($activeReportId ?? 0) == $r['id'] ? 'show active' : ($i === 0 && !$activeReportId ? 'show active' : ''))?>" id="report-<?=$r['id']?>" role="tabpanel">
      <div class="row">
        <div class="col-md-5">
            <div class="card">
              <div class="card-header">报告信息</div>
              <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                  <colgroup><col class="w-100px"><col></colgroup>
                  
                <tr><th>报告编号</th><td><?=h($r['report_no'] ?: '-')?></td></tr>
                <tr><th>报告版本</th><td>v<?=(int)($r['version'] ?? 1)?></td></tr>
                <?php if ($r['report_type'] !== 'MSDS'): ?>
                <tr><th>检测机构</th><td><?=h($r['test_agency'] ?: '-')?></td></tr>
                <tr><th>检测日期</th><td><?=h($r['test_date'] ?: '-')?></td></tr>
                <tr>
                  <th>有效期</th>
                  <td>
                    <?=h($r['expiry_date'] ?: '-')?>
                    <?php if ($expired): ?><span class="status-tag danger">已过期</span><?php endif; ?>
                    <?php if ($r['expiry_date'] && !$expired && strtotime($r['expiry_date']) < strtotime('+30 days')): ?><span class="status-tag warning">即将到期</span><?php endif; ?>
                  </td>
                </tr>
                <?php endif; ?>
                <tr><th>文件</th><td><?php if ($r['file_path']): ?><a href="<?=h(fileUrl($r['file_path']))?>" target="_blank" class="text-info"><i class="fas fa-file-pdf fa-lg"></i> <?=h(basename($r['file_path']))?></a><?php else: ?><span class="text-muted">-</span><?php endif; ?></td></tr>
                <tr><th>上传时间</th><td><small><?=h($r['created_at'])?></small></td></tr>
              </table>
            </div>
          </div>
        </div>
        <?php if ($r['report_type'] !== 'MSDS'): ?>
        <div class="col-md-7">
          <div class="card">
            <div class="card-header">元素数值清单</div>
            <div class="card-body p-0">
              <?php if (in_array($r['report_type'], ['RoHS', 'REACH', 'HF'])): ?>
              <table class="table table-hover table-sm mb-0">
                <thead><tr><th style="width:40px" class="col-id">ID</th><th>物质名称</th><th>CAS</th><th>检测值</th><th>限值</th></tr></thead>
                <tbody>

                  <?php if ($elems): ?>
                  <?php foreach ($elems as $j => $e): ?>
                  <tr><td class="col-id"><?=h($e['id'] ?? '')?></td><td><?=h($e['substance_name'])?></td><td><?=h($e['cas_no'] ?: '-')?></td><td class=""><?=h($e['content_value'] ?? '-')?></td><td class=""><?=h($e['limit_value'])?></td></tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr><td colspan="5" class="text-muted">暂无测试元素数据</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
              <?php else: ?>
              <table class="table table-hover table-sm mb-0">
                <thead><tr><th style="width:40px" class="col-id">ID</th><th>物质中文</th><th>物质名称</th><th>CAS</th><th>含量(%)</th></tr></thead>
                <tbody>

                  <?php if ($composition): ?>
                  <?php foreach ($composition as $j => $c): ?>
                  <tr><td class="col-id"><?=h($c['id'] ?? '')?></td><td><?=h($c['name_cn'] ?: '-')?></td><td><?=h($c['substance_name'])?></td><td><?=h($c['cas_no'] ?: '-')?></td><td><?=h($c['percentage'])?>%</td></tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr><td colspan="5" class="text-muted">暂无成分数据</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($r['report_type'] !== 'MSDS'): ?>
      <div class="card mt-3">
        <div class="card-header">测试数据 — 手工录入</div>
        <div class="card-body p-0">
          <form method="post" action="/homogeneous?action=reports&id=<?=$item['id']?>"><?=csrf_field()?>
            <input type="hidden" name="action" value="save_elements">
            <input type="hidden" name="hm_report_id" value="<?=$r['id']?>">
            <input type="hidden" name="hm_id" value="<?=$item['id']?>">
            <div class="table-responsive">
              <table class="table table-bordered table-sm mb-0" id="elemTable-<?=$r['id']?>">
                <thead>
                  <tr>
                    <th style="width:70px"></th>
                    <?php foreach ($elems as $j => $e):
                      $name = $e['substance_name'] ?? '';
                      // 表头两行显示：从第一个括号（中英文）处裁断
                      $nm1 = $name; $nm2 = '';
                      if (preg_match('/^(.+?)([（(].*)$/u', $name, $mm)) { $nm1 = $mm[1]; $nm2 = $mm[2]; }
                      $len = mb_strlen($name, 'UTF-8');
                      $w = max(65, min(120, $len * 8));
                    ?>
                    <th style="width:<?=$w?>px;text-align:center;vertical-align:top;line-height:1.3">
                      <?=h($nm1)?><?php if ($nm2): ?><br><?=h($nm2)?><?php endif; ?>
                      <input type="hidden" name="substance_name[<?=$j?>]" value="<?=h($name)?>">
                      <input type="hidden" name="cas_no[<?=$j?>]" value="<?=h($e['cas_no'] ?? '')?>">
                      <input type="hidden" name="limit_value[<?=$j?>]" value="<?=h($e['limit_value'])?>">
                      <input type="hidden" name="limit_unit[<?=$j?>]" value="<?=h($e['limit_unit'] ?? 'mg/kg')?>">
                      <input type="hidden" name="unit[<?=$j?>]" value="mg/kg">
                    </th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>

                  <tr>
                    <th class="text-muted va-middle" style="font-size:12px">检测值</th>
                    <?php foreach ($elems as $j => $e): ?>
                    <td><input type="text" name="content_value[<?=$j?>]" value="<?=h($e['content_value'] ?? '')?>" class="form-control form-control-sm text-center" placeholder="0或ND" pattern="[0-9.]*|ND|nd|Nd" title="请输入数字或ND"></td>
                    <?php endforeach; ?>
                  </tr>
                  <tr>
                    <th class="text-muted va-middle" style="font-size:12px" title="客户方（供应商或客户）要求限值">客户方限值</th>
                    <?php foreach ($elems as $j => $e): ?>
                    <td class="text-muted" style="font-size:11px;vertical-align:middle"><?=h($e['limit_value'])?></td>
                    <?php endforeach; ?>
                  </tr>
                  <tr>
                    <th class="text-primary va-middle" style="font-size:12px" title="管理员在系统管理-内部标准限值中配置的己方内部标准">内部标准限值</th>
                    <?php foreach ($elems as $j => $e):
                      $sl = $stdLimits[$r['id']][$e['cas_no'] ?? ''] ?? null;
                      $internal = $sl ? $sl['internal'] : null;
                    ?>
                    <td class="text-primary" style="font-size:11px;vertical-align:middle;font-weight:bold">
                      <?=($internal !== null) ? h($internal) : '<span class="text-muted">-</span>'?>
                    </td>
                    <?php endforeach; ?>
                  </tr>
                  <tr>
                    <th class="text-success va-middle" style="font-size:12px" title="官方（指令/法规）限值">官方限值</th>
                    <?php foreach ($elems as $j => $e):
                      $sl = $stdLimits[$r['id']][$e['cas_no'] ?? ''] ?? null;
                      $official = $sl ? $sl['official'] : null;
                    ?>
                    <td class="text-success" style="font-size:11px;vertical-align:middle"><?=($official !== null) ? h($official) : '-'?></td>
                    <?php endforeach; ?>
                  </tr>
                  <tr>
                    <th class="text-muted va-middle" style="font-size:12px">单位</th>
                    <?php foreach ($elems as $j => $e): ?>
                    <td class="text-muted va-middle" style="font-size:11px">mg/kg</td>
                    <?php endforeach; ?>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="p-3 d-flex justify-content-between border-top">
              <div>
                <?php if (can('homogeneous.edit')): ?><button type="button" class="btn btn-sm btn-outline-primary" onclick="addReachCol(<?=$r['id']?>)"><i class="fas fa-plus"></i> 添加元素</button><?php endif; ?>
                <small class="text-muted me-2">检测值可输入数字或ND（未检出）</small>
              </div>
              <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> 保存</button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
function addReachCol(reportId) {
  var table = document.querySelector('#elemTable-' + reportId);
  var thead = table.querySelector('thead tr');
  var tbody = table.querySelector('tbody');
  var colNum = thead.querySelectorAll('th').length;

  var th = document.createElement('th');
  th.style.cssText = 'width:80px;text-align:center;vertical-align:top';
  th.innerHTML =
    '<input type="text" name="substance_name[' + colNum + ']" class="form-control form-control-sm" placeholder="物质名称" style="font-weight:normal;text-align:center;width:70px">' +
    '<input type="hidden" name="cas_no[' + colNum + ']" value="">' +
    '<input type="hidden" name="limit_value[' + colNum + ']" value="0">' +
    '<input type="hidden" name="limit_unit[' + colNum + ']" value="mg/kg">' +
    '<input type="hidden" name="unit[' + colNum + ']" value="mg/kg">';
  thead.appendChild(th);

  var rows = tbody.querySelectorAll('tr');
  var tdVal = document.createElement('td');
  tdVal.innerHTML = '<input type="text" name="content_value[' + colNum + ']" class="form-control form-control-sm text-center" style="width:60px" placeholder="0或ND" pattern="[0-9.]*|ND|nd|Nd" title="请输入数字或ND">';
  rows[0].appendChild(tdVal);

  var tdLimit = document.createElement('td');
  tdLimit.className = 'text-center text-muted small';
  tdLimit.style.cssText = 'vertical-align:middle';
  tdLimit.textContent = '-';
  rows[1].appendChild(tdLimit);

  var tdInternal = document.createElement('td');
  tdInternal.className = 'text-center small text-muted';
  tdInternal.style.cssText = 'vertical-align:middle';
  tdInternal.textContent = '-';
  rows[2].appendChild(tdInternal);

  var tdOfficial = document.createElement('td');
  tdOfficial.className = 'text-center small text-muted';
  tdOfficial.style.cssText = 'vertical-align:middle';
  tdOfficial.textContent = '-';
  rows[3].appendChild(tdOfficial);

  var tdUnit = document.createElement('td');
  tdUnit.className = 'text-center text-muted small';
  tdUnit.style.cssText = 'vertical-align:middle';
  tdUnit.textContent = 'mg/kg';
  rows[4].appendChild(tdUnit);
}
</script>

