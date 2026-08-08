<div class="page-header">
  <h4><i class="fas fa-boxes"></i> 使用本均质材料【<span class="text-primary"><?=h($item['material_name'])?></span>】的物料</h4>
  <div>
    <a href="/homogeneous?action=reports&id=<?=h($item['id'])?>" class="btn btn-outline-info"><i class="fas fa-file-alt"></i> 检测报告</a>
    <a href="/homogeneous" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> 返回均质材料</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body py-2">
    <h4 class="mb-0"><i class="fas fa-flask text-primary"></i> 均质材料：<span class="fw-bold"><?=h($item['material_name'])?></span>
      <span class="badge badge-soft-success text-muted ms-1">使用物料 <?=count($materials)?> 个</span>
    </h4>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover">
      <thead>
        <tr>
          <th style="width:40px" class="col-id">ID</th>
          <th>物料编号</th>
          <th>物料名称</th>
          <th>规格</th>
          <th>供应商</th>
          <th>占比(%)</th>
          <th>主成分</th>
          <th width="90">环保状态</th>
          <th class="nowrap">更新日期</th>
          <th width="80">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php $__rnum = 0; ?>
        <?php foreach ($materials as $mm): ?>
        <tr>
          <td class="text-muted col-id"><?=h($mm['id'])?></td>
          <td><span class="fw-bold"><?=h($mm['material_no'] ?: '-')?></span></td>
          <td><?=h($mm['material_name'] ?? '-')?></td>
          <td><small><?=h($mm['spec'] ?? '-')?></small></td>
          <td><small><?=h($mm['supplier_name'] ?: '-')?></small></td>
          <td>
            <?php if (($mm['min_percentage'] ?? '') !== '' || ($mm['max_percentage'] ?? '') !== ''): ?>
            <?=h($mm['min_percentage'] ?? '')?> ~ <?=h($mm['max_percentage'] ?? '')?>
            <?php else: ?><span class="text-muted">-</span><?php endif; ?>
          </td>
          <td><?= $mm['is_main_component'] ? '<span class="badge badge-soft-primary">主成分</span>' : '<span class="text-muted">-</span>' ?></td>
          <td><?php
            if ($mm['env_status'] === 'compliant'): ?><span class="badge badge-soft-success">合规</span><?php
            elseif ($mm['env_status'] === 'non_compliant'): ?><span class="badge badge-soft-danger">不合规</span><?php
            else: ?><span class="badge badge-soft-warning">待评估</span><?php endif; ?></td>
          <td><small class="text-muted"><?=h(substr($mm['updated_at'] ?: '', 0, 10))?></small></td>
          <td>
            <div class="btn-group btn-group-sm">
              <?php if (can('material.view')): ?><a href="/material?action=detail&id=<?=$mm['id']?>" class="btn-icon btn btn-sm btn-outline-primary" title="查看物料"><i class="fas fa-eye"></i></a><?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($materials)): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">暂无物料使用该均质材料</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>