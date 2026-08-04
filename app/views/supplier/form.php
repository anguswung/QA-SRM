<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><i class="fas fa-<?=$id ? 'edit' : 'plus'?>"></i> <?=h($title)?></div>
      <div class="card-body">
        <form method="post"><?=csrf_field()?>
          <input type="hidden" name="action" value="<?=$id ? 'update' : 'create'?>">
          <?php if ($id): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
          <?php if (isSupplier() && (int)$item['id'] === userSupplierId()): ?>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group"><label>供应商编码</label><input type="text" class="form-control" value="<?=h($item['code'] ?? '')?>" readonly></div>
            </div>
            <div class="col-md-6">
              <div class="form-group"><label>供应商名称</label><input type="text" class="form-control" value="<?=h($item['name'] ?? '')?>" readonly></div>
            </div>
          </div>
          <?php else: ?>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>供应商编码<span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control" value="<?=h($item['code'] ?? '')?>" <?=$id ? 'readonly' : 'required'?> placeholder="输入ERP编码">
                <?php if (!$id): ?><small class="text-muted">编码同时也是登录账号和初始密码</small><?php endif; ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group"><label>供应商名称<span class="text-danger">*</span></label><input type="text" name="name" class="form-control" value="<?=h($item['name'] ?? '')?>" required></div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group"><label>统一信用代码</label><input type="text" name="credit_code" class="form-control" value="<?=h($item['credit_code'] ?? '')?>"></div>
            </div>
            <div class="col-md-4">
              <div class="form-group"><label>初始等级</label>
                <select name="level" class="form-control">
                  <option value="A" <?=($item['level'] ?? 'C') === 'A' ? 'selected' : ''?>>A级</option>
                  <option value="B" <?=($item['level'] ?? 'C') === 'B' ? 'selected' : ''?>>B级</option>
                  <option value="C" <?=($item['level'] ?? 'C') === 'C' ? 'selected' : ''?>>C级</option>
                </select>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group"><label>简称</label><input type="text" name="name_short" class="form-control" value="<?=h($item['name_short'] ?? '')?>"></div>
            </div>
            <div class="col-md-4">
              <div class="form-group"><label>联系人</label><input type="text" name="contact_person" class="form-control" value="<?=h($item['contact_person'] ?? '')?>"></div>
            </div>
            <div class="col-md-4">
              <div class="form-group"><label>联系电话</label><input type="text" name="contact_phone" class="form-control" value="<?=h($item['contact_phone'] ?? '')?>"></div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group"><label>邮箱</label><input type="email" name="email" class="form-control" value="<?=h($item['email'] ?? '')?>"></div>
            </div>
            <div class="col-md-8">
              <div class="form-group"><label>地址</label><input type="text" name="address" class="form-control" value="<?=h($item['address'] ?? '')?>"></div>
            </div>
          </div>
          <?php if (!$id): ?>
          <div class="alert alert-info py-2">供应商登录账号和初始密码为分配的三位编码</div>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary">保存</button>
          <a href="/supplier" class="btn btn-secondary">取消</a>
        </form>
      </div>
    </div>
  </div>
</div>
