<div class="page-header">
  <h5><i class="fas fa-balance-scale"></i> 合规分析 - <?=h($item['material_name'])?></h5>
  <div>
    <a href="/homogeneous" class="btn btn-sm btn-outline-secondary"><i class="fas fa-list"></i> 返回</a>
  </div>
</div>
<div class="row">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">基本信息</div>
      <div class="card-body">
        <table class="table table-sm">
          <tr><td>名称</td><td><?=h($item['material_name'])?></td></tr>
          <tr><td>供应商</td><td><?=h($item['supplier_name'] ?: '-')?></td></tr>
          <tr><td>环保状态</td><td><?=statusBadge($reportEnvStatus)?> <small class="text-muted">(基于报告有效期)</small></td></tr>
          <tr><td>成分合规</td><td><?=statusBadge($item['env_status'])?></td></tr>
          <tr><td>成分数</td><td><?=count($composition)?></td></tr>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header">成分列表</div>
      <div class="card-body p-0">
        <table class="table table-sm">
          <thead><tr><th>CAS</th><th>英文名称</th><th>中文名称</th><th>含量%</th></tr></thead>
          <tbody>
            <?php foreach ($composition as $c): ?>
            <tr>
              <td><?=h($c['cas_no'] ?: '-')?></td>
              <td><?=h($c['substance_name'])?></td>
              <td><?=h($c['name_cn'] ?? '-')?></td>
              <td><?=h($c['percentage'])?>%</td>
            </tr>
            <?php endforeach; ?>
            <?php
              $totalPct = number_format(array_sum(array_column($composition, 'percentage')), 2);
              $isTotalValid = abs((float)$totalPct - 100) < 0.01;
            ?>
            <tr class="table-light font-weight-bold">
              <td colspan="3" class="text-right">合计</td>
              <td class="<?=$isTotalValid ? 'text-success' : 'text-danger'?>"><?=$totalPct?>%</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        合规判定
        <div class="card-actions">
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="analyze_hm">
            <input type="hidden" name="hm_id" value="<?=$item['id']?>">
            <button class="btn btn-sm btn-primary"><i class="fas fa-play"></i> 执行分析</button>
          </form>
        </div>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th style="width:60px;white-space:nowrap">序号</th><th>物质中文</th><th>物质英文</th><th>CAS</th><th>检测值(mg/kg)</th><th>法规限值</th><th>判定</th><th>时间</th></tr></thead>
          <tbody>
            <?php foreach ($results as $i => $r): ?>
            <tr class="<?=$r['result'] == 2 ? 'table-danger' : ($r['result'] == 1 ? 'table-success' : 'table-info')?>">
              <td class="text-center"><?=$i+1?></td>
              <td><?=h($r['name_cn'] ?: '-')?></td>
              <td><?=h($r['substance'])?></td>
              <td><?=h($r['cas'])?></td>
              <td class="font-weight-bold"><?=$r['detected'] ? h($r['detected']) : '-'?></td>
              <td><?=$r['limit'] ? h($r['limit']).' mg/kg' : '-'?></td>
              <td><span class="status-tag <?=$r['result'] == 1 ? 'success' : ($r['result'] == 2 ? 'danger' : 'info')?>"><?=h($r['result_text'])?></span></td>
              <td><small><?=h($r['analyzed_at'] ?? '-')?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($results)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">点击"执行分析"进行合规判定</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
