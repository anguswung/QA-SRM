<div class="page-header">
  <h5><i class="fas fa-cog"></i> 系统参数配置</h5>
</div>
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-building mr-1"></i> 公司信息</div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data"><?=csrf_field()?>
      <?php
      $companyKeys = ['company_name' => '公司名称', 'company_short_name' => '公司简称', 'company_phone' => '公司电话', 'company_address' => '公司地址', 'system_name' => '系统名称'];
      foreach ($companyKeys as $ck => $cl):
        $val = '';
        foreach ($configs as $c) { if ($c['config_key'] === $ck) { $val = $c['config_value']; break; } }
      ?>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label"><?=h($cl)?></label>
        <div class="col-sm-10">
          <input type="hidden" name="config_key[]" value="<?=h($ck)?>">
          <input type="text" name="config_value[]" class="form-control" value="<?=h($val)?>">
        </div>
      </div>
      <?php endforeach; ?>
      <?php
      $logoVal = '';
      foreach ($configs as $c) { if ($c['config_key'] === 'company_logo') { $logoVal = $c['config_value']; break; } }
      ?>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label">公司Logo</label>
        <div class="col-sm-10">
          <input type="file" name="company_logo" class="form-control-file" accept="image/*">
          <?php if ($logoVal): ?>
          <div class="mt-2"><img src="<?=h($logoVal)?>" class="summary-img"></div>
          <small class="text-muted">当前Logo，上传新图将替换</small>
          <?php else: ?>
          <small class="text-muted">默认使用 /assets/images/logo.png</small>
          <?php endif; ?>
        </div>
      </div>
      <?php if (can('system.config')): ?><button class="btn btn-primary">保存</button><?php endif; ?>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-header"><i class="fas fa-sliders-h mr-1"></i> 其他参数</div>
  <div class="card-body">
    <form method="post"><?=csrf_field()?>
      <table class="table mb-0">
        <thead><tr><th>配置键</th><th>配置值</th><th>说明</th></tr></thead>
        <tbody>
          <?php foreach ($configs as $c):
            if (in_array($c['config_key'], ['company_name','company_short_name','company_phone','company_address','company_logo','system_name'])) continue;
          ?>
          <tr>
            <td><?=h($c['config_key'])?></td>
            <td><input type="hidden" name="config_key[]" value="<?=h($c['config_key'])?>"><input type="text" name="config_value[]" class="form-control form-control-sm" value="<?=h($c['config_value'])?>"></td>
            <td><small class="text-muted"><?=h($c['description'] ?? '')?></small></td>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td><input type="text" name="config_key[]" class="form-control form-control-sm" placeholder="新配置键"></td>
            <td><input type="text" name="config_value[]" class="form-control form-control-sm" placeholder="值"></td>
            <td></td>
          </tr>
        </tbody>
      </table>
      <?php if (can('system.config')): ?><button class="btn btn-primary mt-2">保存配置</button><?php endif; ?>
    </form>
  </div>
</div>
