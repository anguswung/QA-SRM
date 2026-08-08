<div class="page-header">
  <h4><i class="fas fa-cog"></i> 系统参数配置</h4>
</div>
<div class="card mb-3">
  <div class="card-header"><i class="fas fa-building ms-1"></i> 公司信息</div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data"><?=csrf_field()?>
      <?php
      $companyKeys = ['company_name' => '公司名称', 'company_short_name' => '公司简称', 'company_phone' => '公司电话', 'company_address' => '公司地址', 'system_name' => '系统名称'];
      foreach ($companyKeys as $ck => $cl):
        $val = '';
        foreach ($configs as $c) { if ($c['config_key'] === $ck) { $val = $c['config_value']; break; } }
      ?>
      <div class="mb-3 row">
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
      <div class="mb-3 row">
        <label class="col-sm-2 col-form-label">公司Logo</label>
        <div class="col-sm-10">
          <input type="file" name="company_logo" class="form-control" accept="image/*">
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
  <div class="card-header"><i class="fas fa-sliders-h ms-1"></i> 其他参数</div>
  <div class="card-body">
    <form method="post"><?=csrf_field()?>
      <table class="table mb-0">
        <thead><tr><th style="width:60px">#</th><th>配置键</th><th>配置值</th><th>说明</th></tr></thead>
        <tbody>
<?php $__rnum = 0; ?>
          <?php foreach ($configs as $c):
            if (in_array($c['config_key'], ['company_name','company_short_name','company_phone','company_address','company_logo','system_name'])) continue;
            if (strpos($c['config_key'], 'mail') === 0) continue;
          ?>
          <tr>
            <td class="row-num"><?=++$__rnum?></td><td><?=h($c['config_key'])?></td>
            <td><input type="hidden" name="config_key[]" value="<?=h($c['config_key'])?>"><input type="text" name="config_value[]" class="form-control form-control-sm" value="<?=h($c['config_value'])?>"></td>
            <td><small class="text-muted"><?=h($c['description'] ?? '')?></small></td>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td class="row-num"><?=++$__rnum?></td><td><input type="text" name="config_key[]" class="form-control form-control-sm" placeholder="新配置键"></td>
            <td><input type="text" name="config_value[]" class="form-control form-control-sm" placeholder="值"></td>
            <td></td>
          </tr>
        </tbody>
      </table>
      <?php
      if (can('system.config')): ?><button class="btn btn-primary mt-2">保存配置</button><?php endif; ?>
    </form>
  </div>
</div>

