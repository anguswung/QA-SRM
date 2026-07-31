<div class="page-header">
  <h5><i class="fas fa-clipboard-list"></i> 操作日志</h5>
  <div>
    <form method="post" action="/system/logs" id="quickCleanForm" class="d-inline-flex align-items-center">
      <input type="hidden" name="action" value="clean_logs">
      <select name="keep_days" class="form-control form-control-sm mr-2" style="width:auto" onchange="document.getElementById('quickKeepDays').value=this.value">
        <option value="30">30 天前</option>
        <option value="60">60 天前</option>
        <option value="90">90 天前</option>
        <option value="180">180 天前</option>
        <option value="365">365 天前</option>
      </select>
      <input type="hidden" name="quick_keep_days" id="quickKeepDays" value="30">
      <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('确定清理30天前的日志？此操作不可恢复。')"><i class="fas fa-trash-alt mr-1"></i> 清理日志</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable" data-options='{"order":[[0,"desc"]]}'>
      <thead><tr><th style="width:160px">时间</th><th style="width:240px">操作人</th><th style="width:80px">操作</th><th style="width:100px">模块</th><th style="width:80px">目标类型</th><th style="width:70px">目标ID</th><th>详情</th><th style="width:140px">IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td><small><?=h($l['created_at'])?></small></td>
          <td><?=h($l['realname'] ?? $l['user_id'])?></td>
          <td><span class="badge badge-soft-info"><?=h($l['action'])?></span></td>
          <td><?=h($l['module'])?></td>
          <td><?=h($l['target_type'] ?? '-')?></td>
          <td><?=h($l['target_id'] ?? '-')?></td>
          <td><small><?=h($l['detail'])?></small></td>
          <td><small><?=h($l['ip_address'])?></small></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="d-flex justify-content-center">
  <nav>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?=$i == $page ? 'active' : ''?>"><a class="page-link" href="/system/logs?page=<?=$i?>"><?=$i?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>
<script>
document.querySelector('[name="keep_days"]').addEventListener('change', function() {
    var days = this.value;
    document.getElementById('quickKeepDays').value = days;
    document.querySelector('#quickCleanForm button[type="submit"]').setAttribute('onclick', "return confirm('确定清理" + days + "天前的日志？此操作不可恢复。')");
});
</script>
