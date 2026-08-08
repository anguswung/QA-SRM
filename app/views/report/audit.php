<div class="page-header">
  <h4><i class="fas fa-clipboard-check"></i> 报告审核</h4>
  <div>
    <a href="/report" class="btn btn-success">报告列表</a>
  </div>
</div>
<div class="search-bar mb-3">
  <div class="btn-group">
    <a href="/report/audit?status=pending" class="btn btn-sm <?=($_GET['status'] ?? 'pending') === 'pending' ? 'btn-primary' : 'btn-outline-primary'?>">待审核</a>
    <a href="/report/audit?status=rejected" class="btn btn-sm <?=($_GET['status'] ?? '') === 'rejected' ? 'btn-primary' : 'btn-outline-primary'?>">已退回</a>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/report/audit?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:12%">报告编号</th><th>物料</th><th style="width:10%">供应商</th><th style="width:7%">类型</th><th style="width:14%">机构</th><th style="width:10%">日期</th><th style="width:8%">状态</th><th style="width:10%">操作</th></tr></thead>
      <tbody>
        <tr><td colspan="9" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="auditModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">报告审核</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="id" id="auditId">
        <input type="hidden" name="audit_status" id="auditStatus">
        <div class="mb-3"><label>审核意见</label><textarea name="audit_comment" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">确认</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>
<script>
$('#auditModal').on('show.bs.modal', function(e){
  var btn = $(e.relatedTarget);
  $(this).find('#auditId').val(btn.data('id'));
  $(this).find('#auditStatus').val(btn.data('status'));
});
</script>
