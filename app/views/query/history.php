<div class="page-header">
  <h4><i class="fas fa-history"></i> 变更历史</h4>
</div>
<div class="card">
  <div class="card-body">
    <form method="get" class="d-flex flex-wrap align-items-center gap-2">
      <input type="date" name="start_date" class="form-control form-control-sm w-auto ms-2" value="<?=h($_GET['start_date'] ?? '')?>">
      <input type="date" name="end_date" class="form-control form-control-sm w-auto ms-2" value="<?=h($_GET['end_date'] ?? '')?>">
      <select name="module" class="form-control form-control-sm w-auto ms-2">
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
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:14%">时间</th><th style="width:20%">操作人</th><th style="width:8%">操作</th><th style="width:10%">模块</th><th>详情</th><th style="width:12%">IP</th></tr></thead>
      <tbody>
<?php $__rnum = 0; ?>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td class="col-id"><?=h($l['id'] ?? '')?></td><td><small><?=h($l['created_at'])?></small></td>
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
