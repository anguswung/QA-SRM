<div class="page-header">
  <h5><i class="fas fa-truck"></i> 供应商列表</h5>
  <div>
    <?php if (can('supplier.create')): ?><a href="/supplier?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 新增供应商</a><?php endif; ?>
    <a href="/supplier/rating" class="btn btn-sm btn-outline-info"><i class="fas fa-star"></i> 评级</a>
    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-upload"></i> 批量导入</button>
  </div>
</div>
<div class="search-bar mb-3">
  <form method="get" class="form-inline">
    <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="搜索编码/名称" value="<?=h($_GET['search'] ?? '')?>">
    <select name="level" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部等级</option>
      <option value="A" <?=($_GET['level'] ?? '') === 'A' ? 'selected' : ''?>>A级</option>
      <option value="B" <?=($_GET['level'] ?? '') === 'B' ? 'selected' : ''?>>B级</option>
      <option value="C" <?=($_GET['level'] ?? '') === 'C' ? 'selected' : ''?>>C级</option>
    </select>
    <select name="blacklist" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部</option>
      <option value="0" <?=($_GET['blacklist'] ?? '') === '0' ? 'selected' : ''?>>正常</option>
      <option value="1" <?=($_GET['blacklist'] ?? '') === '1' ? 'selected' : ''?>>黑名单</option>
    </select>
    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th style="width:80px">编码</th><th>名称</th><th style="width:80px">联系人</th><th style="width:120px">电话</th><th style="width:160px">邮箱</th><th style="width:70px">等级</th><th style="width:80px">黑名单</th><th style="width:70px">物料数</th><th style="width:180px">操作</th></tr></thead>
      <tbody>
        <?php foreach ($suppliers as $s): ?>
        <tr>
          <td><?=h($s['code'])?></td>
          <td><a href="/supplier?action=detail&id=<?=$s['id']?>"><?=h($s['name'])?></a></td>
          <td><?=h($s['contact_person'])?></td>
          <td><?=h($s['contact_phone'])?></td>
          <td><small><?=h($s['email'] ?: '-')?></small></td>
          <td><?=statusBadge($s['level'] . '_')?></td>
          <td><?=$s['blacklist'] ? '<span class="status-tag danger">黑名单</span>' : '<span class="status-tag success">正常</span>'?></td>
          <td><span class="badge badge-soft-info"><?=(int)$s['material_count']?></span></td>
          <td>
            <?php if (can('supplier.edit')): ?><a href="/supplier?action=edit&id=<?=$s['id']?>" class="btn btn-sm btn-outline-primary" title="编辑"><i class="fas fa-edit"></i></a><?php endif; ?>
            <?php if (can('supplier.edit')): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('确定<?=$s['blacklist'] ? '移出' : '加入'?><?=csrf_field()?>黑名单？')">
              <input type="hidden" name="action" value="blacklist"><input type="hidden" name="id" value="<?=$s['id']?>">
              <button class="btn btn-sm btn-<?=$s['blacklist'] ? 'success' : 'danger'?>">
                <i class="fas fa-<?=$s['blacklist'] ? 'check-circle' : 'ban'?>"></i> <?=$s['blacklist'] ? '移出黑名单' : '禁用账号'?>
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="importModal">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">批量导入供应商</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="import">
        <div class="alert alert-info py-2">
          <i class="fas fa-info-circle mr-1"></i> 下载 <a href="/supplier?action=import_template" target="_blank">导入模板</a>，填写后上传
        </div>
        <div class="form-group">
          <label>上传Excel文件</label>
          <input type="file" name="import_file" class="form-control-file" accept=".xls,.xlsx" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">导入</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
