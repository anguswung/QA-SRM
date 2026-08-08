<div class="page-header">
  <h4><i class="fas fa-gavel"></i> 法规库</h4>
  <div>
    <?php if (can('regulation.create')): ?><a href="/regulation?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> 新增法规</a><?php endif; ?>
  </div>
</div>
<div class="search-bar mb-3">
  <form method="get" class="d-flex flex-wrap align-items-center gap-2">
    <input type="text" name="search" class="form-control form-control-sm w-auto ms-2" placeholder="搜索法规名称/编号" value="<?=h($_GET['search'] ?? '')?>">
    <select name="category" class="form-control form-control-sm w-auto ms-2" onchange="this.form.submit()">
      <option value="">全部分类</option>
      <?php foreach ($cats as $c): ?>
      <option value="<?=h($c['category'])?>" <?=($_GET['category'] ?? '') === $c['category'] ? 'selected' : ''?>><?=h($c['category'])?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-control form-control-sm w-auto ms-2" onchange="this.form.submit()">
      <option value="">全部状态</option>
      <option value="draft" <?=($_GET['status'] ?? '') === 'draft' ? 'selected' : ''?>>草案</option>
      <option value="published" <?=($_GET['status'] ?? '') === 'published' ? 'selected' : ''?>>已发布</option>
      <option value="superseded" <?=($_GET['status'] ?? '') === 'superseded' ? 'selected' : ''?>>已废除</option>
    </select>
    <button type="submit" class="btn-icon btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/regulation?action=data">
      <thead>
        <tr><th style="width:40px" class="col-id">ID</th><th style="width:10%">编号</th><th class="text-start">法规名称</th><th style="width:11%">分类</th><th style="width:14%">发布机构</th><th style="width:10%">生效日期</th><th style="width:7%">状态</th><th style="width:6%">重点</th><th style="width:13%">操作</th></tr>
      </thead>
      <tbody>
        <tr><td colspan="9" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<script>
$(document).on('click', '.toggle-focus', function(){
  var $this = $(this);
  var id = $this.data('id');
  var focus = $this.data('focus') ? 0 : 1;
  $.post('/regulation/focus', {id: id, is_focus: focus, _csrf_token: getCsrfToken()}, function(){
    $this.data('focus', focus);
    $this.find('i').toggleClass('text-warning text-muted');
  });
});
</script>
