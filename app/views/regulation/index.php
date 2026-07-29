<div class="page-header">
  <h5><i class="fas fa-gavel"></i> 法规库</h5>
  <div>
    <?php if (can('regulation.create')): ?><a href="/regulation?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 新增法规</a><?php endif; ?>
  </div>
</div>
<div class="search-bar mb-3">
  <form method="get" class="form-inline">
    <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="搜索法规名称/编号" value="<?=h($_GET['search'] ?? '')?>">
    <select name="category" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部分类</option>
      <?php foreach ($cats as $c): ?>
      <option value="<?=h($c['category'])?>" <?=($_GET['category'] ?? '') === $c['category'] ? 'selected' : ''?>><?=h($c['category'])?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部状态</option>
      <option value="draft" <?=($_GET['status'] ?? '') === 'draft' ? 'selected' : ''?>>草案</option>
      <option value="published" <?=($_GET['status'] ?? '') === 'published' ? 'selected' : ''?>>已发布</option>
      <option value="superseded" <?=($_GET['status'] ?? '') === 'superseded' ? 'selected' : ''?>>已废除</option>
    </select>
    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead>
        <tr><th>编号</th><th>法规名称</th><th>分类</th><th>发布机构</th><th>生效日期</th><th>状态</th><th>重点</th><th>操作</th></tr>
      </thead>
      <tbody>
        <?php foreach ($regs as $r): ?>
        <tr>
          <td><?=h($r['regulation_no'])?></td>
          <td><a href="/regulation/<?=$r['id']?>/detail"><?=h($r['name'])?></a></td>
          <td><span class="badge badge-soft-info"><?=h($r['category'])?></span></td>
          <td><?=h($r['issuing_body'])?></td>
          <td><?=h($r['effective_date'])?></td>
          <td><?=statusBadge($r['status'])?></td>
          <td>
            <a href="javascript:;" class="toggle-focus" data-id="<?=$r['id']?>" data-focus="<?=$r['is_focus']?>">
              <i class="fas fa-star <?=$r['is_focus'] ? 'text-warning' : 'text-muted'?>"></i>
            </a>
          </td>
          <td>
            <?php if (can('regulation.edit')): ?><a href="/regulation?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a><?php endif; ?>
            <?php if ($r['status'] === 'draft'): ?>
            <form method="post" class="d-inline inline-form">
              <input type="hidden" name="action" value="publish">
              <input type="hidden" name="id" value="<?=$r['id']?>">
              <input type="hidden" name="status" value="published">
              <button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button>
            </form>
            <?php endif; ?>
            <?php if (can('regulation.delete')): ?>
            <form method="post" class="d-inline inline-form" onsubmit="return confirm('确定删除？')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?=$r['id']?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
$(document).on('click', '.toggle-focus', function(){
  var $this = $(this);
  var id = $this.data('id');
  var focus = $this.data('focus') ? 0 : 1;
  $.post('/regulation/focus', {id: id, is_focus: focus}, function(){
    $this.data('focus', focus);
    $this.find('i').toggleClass('text-warning text-muted');
  });
});
</script>
