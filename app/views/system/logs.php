<div class="page-header">
  <h4><i class="fas fa-clipboard-list"></i> 操作日志</h4>
  <div>
    <form method="post" action="/system/logs" id="quickCleanForm" class="d-inline-flex align-items-center"><?=csrf_field()?>
      <input type="hidden" name="action" value="clean_logs">
      <select name="keep_days" class="form-control form-control-sm w-auto ms-2" style="width:auto" onchange="document.getElementById('quickKeepDays').value=this.value">
        <option value="30">30 天前</option>
        <option value="60">60 天前</option>
        <option value="90">90 天前</option>
        <option value="180">180 天前</option>
        <option value="365">365 天前</option>
      </select>
      <input type="hidden" name="quick_keep_days" id="quickKeepDays" value="30">
      <button type="submit" class="btn btn-outline-danger" onclick="return confirm('确定清理30天前的日志？此操作不可恢复。')"><i class="fas fa-trash-alt ms-1"></i> 清理日志</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/system/logs?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:12%">时间</th><th style="width:16%">操作人</th><th style="width:7%">操作</th><th style="width:9%">模块</th><th style="width:7%">目标类型</th><th style="width:6%">目标ID</th><th>详情</th><th style="width:11%">IP</th></tr></thead>
      <tbody>
        <tr><td colspan="9" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
document.querySelector('[name="keep_days"]').addEventListener('change', function() {
    var days = this.value;
    document.getElementById('quickKeepDays').value = days;
    document.querySelector('#quickCleanForm button[type="submit"]').setAttribute('onclick',"return confirm('确定清理" + days +"天前的日志？此操作不可恢复。')");
});
</script>
