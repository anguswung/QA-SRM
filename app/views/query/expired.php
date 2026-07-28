<div class="page-header">
  <h5><i class="fas fa-calendar-times"></i> 到期查询</h5>
</div>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-inline">
      <select name="range" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
        <option value="">全部</option>
        <option value="expired" <?=($_GET['range'] ?? '') === 'expired' ? 'selected' : ''?>>已过期</option>
        <option value="30days" <?=($_GET['range'] ?? '') === '30days' ? 'selected' : ''?>>30天内到期</option>
        <option value="60days" <?=($_GET['range'] ?? '') === '60days' ? 'selected' : ''?>>60天内到期</option>
        <option value="valid" <?=($_GET['range'] ?? '') === 'valid' ? 'selected' : ''?>>有效期内</option>
      </select>
      <input type="date" name="start_date" class="form-control form-control-sm mr-2" value="<?=h($_GET['start_date'] ?? '')?>">
      <input type="date" name="end_date" class="form-control form-control-sm mr-2" value="<?=h($_GET['end_date'] ?? '')?>">
      <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> 查询</button>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th>报告编号</th><th>物料</th><th>供应商</th><th>类型</th><th>有效期</th><th>剩余天数</th><th>状态</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td><?=h($r['report_no'])?></td>
          <td><a href="/material?action=detail&id=<?=$r['material_id']?>"><?=h($r['material_name'])?></a></td>
          <td><?=h($r['supplier_name'] ?? '-')?></td>
          <td><span class="badge badge-soft-info"><?=h($r['report_type'])?></span></td>
          <td><?=h($r['expiry_date'])?></td>
          <td>
            <?php $days = (int)$r['days_left']; ?>
            <?php if ($days < 0): ?><span class="text-danger font-weight-bold">已过期<?=abs($days)?>天</span>
            <?php elseif ($days <= 7): ?><span class="text-danger font-weight-bold"><?=$days?>天</span>
            <?php elseif ($days <= 30): ?><span class="text-warning font-weight-bold"><?=$days?>天</span>
            <?php else: ?><span class="text-success"><?=$days?>天</span>
            <?php endif; ?>
          </td>
          <td><?=statusBadge($r['audit_status'])?></td>
          <td>
            <a href="/report?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
