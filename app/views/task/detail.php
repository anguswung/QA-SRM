<div class="page-header">
  <h5><i class="fas fa-tasks"></i> <?=h($instance['title'])?></h5>
  <div>
    <a href="/task" class="btn btn-sm btn-outline-secondary"><i class="fas fa-list"></i> 返回</a>
  </div>
</div>
<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header">任务信息</div>
      <div class="card-body">
        <table class="table table-sm">
          <tr><td class="td-label">优先级</td><td><?=priorityBadge($instance['priority'] ?? 0)?></td></tr>
          <tr><td>模板</td><td><?=h($instance['template_name'])?></td></tr>
          <tr><td>状态</td><td><?=statusBadge($instance['status'])?></td></tr>
          <tr><td>发起人</td><td><?=h($instance['initiator_name'] ?? '-')?></td></tr>
          <tr><td>截止日期</td><td><?php if (!empty($instance['due_date'])): ?><span class="<?=$instance['due_date'] < date('Y-m-d') && $instance['status'] === 'active' ? 'text-danger font-weight-bold' : ''?>"><?=h($instance['due_date'])?></span><?php else: ?>-<?php endif; ?></td></tr>
          <tr><td>创建时间</td><td><?=h($instance['created_at'])?></td></tr>
          <tr><td>完成时间</td><td><?=h($instance['completed_at'] ?? '-')?></td></tr>
        </table>
      </div>
    </div>
    <!-- Attachments -->
    <div class="card mt-3">
      <div class="card-header">附件</div>
      <div class="card-body">
        <?php if (!empty($attachments)): ?>
        <div class="list-group list-group-flush">
          <?php foreach ($attachments as $att): ?>
          <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
            <div>
              <i class="fas fa-paperclip text-muted mr-1"></i>
              <a href="<?=h(fileUrl($att['file_path']))?>" target="_blank"><?=h($att['file_name'])?></a>
              <br><small class="text-muted">上传: <?=h($att['uploader_name'] ?? '-')?> / <?=h($att['created_at'])?></small>
            </div>
            <?php if (isAdmin() || $instance['initiator_id'] === userId()): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')"><?=csrf_field()?>
              <input type="hidden" name="action" value="delete_attachment">
              <input type="hidden" name="id" value="<?=$att['id']?>">
              <input type="hidden" name="instance_id" value="<?=$instance['id']?>">
              <button class="btn btn-sm btn-outline-danger">&times;</button>
            </form>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted mb-0">暂无附件</p>
        <?php endif; ?>
        <?php if ($instance['status'] === 'active'): ?>
        <hr>
        <form method="post" enctype="multipart/form-data"><?=csrf_field()?>
          <input type="hidden" name="action" value="upload_attachment">
          <input type="hidden" name="instance_id" value="<?=$instance['id']?>">
          <div class="input-group input-group-sm">
            <div class="custom-file">
              <input type="file" name="attachment" class="custom-file-input" id="attFile" onchange="this.form.submit()">
              <label class="custom-file-label" for="attFile">上传附件</label>
            </div>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <div class="card-header">审批流程</div>
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
            $isPending = $isCurrent && !$isProcessed;
            $isEnd = $n['node_type'] === 'end' || $n['node_type'] === 'completed';
          ?>
          <div class="flow-step d-flex <?=$isCurrent ? 'flow-current' : ''?>">
            <div class="flow-dot mr-3 text-center">
              <?php if ($isEnd && $instance['status'] === 'completed'): ?>
              <i class="fas fa-check-circle text-success"></i>
              <?php elseif ($isProcessed && $processedNodes[$n['id']]['status'] === 'processed'): ?>
              <i class="fas fa-check-circle text-success"></i>
              <?php elseif ($isCurrent && $instance['status'] === 'active'): ?>
              <i class="fas fa-dot-circle text-primary"></i>
              <?php elseif ($isCurrent && $instance['status'] === 'rejected'): ?>
              <i class="fas fa-times-circle text-danger"></i>
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
              <small class="text-primary font-weight-bold">当前节点</small>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <!-- Flow history -->
    <div class="card mt-3">
      <div class="card-header">流转记录</div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover">
          <thead><tr><th>节点</th><th>处理人</th><th>状态</th><th>处理时间</th><th>意见</th></tr></thead>
          <tbody>
            <?php foreach ($tasks as $t): ?>
            <tr>
              <td><?=h($t['node_name'])?></td>
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
    <!-- Supplier submit form -->
    <?php if (isSupplier() && $instance['status'] === 'active'): ?>
    <div class="card mt-3">
      <div class="card-header"><i class="fas fa-check-circle"></i> 提交任务</div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data"><?=csrf_field()?>
          <input type="hidden" name="action" value="submit">
          <input type="hidden" name="instance_id" value="<?=$instance['id']?>">
          <div class="form-group"><label>处理意见</label>
            <textarea name="comment" class="form-control" rows="3" placeholder="请说明完成情况..."></textarea>
          </div>
          <div class="form-group"><label>上传附件（可选）</label>
            <div class="custom-file">
              <input type="file" name="attachment" class="custom-file-input" id="submitAttach">
              <label class="custom-file-label" for="submitAttach">选择文件...</label>
            </div>
          </div>
          <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane"></i> 提交</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>