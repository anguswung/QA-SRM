<div class="page-header">
  <h4><i class="fas fa-balance-scale"></i> 内部标准限值</h4>
</div>
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-info-circle ms-1"></i> 说明</div>
  <div class="card-body small text-muted">
    在此维护<span class="text-primary">己方内部标准限值</span>，与<span class="text-secondary">客户方限值</span>、<span class="text-success">官方（指令/法规）限值</span>对照。
    内部标准将显示在"均质材料-报告-手工录入"表单中，便于与客户方、官方限值直观对比。
  </div>
</div>

<div class="card">
  <div class="card-header">
    <ul class="nav nav-tabs card-header-tabs" id="materialTabs">
      <?php foreach ($types as $t): ?>
      <li class="nav-item">
        <a class="nav-link <?=($activeType === $t) ? 'active' : ''?>" data-bs-toggle="tab" href="#stdTab-<?=$t?>"><?=h($t)?></a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card-body tab-content">
    <?php foreach ($types as $t): ?>
    <div class="tab-pane <?=($activeType===$t)?'active':''?>" id="stdTab-<?=$t?>">
      <form method="post"><?=csrf_field()?>
        <input type="hidden" name="report_type" value="<?=h($t)?>">
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0">
            <thead>
              <tr>
                <th>物质名称</th>
                <th style="width:150px">CAS号</th>
                <th class="text-success" style="width:120px">官方限值</th>
                <th style="width:160px">内部标准限值</th>
                <th style="width:110px">单位</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sections[$t] as $idx => $row): ?>
              <tr>
                <td><?=h($row['substance_name'])?>
                  <input type="hidden" name="substance_name[]" value="<?=h($row['substance_name'])?>">
                  <input type="hidden" name="cas_no[]" value="<?=h($row['cas_no'])?>">
                </td>
                <td><?=h($row['cas_no'])?></td>
                <td class="text-success fw-bold"><?=h($row['official'])?></td>
                <td>
                  <input type="number" name="limit_value[]" class="form-control form-control-sm" value="<?=($row['internal'] !== null) ? h($row['internal']) : ''?>" placeholder="未设置" step="any" min="0">
                </td>
                <td>mg/kg</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="p-3 border-top text-end">
          <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> 保存<?=h($t)?>内部标准</button>
        </div>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>