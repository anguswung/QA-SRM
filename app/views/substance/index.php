<div class="page-header">
  <h4><i class="fas fa-flask"></i> 物质库</h4>
  <div>
    <?php if (can('substance.create')): ?><a href="/substance?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> 新增物质</a><?php endif; ?>
  </div>
</div>
<div class="search-bar mb-3">
  <form method="get" class="d-flex flex-wrap align-items-center gap-2">
    <input type="text" name="search" class="form-control form-control-sm w-auto ms-2" placeholder="搜索名称/CAS" value="<?=h($_GET['search'] ?? '')?>">
    <select name="controlled" class="form-control form-control-sm w-auto ms-2" onchange="this.form.submit()">
      <option value="">全部状态</option>
      <option value="1" <?=($_GET['controlled'] ?? '') === '1' ? 'selected' : ''?>>受控</option>
      <option value="0" <?=($_GET['controlled'] ?? '') === '0' ? 'selected' : ''?>>非受控</option>
    </select>
    <button type="submit" class="btn-icon btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/substance?action=data">
<thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:10%">CAS号</th><th class="text-start">中文名称</th><th style="width:14%">英文名称</th><th style="width:10%">分子式</th><th style="width:7%">受控</th><th style="width:13%">操作</th></tr></thead>
      <tbody>
        <tr><td colspan="7" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
