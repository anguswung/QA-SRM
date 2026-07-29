<div class="page-header">
  <h5><i class="fas fa-exclamation-triangle"></i> 预警中心</h5>
  <div>
    <form method="post" class="inline-form">
      <input type="hidden" name="action" value="generate">
      <button class="btn btn-sm btn-warning"><i class="fas fa-sync"></i> 生成预警</button>
    </form>
  </div>
</div>
<div class="row">
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="stat-icon text-danger"><i class="fas fa-exclamation-circle"></i></div>
      <div class="stat-value"><?php $total = 0; foreach ($stats as $s) $total += $s['pending_count']; echo $total; ?></div>
      <div class="stat-label">待处理</div>
    </div>
  </div>
  <?php foreach ($stats as $s): ?>
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="stat-icon text-info"><i class="fas fa-tag"></i></div>
      <div class="stat-value"><?=(int)$s['cnt']?></div>
      <div class="stat-label"><?=h($s['warning_type'])?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<div class="search-bar">
  <form method="get" class="form-inline">
    <select name="type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部类型</option>
      <option value="report_expiring" <?=($_GET['type'] ?? '') === 'report_expiring' ? 'selected' : ''?>>报告到期</option>
      <option value="over_limit" <?=($_GET['type'] ?? '') === 'over_limit' ? 'selected' : ''?>>物质超标</option>
    </select>
    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部状态</option>
      <option value="pending" <?=($_GET['status'] ?? '') === 'pending' ? 'selected' : ''?>>待处理</option>
      <option value="resolved" <?=($_GET['status'] ?? '') === 'resolved' ? 'selected' : ''?>>已处理</option>
    </select>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th>供应商</th><th>物料</th><th>类型</th><th>级别</th><th>描述</th><th>剩余天数</th><th>状态</th><th>创建时间</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($warnings as $w): ?>
        <tr>
          <td><?=h($w['supplier_name'] ?? '-')?></td>
          <td><?=h($w['material_name'] ?? '-')?></td>
          <td><span class="badge badge-soft-info"><?=h($w['warning_type'])?></span></td>
          <td><?=statusBadge($w['warning_level'])?></td>
          <td><small><?=h($w['warning_desc'])?></small></td>
          <td><?=h($w['days_left'] ?? '-')?>天</td>
          <td><?=$w['solved'] ? '<span class="status-tag success">已处理</span>' : '<span class="status-tag warning">待处理</span>'?></td>
          <td><small><?=h($w['created_at'])?></small></td>
          <td>
            <?php if (!$w['solved']): ?>
            <form method="post" class="d-inline">
              <input type="hidden" name="action" value="resolve">
              <input type="hidden" name="id" value="<?=$w['id']?>">
              <button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i> 处理</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
