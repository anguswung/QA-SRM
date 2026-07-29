<div class="summary-cards">
  <div class="card stat-card">
    <div class="stat-icon text-primary"><i class="fas fa-boxes"></i></div>
    <div class="stat-value"><?=h($totalMaterials ?? 0)?></div>
    <div class="stat-label">物料总数</div>
  </div>
  <div class="card stat-card">
    <div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div>
    <div class="stat-value"><?=h($compliant ?? 0)?></div>
    <div class="stat-label">合规物料</div>
  </div>
  <div class="card stat-card">
    <div class="stat-icon text-danger"><i class="fas fa-times-circle"></i></div>
    <div class="stat-value"><?=h($nonCompliant ?? 0)?></div>
    <div class="stat-label">不合规物料</div>
  </div>
  <div class="card stat-card">
    <div class="stat-icon text-warning"><i class="fas fa-clock"></i></div>
    <div class="stat-value"><?=h($pending ?? 0)?></div>
    <div class="stat-label">待评估</div>
  </div>
  <div class="card stat-card">
    <div class="stat-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
    <div class="stat-value"><?=h($overLimit ?? 0)?></div>
    <div class="stat-label">物质超标</div>
  </div>
  <div class="card stat-card">
    <div class="stat-icon text-warning"><i class="fas fa-calendar-times"></i></div>
    <div class="stat-value"><?=h($expiringSoon ?? 0)?></div>
    <div class="stat-label">即将过期</div>
  </div>
</div>

<?php if (isAdmin()): ?>
<div class="summary-cards">
  <div class="card stat-card">
    <div class="stat-icon text-info"><i class="fas fa-file-signature"></i></div>
    <div class="stat-value"><?=h($pendingReports ?? 0)?></div>
    <div class="stat-label">待审核报告</div>
  </div>
  <div class="card stat-card">
    <div class="stat-icon text-info"><i class="fas fa-clipboard-check"></i></div>
    <div class="stat-value"><?=h($pendingMaterials ?? 0)?></div>
    <div class="stat-label">待审核物料</div>
  </div>
  <div class="card stat-card">
    <div class="stat-icon text-warning"><i class="fas fa-bell"></i></div>
    <div class="stat-value"><?=h($activeWarnings ?? 0)?></div>
    <div class="stat-label">活跃预警</div>
  </div>
  <div class="card stat-card">
    <div class="stat-icon text-success"><i class="fas fa-building"></i></div>
    <div class="stat-value"><?=h($totalSuppliers ?? 0)?></div>
    <div class="stat-label">供应商</div>
  </div>
</div>
<?php endif; ?>

<?php if (isSupplier()): ?>
<div class="row mb-3">
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><i class="fas fa-tasks text-warning"></i> 待办事项</div>
      <div class="card-body py-2">
        <div class="d-flex justify-content-around text-center">
          <a href="/material" class="text-decoration-none">
            <div class="p-3">
              <div class="h3 mb-1 font-weight-bold <?=$todoPendingSubmit > 0 ? 'text-danger' : 'text-muted'?>"><?=(int)($todoPendingSubmit ?? 0)?></div>
              <div class="small text-muted">待提交审核</div>
            </div>
          </a>
          <div class="border-right"></div>
          <a href="/material" class="text-decoration-none">
            <div class="p-3">
              <div class="h3 mb-1 font-weight-bold <?=$todoExpiringReports > 0 ? 'text-warning' : 'text-muted'?>"><?=(int)($todoExpiringReports ?? 0)?></div>
              <div class="small text-muted">报告即将过期</div>
            </div>
          </a>
          <div class="border-right"></div>
          <a href="/notice" class="text-decoration-none">
            <div class="p-3">
              <div class="h3 mb-1 font-weight-bold <?=$todoUnreadNotices > 0 ? 'text-primary' : 'text-muted'?>"><?=(int)($todoUnreadNotices ?? 0)?></div>
              <div class="small text-muted">未读通知</div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><i class="fas fa-bullhorn text-info"></i> 最新通知</div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php if (!empty($recentNotices)): $snum = 0; foreach ($recentNotices as $n): if (++$snum > 5) break; ?>
          <li class="list-group-item py-2 px-3">
            <span class="text-muted mr-2"><?=$snum?>.</span>
            <a href="javascript:;" onclick='showDashboardNotice(<?=htmlspecialchars(json_encode($n['title']), ENT_QUOTES)?>,<?=htmlspecialchars(json_encode($n['content']), ENT_QUOTES)?>,"<?=h($n['publisher_name'])?>","<?=h($n['publish_time'] ?: $n['created_at'])?>")'><?=h($n['title'])?></a>
            <small class="text-muted float-right"><?=h(substr($n['publish_time'] ?: $n['created_at'], 0, 10))?></small>
          </li>
          <?php endforeach; else: ?>
          <li class="list-group-item py-3 text-center text-muted">暂无通知</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-chart-pie"></i> 状态分布</div>
      <div class="card-body">
        <canvas id="statusChart" height="220"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-chart-line"></i> 近6个月趋势</div>
      <div class="card-body">
        <canvas id="trendChart" height="220"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="fas fa-exclamation-triangle text-danger"></i> 最近预警</div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>物料</th>
          <th>超标物质</th>
          <th>含量</th>
          <th>限值</th>
          <th>报告编号</th>
          <th>日期</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($warnings)): $idx = 0; foreach ($warnings as $w): if (++$idx > 10) break; ?>
        <tr>
          <td><?=h($w['material_name'] ?? '')?></td>
          <td><?=h($w['substance_name'] ?? '')?></td>
          <td class="text-danger font-weight-bold"><?=h($w['content_value'] ?? '')?></td>
          <td><?=h($w['limit_value'] ?? '')?></td>
          <td><?=h($w['report_no'] ?? '')?></td>
          <td><?=h($w['test_date'] ?? $w['created_at'] ?? '')?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" class="text-center text-muted py-4">暂无预警数据</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="dashboardNoticeModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="dashboardNoticeTitle"></h4>
        <button class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="text-right text-muted mb-3 pb-2 border-bottom" style="font-size:13px">
          <span id="dashboardNoticePubInfo"></span>
        </div>
        <div id="dashboardNoticeContent" class="ql-editor" style="padding:0"></div>
      </div>
    </div>
  </div>
</div>

<script>
function showDashboardNotice(title, content, publisher, pubTime) {
  $('#dashboardNoticeTitle').text(title);
  $('#dashboardNoticePubInfo').text('发布人: ' + (publisher || '-') + ' | 时间: ' + (pubTime || '-'));
  $('#dashboardNoticeContent').html(content);
  $('#dashboardNoticeModal').modal('show');
}
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: ['合规', '不合规', '待评估'],
    datasets: [{
      data: [<?=h($compliant ?? 0)?>, <?=h($nonCompliant ?? 0)?>, <?=h($pending ?? 0)?>],
      backgroundColor: ['#22c55e', '#ef4444', '#f59e0b']
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } }
  }
});
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: [<?php foreach ($months ?? [] as $m): ?>'<?=h($m)?>',<?php endforeach; ?>],
    datasets: [
      { label: '检测总数', data: [<?php foreach ($totals ?? [] as $v): ?><?=h($v)?>,<?php endforeach; ?>], borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.1)', fill: true, tension: .3 },
      { label: '超标数', data: [<?php foreach ($violations ?? [] as $v): ?><?=h($v)?>,<?php endforeach; ?>], borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.1)', fill: true, tension: .3 }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>
