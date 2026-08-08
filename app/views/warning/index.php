<div class="page-header">
  <h4><i class="fas fa-exclamation-triangle"></i> 预警中心</h4>
  <div>
    <?php if (!isSupplier()): ?>
    <form method="post" class="inline-form"><?=csrf_field()?>
      <input type="hidden" name="action" value="generate">
      <button class="btn btn-primary"><i class="fas fa-sync"></i> 生成预警</button>
    </form>
    <?php endif; ?>
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
<div class="search-bar mt-3 mb-3">
  <form method="get" class="d-flex flex-wrap align-items-center gap-2">
    <select name="type" class="form-control form-control-sm w-auto ms-2" onchange="this.form.submit()">
      <option value="">全部类型</option>
      <option value="report_expiring" <?=($_GET['type'] ?? '') === 'report_expiring' ? 'selected' : ''?>>报告到期</option>
      <option value="over_limit" <?=($_GET['type'] ?? '') === 'over_limit' ? 'selected' : ''?>>物质超标</option>
    </select>
    <select name="status" class="form-control form-control-sm w-auto ms-2" onchange="this.form.submit()">
      <option value="">全部状态</option>
      <option value="pending" <?=($_GET['status'] ?? '') === 'pending' ? 'selected' : ''?>>待处理</option>
      <option value="resolved" <?=($_GET['status'] ?? '') === 'resolved' ? 'selected' : ''?>>已处理</option>
    </select>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/warning?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:10%">供应商</th><th style="width:14%">物料</th><th style="width:8%">类型</th><th style="width:7%">级别</th><th>描述</th><th style="width:9%">剩余天数</th><th style="width:8%">状态</th><th style="width:12%">创建时间</th><th style="width:8%">操作</th></tr></thead>
      <tbody>
        <tr><td colspan="10" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
