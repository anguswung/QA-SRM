<div class="page-header">
  <h4><i class="fas fa-balance-scale"></i> 合规分析看板</h4>
  <div>
    <a href="/compliance/analyze" class="btn btn-sm btn-primary"><i class="fas fa-flask"></i> 合规评估</a>
    <a href="/compliance/matrix" class="btn btn-sm btn-outline-info"><i class="fas fa-th"></i> 合规矩阵</a>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-chart-pie"></i> 物料环保状态分布</div>
      <div class="card-body">
        <canvas id="envChart" height="250"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-trophy"></i> 超标物质 Top 10</div>
      <div class="card-body p-0">
        <table class="table table-sm">
          <thead><tr><th style="width:60px">#</th><th>物质</th><th style="width:20%">超标次数</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($topViolations as $v): ?>
            <tr>
              <td class="row-num"><?=++$__rnum?></td><td><span class="badge badge-soft-danger"><?=h($v['substance_name'])?></span></td>
              <td><span class="badge badge-soft-danger"><?=h($v['violation_count'])?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($topViolations)): ?>
            <tr><td colspan="2" class="text-muted py-3">暂无超标数据</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="card mt-3">
  <div class="card-header"><i class="fas fa-building"></i> 供应商合规率排行</div>
  <div class="card-body p-0">
    <table class="table table-hover">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:10%">编码</th><th class="text-start">名称</th><th style="width:8%">评级</th><th style="width:9%">物料数</th><th style="width:9%">合规数</th><th style="width:10%">不合规数</th></tr></thead>
      <tbody>
<?php $__rnum = 0; ?>
        <?php foreach ($supplierCompliance as $s): ?>
        <tr>
          <td class="col-id"><?=h($s['id'] ?? '')?></td><td><?=h($s['code'])?></td>
          <td class="text-start"><?=h($s['name'])?></td>
          <td><?=statusBadge($s['level'] . '_')?></td>
          <td><?=(int)$s['material_count']?></td>
          <td><span class="text-success"><?=(int)$s['compliant_count']?></span></td>
          <td><span class="text-danger"><?=(int)$s['non_compliant_count']?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
new Chart(document.getElementById('envChart'), {
  type: 'bar',
  data: {
    labels: [<?php foreach ($envStats as $s): ?>'<?=h($s['env_status'])?>',<?php endforeach; ?>],
    datasets: [{
      label: '数量',
      data: [<?php foreach ($envStats as $s): ?><?=(int)$s['cnt']?>,<?php endforeach; ?>],
      backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#94a3b8']
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>
