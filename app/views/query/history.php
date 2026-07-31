<div class="page-header">
  <h5><i class="fas fa-history"></i> 变更历史</h5>
</div>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-inline">
      <input type="date" name="start_date" class="form-control form-control-sm mr-2" value="<?=h($_GET['start_date'] ?? '')?>">
      <input type="date" name="end_date" class="form-control form-control-sm mr-2" value="<?=h($_GET['end_date'] ?? '')?>">
      <select name="module" class="form-control form-control-sm mr-2">
        <option value="">全部模块</option>
        <?php foreach ($modules as $m): ?>
        <option value="<?=h($m['module'])?>" <?=($_GET['module'] ?? '') === $m['module'] ? 'selected' : ''?>><?=h($m['module'])?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> 查询</button>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th style="width:160px">时间</th><th style="width:240px">操作人</th><th style="width:80px">操作</th><th style="width:100px">模块</th><th>详情</th><th style="width:140px">IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td><small><?=h($l['created_at'])?></small></td>
          <td><?=h($l['realname'] ?? $l['user_id'])?></td>
          <td><span class="badge badge-soft-info"><?=h($l['action'])?></span></td>
          <td><?=h($l['module'])?></td>
          <td><small><?=h($l['detail'])?></small></td>
          <td><small><?=h($l['ip_address'])?></small></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
