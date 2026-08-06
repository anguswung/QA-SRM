<div class="page-header">
  <h4><i class="fas fa-search"></i> 合规详情 - <?=h($material['material_name'])?></h4>
  <div>
    <a href="/compliance/analyze?material_id=<?=$material['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-flask"></i> 分析</a>
    <a href="/compliance" class="btn btn-sm btn-outline-secondary">返回</a>
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
          <thead><tr><th style="width:40px" class="col-id">ID</th><th>物质</th><th>CAS</th><th>含量</th><th>限值</th><th>超标</th><th>报告</th><th>检测日期</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($elements as $e): ?>
            <tr class="<?=$e['is_over_limit'] ? 'table-danger' : ''?>">
              <td class="col-id"><?=h($e['id'] ?? '')?></td><td><?=h($e['substance_name'])?></td>
              <td><?=h($e['cas_no'])?></td>
              <td class="fw-bold"><?=h($e['content_value'])?> <?=h($e['unit'])?></td>
              <td><?=h($e['limit_value'] ?? '-')?> <?=h($e['limit_unit'] ?? '')?></td>
              <td><?=$e['is_over_limit'] ? '<span class="status-tag danger">超标</span>' : '<span class="status-tag success">正常</span>'?></td>
              <td><small><?=h($e['report_no'])?></small></td>
              <td><small><?=h($e['test_date'])?></small></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
