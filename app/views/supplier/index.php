<div class="page-header">
  <h4><i class="fas fa-truck"></i> 供应商列表</h4>
  <div>
    <?php if (can('supplier.create')): ?><a href="/supplier?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> 新增供应商</a><?php endif; ?>
    <a href="/supplier/rating" class="btn btn-success"><i class="fas fa-star"></i> 评级</a>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-upload"></i> 批量导入</button>
  </div>
</div>
<div class="search-bar mb-3">
  <form method="get" class="d-flex flex-wrap align-items-center gap-2">
    <input type="text" name="search" class="form-control form-control-sm w-auto ms-2" placeholder="搜索编码/名称" value="<?=h($_GET['search'] ?? '')?>">
    <select name="level" class="form-control form-control-sm w-auto ms-2" onchange="this.form.submit()">
      <option value="">全部等级</option>
      <option value="A" <?=($_GET['level'] ?? '') === 'A' ? 'selected' : ''?>>A级</option>
      <option value="B" <?=($_GET['level'] ?? '') === 'B' ? 'selected' : ''?>>B级</option>
      <option value="C" <?=($_GET['level'] ?? '') === 'C' ? 'selected' : ''?>>C级</option>
    </select>
    <select name="blacklist" class="form-control form-control-sm w-auto ms-2" onchange="this.form.submit()">
      <option value="">全部</option>
      <option value="0" <?=($_GET['blacklist'] ?? '') === '0' ? 'selected' : ''?>>正常</option>
      <option value="1" <?=($_GET['blacklist'] ?? '') === '1' ? 'selected' : ''?>>黑名单</option>
    </select>
    <button type="submit" class="btn-icon btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/supplier?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:8%">编码</th><th class="text-start">名称</th><th style="width:8%">联系人</th><th style="width:11%">电话</th><th style="width:14%">邮箱</th><th style="width:7%">等级</th><th style="width:8%">黑名单</th><th style="width:7%">物料数</th><th style="width:16%">操作</th></tr></thead>
      <tbody>
        <tr><td colspan="10" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="importModal">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">批量导入供应商</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="import">
        <div class="alert alert-info py-2">
          <i class="fas fa-info-circle ms-1"></i> 下载 <a href="/supplier?action=import_template" target="_blank">导入模板</a>，填写后上传
        </div>
        <div class="mb-3">
          <label>上传Excel文件</label>
          <input type="file" name="import_file" class="form-control" accept=".xls,.xlsx" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">导入</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
