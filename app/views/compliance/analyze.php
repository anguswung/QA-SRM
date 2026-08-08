<div class="page-header">
  <h4><i class="fas fa-flask"></i> 合规评估</h4>
  <div>
    <a href="/compliance" class="btn btn-success">分析看板</a>
  </div>
</div>
<div class="row">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">选择物料</div>
      <div class="card-body">
        <form method="get" class="mb-3">
          <div class="mb-3">
            <select name="material_id" class="form-control" onchange="this.form.submit()">
              <option value="">选择物料</option>
              <?php foreach ($materials as $m): ?>
              <option value="<?=$m['id']?>" <?=$materialId == $m['id'] ? 'selected' : ''?>><?=h($m['material_no'])?> - <?=h($m['material_name'])?> [<?=h($m['env_status'])?>]</option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
        <?php if ($materialId): ?>
        <form method="post"><?=csrf_field()?>
          <input type="hidden" name="material_id" value="<?=$materialId?>">
          <button type="submit" class="btn btn-primary d-block w-100"><i class="fas fa-play"></i> 执行分析</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">分析结果</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th>法规</th><th>分析结果</th><th>人工设定</th><th>备注</th><th>分析时间</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($results as $r): ?>
            <tr>
              <td class="col-id"><?=h($r['id'] ?? '')?></td><td><small><?=h($r['regulation_no'])?></small><br><?=h($r['regulation_name'])?></td>
              <td><?=statusBadge($r['analyze_result'] == 1 ? 'compliant' : ($r['analyze_result'] == 2 ? 'non_compliant' : 'pending'))?></td>
              <td><?=$r['is_manual'] ? '<span class="status-tag warning">人工</span>' : '-'?></td>
              <td><small><?=h($r['remark'] ?? '-')?></small></td>
              <td><small><?=h($r['analyzed_at'])?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($results)): ?>
            <tr><td colspan="5" class="text-muted py-4">
              <?php if ($materialId && $justAnalyzed): ?>
              <i class="fas fa-check-circle text-success ms-1"></i> 分析完成，该物料不含管制物质
              <?php elseif ($materialId): ?>
              已选择物料，请点击「执行分析」              <?php else: ?>
              请选择物料并执行分析              <?php endif; ?>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
