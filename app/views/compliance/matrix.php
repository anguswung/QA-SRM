<div class="page-header">
  <h5><i class="fas fa-th"></i> 合规矩阵</h5>
  <div>
    <a href="/compliance" class="btn btn-sm btn-outline-secondary">分析看板</a>
    <a href="/compliance/analyze" class="btn btn-sm btn-outline-primary">合规评估</a>
  </div>
</div>
<div class="card"><div class="card-body p-0" style="overflow-x:auto">
  <table class="table table-sm table-bordered">
    <thead>
      <tr>
        <th style="min-width:150px">物料</th>
        <th>状态</th>
        <?php foreach ($regulations as $r): ?>
        <th title="<?=h($r['name'])?>"><?=h($r['regulation_no'])?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($matrix as $row): $m = $row['material']; ?>
      <tr>
        <td><small><?=h($m['material_no'])?></small><br><?=h($m['material_name'])?></td>
        <td><?=statusBadge($m['env_status'])?></td>
        <?php foreach ($regulations as $r): $rec = $row['results'][$r['id']] ?? null; ?>
        <td class="text-center">
          <?php if ($rec): ?>
            <?php if ($rec['is_manual']): ?>
              <span class="status-tag warning" title="人工设定"><?=$rec['manual_result'] == '9' ? '合规' : '违规'?></span>
            <?php else: ?>
              <?=$rec['analyze_result'] == 1 ? '<span class="status-tag success">✓</span>' : ($rec['analyze_result'] == 2 ? '<span class="status-tag danger">✓</span>' : '<span class="status-tag secondary">?</span>')?>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
