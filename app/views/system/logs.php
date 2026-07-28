<div class="page-header">
  <h5><i class="fas fa-clipboard-list"></i> 操作日志</h5>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable" data-options='{"order":[[0,"desc"]]}'>
      <thead><tr><th>时间</th><th>操作人</th><th>操作</th><th>模块</th><th>目标类型</th><th>目标ID</th><th>详情</th><th>IP</th></tr></thead>
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
