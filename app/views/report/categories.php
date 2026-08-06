<div class="page-header">
  <h4><i class="fas fa-folder-tree"></i> 文档分类管理</h4>
  <div>
    <?php if ($canManage): ?><a href="/report/categories?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 新增分类</a><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th style="width:45px">编码</th><th class="text-start">名称</th><th>有效期/天</th><th>强制</th><th>排序</th><?php if ($canManage): ?><th>操作</th><?php endif; ?></tr></thead>
      <tbody>
<?php $__rnum = 0; ?>
        <?php foreach ($cats as $c): ?>
        <tr>
          <td><?=h($c['code'])?></td>
          <td><?=h($c['name'])?></td>
          <td><?=h($c['validity_days'])?></td>
          <td><?=$c['is_required'] ? '<span class="status-tag danger">强制</span>' : '<span class="status-tag secondary">可选</span>'?></td>
          <td><?=h($c['sort_order'])?></td>
          <?php if ($canManage): ?>
          <td>
            <a href="/report/categories?action=edit&id=<?=$c['id']?>" class="btn-icon btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')"><?=csrf_field()?>
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$c['id']?>">
              <button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
