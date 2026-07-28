<div class="page-header">
  <h5><i class="fas fa-tags"></i> 物质用途</h5>
  <div>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#useModal"><i class="fas fa-plus"></i> 新增用途</button>
    <a href="/substance" class="btn btn-sm btn-outline-secondary">物质库</a>
    <a href="/substance/groups" class="btn btn-sm btn-outline-secondary">分组管理</a>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover">
      <thead><tr><th>名称</th><th>描述</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($uses as $u): ?>
        <tr>
          <td><?=h($u['name'])?></td>
          <td><?=h($u['description'] ?? '-')?></td>
          <td>
            <form method="post" onsubmit="return confirm('确定删除？')" class="d-inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?=$u['id']?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="useModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">新增用途</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label>用途名称</label><input type="text" name="name" class="form-control" required></div>
        <div class="form-group"><label>描述</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">创建</button>
        <button class="btn btn-secondary" data-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
