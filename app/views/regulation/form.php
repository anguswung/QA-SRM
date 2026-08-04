<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><i class="fas fa-<?=$id ? 'edit' : 'plus'?>"></i> <?=h($title)?></div>
      <div class="card-body">
        <form method="post"><?=csrf_field()?>
          <input type="hidden" name="action" value="<?=$id ? 'update' : 'create'?>">
          <?php if ($id): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group"><label>法规编号</label><input type="text" name="regulation_no" class="form-control" value="<?=h($item['regulation_no'] ?? '')?>"></div>
            </div>
            <div class="col-md-6">
              <div class="form-group">                <label>分类</label>
                <select name="category" class="form-control">
                  <option value="">请选择</option>
                  <?php foreach (['RoHS','REACH','HF','CA65','包装指令','电池指令'] as $c): ?>
                  <option value="<?=h($c)?>" <?=($item['category'] ?? '') === $c ? 'selected' : ''?>><?=h($c)?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group"><label>法规名称</label><input type="text" name="name" class="form-control" value="<?=h($item['name'] ?? '')?>" required></div>
          <div class="form-group"><label>英文名称</label><input type="text" name="name_en" class="form-control" value="<?=h($item['name_en'] ?? '')?>"></div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group"><label>发布机构</label><input type="text" name="issuing_body" class="form-control" value="<?=h($item['issuing_body'] ?? '')?>"></div>
            </div>
            <div class="col-md-3">
              <div class="form-group"><label>发布日期</label><input type="date" name="publish_date" class="form-control" value="<?=h($item['publish_date'] ?? '')?>"></div>
            </div>
            <div class="col-md-3">
              <div class="form-group"><label>生效日期</label><input type="date" name="effective_date" class="form-control" value="<?=h($item['effective_date'] ?? '')?>"></div>
            </div>
          </div>
          <div class="form-group"><label>版本</label><input type="text" name="version" class="form-control" value="<?=h($item['version'] ?? 'V1.0')?>"></div>
          <div class="form-group"><label>描述</label><textarea name="description" class="form-control" rows="3"><?=h($item['description'] ?? '')?></textarea></div>
          <button type="submit" class="btn btn-primary">保存</button>
          <a href="/regulation" class="btn btn-secondary">取消</a>
        </form>
      </div>
    </div>
  </div>
</div>
