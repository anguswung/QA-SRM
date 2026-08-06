<div class="page-header">
  <h4><i class="fas fa-tasks"></i> 流程详情 - <?=h($instance['title'])?></h4>
  <div>
    <a href="/bpm" class="btn btn-sm btn-outline-secondary"><i class="fas fa-list"></i> 返回</a>
  </div>
</div>
<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header">流程信息</div>
      <div class="card-body">
        <table class="table table-sm">
        <?php $__rnum = 0; ?>
          <tr><td class="row-num"><?=++$__rnum?></td><td class="td-label">模板</td><td><?=h($instance['template_name'])?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>状态</td><td><?=statusBadge($instance['status'])?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>发起人</td><td><?=h($instance['initiator_name'] ?? '-')?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>引用类型</td><td><?=h($instance['ref_type'] ?? '-')?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>引用ID</td><td><?=h($instance['ref_id'] ?? '-')?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>创建时间</td><td><?=h($instance['created_at'])?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>完成时间</td><td><?=h($instance['completed_at'] ?? '-')?></td></tr>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <div class="card-header">审批流程图</div>
      <div class="card-body p-3">
        <div class="flow-timeline">
          <?php
          $nodes = $db->query("SELECT bn.*, t.name AS template_name FROM bpm_node bn JOIN bpm_template t ON bn.template_id=t.id WHERE bn.template_id=? ORDER BY bn.sort_order", [$instance['template_id']]);
          $processedNodes = [];
          foreach ($tasks as $t) {
            if ($t['status'] !== 'pending') $processedNodes[$t['node_id']] = $t;
          }
          $currentNodeId = $instance['current_node_id'];
          foreach ($nodes as $ni => $n):
            $isCurrent = $n['id'] == $currentNodeId;
            $isProcessed = isset($processedNodes[$n['id']]);
            $isEnd = $n['node_type'] === 'end';
          ?>
          <div class="flow-step d-flex <?=$isCurrent ? 'flow-current' : ''?>">
            <div class="flow-dot ms-3 text-center">
              <?php if ($isEnd && $instance['status'] === 'completed'): ?>
              <i class="fas fa-check-circle text-success"></i>
              <?php elseif ($isProcessed): ?>
              <i class="fas fa-check-circle text-success"></i>
              <?php elseif ($isCurrent): ?>
              <i class="fas fa-dot-circle text-primary"></i>
              <?php else: ?>
              <i class="far fa-circle text-muted"></i>
              <?php endif; ?>
            </div>
            <div class="flow-content pb-3 flex-grow-1" style="border-left:2px solid <?=$ni < count($nodes)-1 ? '#e0e0e0' : 'transparent'?>;padding-left:12px">
              <div class="d-flex justify-content-between">
                <strong><?=h($n['node_name'])?></strong>
                <small class="text-muted"><?=h($n['node_type'])?></small>
              </div>
              <?php if ($isProcessed && isset($processedNodes[$n['id']])): $pt = $processedNodes[$n['id']]; ?>
              <small class="text-muted">
                处理人 <?=h($pt['assignee_name'] ?? 'N/A')?> |
                状态 <?=statusBadge($pt['status'])?> |
                时间: <?=h($pt['processed_at'] ?? $pt['created_at'])?>
              </small>
              <?php if ($pt['comment']): ?>
              <div class="text-muted small mt-1 p-1 bg-light rounded"><?=h($pt['comment'])?></div>
              <?php endif; ?>
              <?php elseif ($isCurrent): ?>
              <small class="text-primary fw-bold">当前节点</small>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-header">流转记录</div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover">
          <thead><tr><th style="width:60px">#</th><th>节点</th><th>处理人</th><th>状态</th><th>处理时间</th><th>意见</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($tasks as $t): ?>
            <tr>
              <td class="row-num"><?=++$__rnum?></td><td><?=h($t['node_name'])?></td>
              <td><?=h($t['assignee_name'] ?? '-')?></td>
              <td><?=statusBadge($t['status'])?></td>
              <td><small><?=h($t['processed_at'] ?? $t['created_at'])?></small></td>
              <td><small><?=h($t['comment'] ?? '-')?></small></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>