<?php
$gvk = function ($key, $default = '') use ($configs) {
    foreach ($configs as $c) { if ($c['config_key'] === $key) return $c['config_value']; }
    return $default;
};
?>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-envelope ms-1"></i> 邮箱设置（SMTP）</div>
  <div class="card-body">
    <form method="post"><?=csrf_field()?>
      <?php $mailSMTP = [
        'mail_smtp_host' => 'SMTP 服务器', 'mail_smtp_port' => 'SMTP 端口（默认587）',
        'mail_smtp_encryption' => '加密方式', 'mail_smtp_username' => 'SMTP 账号',
        'mail_smtp_password' => 'SMTP 密码', 'mail_from_address' => '发件人邮箱',
        'mail_from_name' => '发件人名称',
      ]; foreach ($mailSMTP as $ck => $cl): $v = $gvk($ck); ?>
      <div class="mb-2 row">
        <label class="col-sm-3 col-form-label"><?=h($cl)?></label>
        <div class="col-sm-9">
          <input type="hidden" name="config_key[]" value="<?=h($ck)?>">
          <?php if ($ck === 'mail_smtp_encryption'): ?>
          <select name="config_value[]" class="form-select form-select-sm">
            <?php foreach (['tls'=>'TLS（端口587）','ssl'=>'SSL（端口465）','none'=>'无'] as $ov=>$ol): ?>
            <option value="<?=h($ov)?>" <?=($v===$ov)?'selected':''?>><?=h($ol)?></option>
            <?php endforeach; ?>
          </select>
          <?php elseif ($ck === 'mail_smtp_password'): ?>
          <input type="password" name="config_value[]" class="form-control form-control-sm" value="<?=h($v)?>" autocomplete="off">
          <?php else: ?>
          <input type="text" name="config_value[]" class="form-control form-control-sm" value="<?=h($v)?>">
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (can('system.config')): ?><button class="btn btn-primary btn-sm">保存SMTP设置</button><?php endif; ?>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-file-invoice ms-1"></i> 邮件模板（到期报告提醒）</div>
  <div class="card-body">
    <form method="post"><?=csrf_field()?>
      <div class="mb-2 row">
        <label class="col-sm-3 col-form-label">邮件主题</label>
        <div class="col-sm-9">
          <input type="hidden" name="config_key[]" value="mail_template_expiry_title">
          <input type="text" name="config_value[]" class="form-control form-control-sm" value="<?=h($gvk('mail_template_expiry_title','【{company_name}】检测报告到期提醒'))?>">
        </div>
      </div>
      <div class="mb-2 row">
        <label class="col-sm-3 col-form-label">邮件正文</label>
        <div class="col-sm-9">
          <input type="hidden" name="config_key[]" value="mail_template_expiry_body">
          <textarea name="config_value[]" rows="6" class="form-control form-control-sm font-monospace" placeholder="支持占位符：{material_name} {report_no} {report_type} {expiry_date} {days_left} {supplier_name} {company_name} {system_name}"><?=h($gvk('mail_template_expiry_body',''))?></textarea>
        </div>
      </div>
      <?php if (can('system.config')): ?><button class="btn btn-primary btn-sm">保存模板</button><?php endif; ?>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-bell ms-1"></i> 到期报告提醒发送</div>
  <div class="card-body">
    <form method="post" class="mb-3"><?=csrf_field()?>
      <div class="row align-items-center">
        <label class="col-sm-3 col-form-label">启用到期提醒</label>
        <div class="col-sm-9">
          <input type="hidden" name="config_key[]" value="mail_report_expiry_enabled">
          <select name="config_value[]" class="form-select form-select-sm w-auto">
            <option value="1" <?=$gvk('mail_report_expiry_enabled','1')==='1'?'selected':''?>>启用</option>
            <option value="0" <?=$gvk('mail_report_expiry_enabled','1')==='0'?'selected':''?>>停用</option>
          </select>
          <small class="text-muted ms-2">收件人为报告对应供应商的联系人与邮箱，同一报告每天只发送一次。</small>
        </div>
      </div>
      <?php if (can('system.config')): ?><button class="btn btn-primary btn-sm mt-2">保存开关</button><?php endif; ?>
    </form>
    <hr>
    <div class="d-flex flex-wrap gap-2">
      <form method="post" class="btn-group"><?=csrf_field()?>
        <input type="hidden" name="action" value="test_smtp">
        <button class="btn btn-outline-primary btn-sm"><i class="fas fa-plug"></i> 测试 SMTP</button>
      </form>
      <form method="post" class="btn-group"><?=csrf_field()?>
        <input type="hidden" name="action" value="send_test">
        <input type="email" name="mail_test_to" class="form-control form-control-sm" style="width:220px" placeholder="测试收件邮箱" required>
        <button class="btn btn-outline-success btn-sm"><i class="fas fa-paper-plane"></i> 发送测试邮件</button>
      </form>
      <form method="post" class="btn-group"><?=csrf_field()?>
        <input type="hidden" name="action" value="send_reminders">
        <button class="btn btn-success btn-sm" onclick="return confirm('确定立即发送所有到期报告提醒邮件？')"><i class="fas fa-envelope-open-text"></i> 立即发送到期提醒</button>
      </form>
    </div>
    <small class="text-muted d-block mt-2">生产环境可通过计划任务调用 <code>php scripts/send_expiry_reminders.php</code> 定时自动发送。</small>
  </div>
</div>
