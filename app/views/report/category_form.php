<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-<?=$id ? 'edit' : 'plus'?>"></i> <?=h($title)?></div>
      <div class="card-body">
        <form method="post"><?=csrf_field()?>
          <input type="hidden" name="action" value="<?=$id ? 'update' : 'create'?>">
          <?php if ($id): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
          <div class="mb-3"><label>分类编码</label><input type="text" name="code" class="form-control" value="<?=h($item['code'] ?? '')?>" required></div>
          <div class="mb-3"><label>分类名称</label><input type="text" name="name" class="form-control" value="<?=h($item['name'] ?? '')?>" required></div>
          <div class="row g-3">
            <div class="col-6"><div class="mb-3"><label>有效期/天</label><input type="number" name="validity_days" class="form-control" value="<?=h($item['validity_days'] ?? 365)?>"></div></div>
            <div class="col-6"><div class="mb-3"><label>排序</label><input type="number" name="sort_order" class="form-control" value="<?=h($item['sort_order'] ?? 0)?>"></div></div>
          </div>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input type="checkbox" class="form-check-input" id="isRequired" name="is_required" value="1" <?=($item['is_required'] ?? 1) ? 'checked' : ''?>>
              <label class="form-check-label" for="isRequired">强制要求（供应商必须提交）</label>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">保存</button>
          <a href="/report/categories" class="btn btn-secondary">取消</a>
        </form>
      </div>
    </div>
  </div>
</div>
