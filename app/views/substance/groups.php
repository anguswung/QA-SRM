<div class="page-header">
  <h4><i class="fas fa-sitemap"></i> 物质分组</h4>
  <div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#groupModal"><i class="fas fa-plus"></i> 新增分组</button>
    <a href="/substance" class="btn btn-sm btn-outline-secondary">物质库</a>
    <a href="/substance/uses" class="btn btn-sm btn-outline-secondary">用途管理</a>
  </div>
</div>
<div class="card">
  <div class="card-header">分组列表</div>
  <div class="card-body p-0">
    <table class="table table-hover">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th>层级</th><th class="text-start">名称</th><th>父级</th><th>操作</th></tr></thead>
      <tbody>
<?php $__rnum = 0; ?>
        <?php foreach ($groups as $g): ?>
        <tr>
          <td class="col-id"><?=h($g['id'] ?? '')?></td><td><span class="text-muted"><?=str_repeat('&nbsp;&nbsp;', $g['depth'])?> <?=$g['depth'] > 0 ? '└' : ''?></span></td>
          <td><?=h($g['name'])?></td>
          <td><?=$g['parent_id'] ? '...' : '-'?></td>
          <td>
            <form method="post" onsubmit="return confirm('删除分组将同时删除子分组，确定？')" class="d-inline"><?=csrf_field()?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?=$g['id']?>">
              <button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="groupModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">新增分组</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="mb-3"><label>名称</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-3"><label>父级分组</label>
          <select name="parent_id" class="form-control">
            <option value="">顶级分组</option>
            <?php foreach ($allGroups as $g): ?>
            <option value="<?=$g['id']?>"><?=h($g['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label>排序</label><input type="number" name="sort_order" class="form-control" value="0"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">创建</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
