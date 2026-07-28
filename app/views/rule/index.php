<div class="page-header">
  <h5><i class="fas fa-gavel"></i> 合规规则管理</h5>
  <div>
    <a href="/rule?action=check" class="btn btn-sm btn-outline-info"><i class="fas fa-search"></i> 合规检查</a>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover">
      <thead><tr><th>编码</th><th>规则名称</th><th>文档分类</th><th>类型</th><th>状态</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($rules as $r): ?>
        <tr>
          <td><?=h($r['rule_code'])?></td>
          <td><?=h($r['name'])?></td>
          <td><span class="badge badge-soft-info"><?=h($r['doc_category_code'] ?: '-')?></span></td>
          <td><?=$r['rule_type'] === 'mandatory' ? '<span class="status-tag danger">强制</span>' : '<span class="status-tag warning">提醒</span>'?></td>
          <td><?=$r['enabled'] ? '<span class="status-tag success">启用</span>' : '<span class="status-tag secondary">禁用</span>'?></td>
          <td>
            <button class="btn btn-sm btn-outline-<?=$r['enabled'] ? 'warning' : 'success'?>" onclick="toggleRule(<?=$r['id']?>)">
              <?=$r['enabled'] ? '禁用' : '启用'?>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
function toggleRule(id) {
  fetch('/rule', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=toggle&id='+id})
    .then(function(){ location.reload(); });
}
</script>
