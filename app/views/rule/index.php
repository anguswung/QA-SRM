<div class="page-header">
  <h4><i class="fas fa-gavel"></i> 合规规则管理</h4>
  <div>
    <a href="/rule?action=check" class="btn btn-success"><i class="fas fa-search"></i> 合规检查</a>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/rule?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:10%">编码</th><th>规则名称</th><th style="width:14%">文档分类</th><th style="width:10%">类型</th><th style="width:8%">状态</th><th style="width:12%">操作</th></tr></thead>
      <tbody>
        <tr><td colspan="7" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<script>
function toggleRule(id) {
  fetch('/rule', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=toggle&id='+id+'&_csrf_token='+encodeURIComponent(getCsrfToken())})
    .then(function(){ location.reload(); });
}
</script>
