<div class="page-header">
  <h5><i class="fas fa-folder-tree"></i> 文档分类管理</h5>
  <div>
    <?php if (can('document.view')): ?><a href="/document/categories?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 新增分类</a><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th>编码</th><th>名称</th><th>有效期/天</th><th>强制</th><th>排序</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($cats as $c): ?>
        <tr>
          <td><?=h($c['code'])?></td>
          <td><?=h($c['name'])?></td>
          <td><?=h($c['validity_days'])?></td>
          <td><?=$c['is_required'] ? '<span class="status-tag danger">强制</span>' : '<span class="status-tag secondary">可选</span>'?></td>
          <td><?=h($c['sort_order'])?></td>
          <td>
            <a href="/document/categories?action=edit&id=<?=$c['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$c['id']?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
