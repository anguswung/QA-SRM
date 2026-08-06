<div class="page-header">
  <h4><i class="fas fa-tasks"></i> 任务中心</h4>
  <?php if (canManage('task.create')): ?>
  <div>
    <a href="/task?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 下发任务</a>
  </div>
  <?php endif; ?>
</div>
<?php if (isSupplier()): ?>
<div class="row mb-3">
  <div class="col">
    <div class="card stat-card py-2 px-2 text-center">
      <div class="stat-value h5 mb-0 text-warning"><?=count($pendingTasks)?></div>
      <div class="stat-label text-muted fs-11">待处理</div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card py-2 px-2 text-center">
      <div class="stat-value h5 mb-0 text-success"><?=count($instances)?></div>
      <div class="stat-label text-muted fs-11">所有任务</div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-inbox"></i> 待处理任务</div>
  <div class="card-body p-0">
    <table class="table table-hover">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:6%">优先</th><th>任务标题</th><th style="width:12%">模板</th><th style="width:14%">当前节点</th><th style="width:12%">截止日期</th><th style="width:14%">收到时间</th><th style="width:8%">操作</th></tr></thead>
      <tbody>
<?php $__rnum = 0; ?>
        <?php foreach ($pendingTasks as $t): ?>
        <tr class="<?=$t['priority'] >= 1 ? 'table-warning' : ''?>">
          <td class="col-id"><?=h($t['id'] ?? '')?></td>
          <td><?=priorityBadge($t['priority'] ?? 0)?></td>
          <td class="text-start"><a href="/task?action=detail&id=<?=$t['instance_id']?>"><?=h($t['title'])?></a></td>
          <td><?=h($t['template_name'])?></td>
          <td><span class="badge badge-soft-info"><?=h($t['node_name'])?></span></td>
          <td><small><?=h($t['due_date'] ?? '-')?></small></td>
          <td><small><?=h($t['created_at'])?></small></td>
          <td>
            <a href="/task?action=detail&id=<?=$t['instance_id']?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> 处理</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pendingTasks)): ?>
        <tr><td class="col-id"><?=h($t['id'] ?? '')?></td><td colspan="7" class="text-muted py-4">暂无待处理任务</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="search-bar mb-3">
  <form method="get" class="d-flex flex-wrap align-items-center gap-2">
    <input type="text" name="search" class="form-control form-control-sm w-auto ms-2" placeholder="搜索任务标题" value="<?=h($_GET['search'] ?? '')?>">
    <select name="priority" class="form-control form-control-sm w-auto ms-2">
      <option value="">全部优先级</option>
      <option value="1" <?=($_GET['priority'] ?? 0) == 1 ? 'selected' : ''?>>紧急</option>
      <option value="2" <?=($_GET['priority'] ?? 0) == 2 ? 'selected' : ''?>>高优先</option>
      <option value="0" <?=isset($_GET['priority']) && $_GET['priority'] === '0' ? 'selected' : ''?>>普通</option>
    </select>
    <button type="submit" class="btn-icon btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
  </form>
</div>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?=($tab ?? '') === '' ? 'active' : ''?>" href="/task">全部</a></li>
  <li class="nav-item"><a class="nav-link <?=($tab ?? '') === 'active' ? 'active' : ''?>" href="/task?tab=active">进行中</a></li>
  <li class="nav-item"><a class="nav-link <?=($tab ?? '') === 'completed' ? 'active' : ''?>" href="/task?tab=completed">已完成</a></li>
</ul>
<?php endif; ?>
<div class="card">
  <div class="card-header"><i class="fas fa-list"></i> 任务列表</div>
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th width="50">优先</th><th class="text-start">标题</th><th width="90">模板</th><?php if (isAdmin()): ?><th width="100">供应商</th><?php endif; ?><th width="60">状态</th><th width="100">截止日期</th><th width="140">创建时间</th><th width="60">操作</th></tr></thead>
      <tbody>
<?php $__rnum = 0; ?>
        <?php foreach ($instances as $i): ?>
        <tr class="<?=$i['priority'] >= 1 ? 'table-warning' : ''?>">
          <td class="col-id"><?=h($i['id'] ?? '')?></td><td><?=priorityBadge($i['priority'] ?? 0)?></td>
          <td class="text-start"><a href="/task?action=detail&id=<?=$i['id']?>"><?=h($i['title'])?></a></td>
          <td><?=h($i['template_name'])?></td>
          <?php if (isAdmin()): ?>
          <td><?=h($i['initiator_name'] ?? '-')?></td>
          <?php endif; ?>
          <td><?=statusBadge($i['status'])?></td>
          <td><small><?php if (!empty($i['due_date'])): ?><span class="<?=$i['due_date'] < date('Y-m-d') && $i['status'] === 'active' ? 'text-danger' : ''?>"><?=h($i['due_date'])?></span><?php else: ?>-<?php endif; ?></small></td>
          <td><small><?=h($i['created_at'])?></small></td>
          <td><a href="/task?action=detail&id=<?=$i['id']?>" class="btn-icon btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>