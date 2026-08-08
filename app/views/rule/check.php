<div class="page-header">
  <h4><i class="fas fa-search"></i> 合规检查</h4>
  <div>
    <a href="/rule" class="btn btn-success">规则管理</a>
  </div>
</div>
<div class="card">
  <div class="card-body">
    <div class="mb-3">
      <label>选择供应商</label>
      <select id="supplierSelect" class="form-control select-max-400">
        <option value="0">我的物料</option>
        <?php foreach ($suppliers as $s): ?>
        <option value="<?=$s['id']?>"><?=h($s['code'])?> - <?=h($s['name'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" onclick="runCheck()"><i class="fas fa-play"></i> 执行检查</button>
  </div>
</div>
<div id="checkResults" class="mt-3"></div>
<script>
function runCheck() {
  var sid = document.getElementById('supplierSelect').value;
  var btn = event.target;
  btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin ms-1"></i>检查中...';
  document.getElementById('checkResults').innerHTML = '';
  fetch('/rule', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=check&supplier_id=' + sid + '&_csrf_token=' + encodeURIComponent(getCsrfToken())})
    .then(function(r){ return r.json(); })
    .then(function(d){
      var html = '<div class="card"><div class="card-header">检查结果</div><div class="card-body">';
      if (d.overall === 'ok') {
        html += '<div class="alert alert-success"><i class="fas fa-check-circle ms-1"></i>所有规则已满足</div>';
      } else if (d.overall === 'warning') {
        html += '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle ms-1"></i>存在提醒事项</div>';
      } else {
        html += '<div class="alert alert-danger"><i class="fas fa-times-circle ms-1"></i>存在强制要求未满足，无法提交</div>';
      }
      if (d.mandatory.length > 0) {
        html += '<h6 class="text-danger mt-3">强制要求（需处理后才能提交）</h6><ul class="list-group mb-3">';
        d.mandatory.forEach(function(m){ html += '<li class="list-group-item list-group-item-danger py-2"><i class="fas fa-times-circle text-danger ms-2"></i>' + m + '</li>'; });
        html += '</ul>';
      }
      if (d.warnings.length > 0) {
        html += '<h6 class="text-warning mt-3">提醒事项</h6><ul class="list-group">';
        d.warnings.forEach(function(m){ html += '<li class="list-group-item list-group-item-warning py-2"><i class="fas fa-exclamation-circle text-warning ms-2"></i>' + m + '</li>'; });
        html += '</ul>';
      }
      html += '</div></div>';
      document.getElementById('checkResults').innerHTML = html;
    })
    .catch(function(){ document.getElementById('checkResults').innerHTML = '<div class="alert alert-danger">检查失败</div>'; })
    .finally(function(){ btn.disabled = false; btn.innerHTML = '<i class="fas fa-play"></i> 执行检查'; });
}
</script>
