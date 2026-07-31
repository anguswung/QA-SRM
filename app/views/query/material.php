<div class="page-header">
  <h5><i class="fas fa-search"></i> 物料查询</h5>
</div>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-inline">
      <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="搜索编号/名称" value="<?=h($_GET['search'] ?? '')?>">
      <select name="env_status" class="form-control form-control-sm mr-2">
        <option value="">全部状态</option>
        <option value="compliant" <?=($_GET['env_status'] ?? '') === 'compliant' ? 'selected' : ''?>>合规</option>
        <option value="non_compliant" <?=($_GET['env_status'] ?? '') === 'non_compliant' ? 'selected' : ''?>>不合规</option>
        <option value="pending" <?=($_GET['env_status'] ?? '') === 'pending' ? 'selected' : ''?>>待评估</option>
      </select>
      <select name="audit_status" class="form-control form-control-sm mr-2">
        <option value="">全部审核</option>
        <option value="N" <?=($_GET['audit_status'] ?? '') === 'N' ? 'selected' : ''?>>待提交</option>
        <option value="S" <?=($_GET['audit_status'] ?? '') === 'S' ? 'selected' : ''?>>已提交</option>
        <option value="A" <?=($_GET['audit_status'] ?? '') === 'A' ? 'selected' : ''?>>已认证</option>
      </select>
      <?php if (isAdmin()): ?>
      <select name="supplier_id" class="form-control form-control-sm mr-2">
        <option value="">全部供应商</option>
        <?php foreach ($suppliers as $s): ?>
        <option value="<?=$s['id']?>" <?=($_GET['supplier_id'] ?? 0) == $s['id'] ? 'selected' : ''?>><?=h($s['name'])?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> 查询</button>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th style="width:120px">编号</th><th>名称</th><th style="width:140px">供应商</th><th style="width:80px">环保状态</th><th style="width:80px">审核</th><th style="width:60px">有效</th><th style="width:70px">报告数</th></tr></thead>
      <tbody>
        <?php foreach ($materials as $m): ?>
        <tr>
          <td><?=h($m['material_no'])?></td>
          <td><a href="/material?action=detail&id=<?=$m['id']?>"><?=h($m['material_name'])?></a></td>
          <td><?=h($m['supplier_name'] ?? '-')?></td>
          <td><?=statusBadge($m['env_status'])?></td>
          <td><?=statusBadge($m['audit_status'])?></td>
          <td><?=statusBadge($m['valid_status'])?></td>
          <td><span class="badge badge-soft-info"><?=(int)$m['report_count']?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
