<div class="page-header">
  <h4><i class="fas fa-user-friends"></i> 流程代理</h4>
  <div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agentModal"><i class="fas fa-plus"></i> 新增代理</button>
    <a href="/bpm" class="btn btn-success">任务中心</a>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th>委托人</th><th>代理人</th><th>适用模板</th><th>有效期</th><th>状态</th><th>备注</th><th>操作</th></tr></thead>
      <tbody>
<?php $__rnum = 0; ?>
        <?php foreach ($agents as $a): ?>
        <tr>
          <td class="col-id"><?=h($a['id'] ?? '')?></td><td><?=h($a['principal_name'])?></td>
          <td><?=h($a['agent_name'])?></td>
          <td><small><?=h($a['template_ids'])?></small></td>
          <td><small><?=h($a['start_date'])?> ~ <?=h($a['end_date'])?></small></td>
          <td><?=statusBadge($a['status'])?></td>
          <td><small><?=h($a['remark'])?></small></td>
          <td>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')"><?=csrf_field()?>
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$a['id']?>">
              <button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="agentModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">新增代理</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="mb-3"><label>委托人</label>
          <select name="principal_id" class="form-control" required>
            <?php foreach ($users as $u): ?>
            <option value="<?=$u['id']?>"><?=h($u['realname'] ?: $u['username'])?> (<?=h($u['role'])?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label>代理人</label>
          <select name="agent_id" class="form-control" required>
            <?php foreach ($users as $u): ?>
            <option value="<?=$u['id']?>"><?=h($u['realname'] ?: $u['username'])?> (<?=h($u['role'])?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label>适用模板ID (*表示全部)</label><input type="text" name="template_ids" class="form-control" value="*"></div>
        <div class="row">
          <div class="col-6"><div class="mb-3"><label>开始日期</label><input type="date" name="start_date" class="form-control"></div></div>
          <div class="col-6"><div class="mb-3"><label>结束日期</label><input type="date" name="end_date" class="form-control"></div></div>
        </div>
        <div class="mb-3"><label>备注</label><textarea name="remark" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">创建</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
