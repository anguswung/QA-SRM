<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><i class="fas fa-<?=$id ? 'edit' : 'plus'?>"></i> <?=h($title)?></div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="<?=$id ? 'update' : 'create'?>">
          <?php if ($id): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group"><label>CAS号<span class="text-danger">*</span></label><input type="text" name="cas_no" class="form-control" value="<?=h($item['cas_no'] ?? '')?>" required></div>
            </div>
            <div class="col-md-3">
              <div class="form-group"><label>EC号</label><input type="text" name="ec_no" class="form-control" value="<?=h($item['ec_no'] ?? '')?>"></div>
            </div>
            <div class="col-md-3">
              <div class="form-group"><label>索引号</label><input type="text" name="index_no" class="form-control" value="<?=h($item['index_no'] ?? '')?>"></div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group"><label>中文名称 <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" value="<?=h($item['name'] ?? '')?>" required></div>
            </div>
            <div class="col-md-6">
              <div class="form-group"><label>英文名称</label><input type="text" name="name_en" class="form-control" value="<?=h($item['name_en'] ?? '')?>"></div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group"><label>分子式</label><input type="text" name="molecular_formula" class="form-control" value="<?=h($item['molecular_formula'] ?? '')?>"></div>
            </div>
            <div class="col-md-8">
              <div class="form-group"><label>别名</label><input type="text" name="alias" class="form-control" value="<?=h($item['alias'] ?? '')?>"></div>
            </div>
          </div>
          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="isControlled" name="is_controlled" value="1" <?=(int)($item['is_controlled'] ?? 0) ? 'checked' : ''?>>
              <label class="custom-control-label" for="isControlled">受控物质</label>
            </div>
          </div>
          <div class="form-group"><label>可能用途</label>
            <select name="maybe_use_ids[]" class="form-control" multiple>
              <?php foreach ($uses as $u): ?>
              <option value="<?=$u['id']?>" <?=in_array($u['id'], $selectedIds ?? []) ? 'selected' : ''?>><?=h($u['name'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">保存</button>
          <a href="/substance" class="btn btn-secondary">取消</a>
        </form>
      </div>
    </div>
  </div>
</div>
