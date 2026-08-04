<div class="page-header">
  <h5><i class="fas fa-tasks"></i> 流程中心</h5>
  <div>
    <a href="/bpm/config" class="btn btn-sm btn-outline-primary">流程配置</a>
    <a href="/bpm/agent" class="btn btn-sm btn-outline-info">流程代理</a>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-inbox"></i> 我的待办</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th>流程</th><th>节点</th><th>类型</th><th>创建时间</th><th>操作</th></tr></thead>
          <tbody>
            <?php foreach ($tasks as $t): ?>
            <tr>
              <td><?=h($t['title'])?></td>
              <td><span class="badge badge-soft-info"><?=h($t['node_name'])?></span></td>
              <td><?=h($t['ref_type'])?></td>
              <td><small><?=h($t['created_at'])?></small></td>
              <td>
                <?php if (can('task.view')): ?>
                <form method="post" class="d-inline"><?=csrf_field()?>
                  <input type="hidden" name="action" value="advance">
                  <input type="hidden" name="instance_id" value="<?=$t['instance_id']?>">
                  <button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button>
                </form>
                <form method="post" class="d-inline"><?=csrf_field()?>
                  <input type="hidden" name="action" value="reject">
                  <input type="hidden" name="instance_id" value="<?=$t['instance_id']?>">
                  <input type="hidden" name="comment" value="驳回">
                  <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-history"></i> 最近流程</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th>标题</th><th>模板</th><th>状态</th><th>发起时间</th></tr></thead>
          <tbody>
            <?php foreach ($instances as $i): ?>
            <tr>
              <td><a href="/bpm/<?=$i['id']?>/detail"><?=h($i['title'])?></a></td>
              <td><small><?=h($i['template_name'])?></small></td>
              <td><?=statusBadge($i['status'])?></td>
              <td><small><?=h($i['created_at'])?></small></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
