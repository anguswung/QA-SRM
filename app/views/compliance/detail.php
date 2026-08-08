<div class="page-header">
  <h4><i class="fas fa-search"></i> 合规详情 - <?=h($material['material_name'])?></h4>
  <div>
    <a href="/compliance/analyze?material_id=<?=$material['id']?>" class="btn btn-success"><i class="fas fa-flask"></i> 分析</a>
    <a href="/compliance" class="btn btn-success">返回</a>
  </div>
</div>
<div class="row">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">物料信息</div>
      <div class="card-body">
        <table class="table table-sm">
        <?php $__rnum = 0; ?>
          <tr><td class="row-num"><?=++$__rnum?></td><td>编号</td><td><?=h($material['material_no'])?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>名称</td><td><?=h($material['material_name'])?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>供应商</td><td><?=h($material['supplier_name'] ?? '-')?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>环保状态</td><td><?=statusBadge($material['env_status'])?></td></tr>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">检测数据及合规判定</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th>物质</th><th>CAS</th><th>检出值</th><th>限值</th><th>结果</th><th>法规</th><th>分析时间</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($records as $e): ?>
            <tr>
              <td class="col-id"><?=h($e['id'] ?? '')?></td><td><?=h($e['substance_name'])?></td>
              <td><?=h($e['cas_no'])?></td>
              <td class="fw-bold"><?=h($e['detected_value'])?></td>
              <td><?=h($e['limit_value'] ?? '-')?></td>
              <td><?php $__rb = (int)$e['analyze_result']; echo $__rb == 2 ? '<span class="status-tag danger">违规</span>' : ($__rb == 1 ? '<span class="status-tag success">合规</span>' : '<span class="status-tag default">未知</span>'); ?></td>
              <td><small><?=h($e['regulation_name'])?></small></td>
              <td><small><?=h($e['analyzed_at'])?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($records)): ?>
            <tr><td colspan="8" class="text-muted py-3">暂无分析记录，点击右上角「分析」生成</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
