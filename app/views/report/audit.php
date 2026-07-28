<div class="page-header">
  <h5><i class="fas fa-clipboard-check"></i> 报告审核</h5>
  <div>
    <a href="/report" class="btn btn-sm btn-outline-secondary">报告列表</a>
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
    <table class="table table-hover datatable">
      <thead><tr><th>报告编号</th><th>物料</th><th>供应商</th><th>类型</th><th>机构</th><th>日期</th><th>状态</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td><?=h($r['report_no'])?></td>
          <td><?=h($r['material_name'] ?? '-')?></td>
          <td><?=h($r['supplier_name'] ?? '-')?></td>
          <td><span class="badge badge-soft-info"><?=h($r['report_type'])?></span></td>
          <td><?=h($r['test_agency'])?></td>
          <td><?=h($r['test_date'])?></td>
          <td><?=statusBadge($r['audit_status'])?></td>
          <td>
            <a href="/report/elements?report_id=<?=$r['id']?>" class="btn btn-sm btn-outline-info" title="元素含量"><i class="fas fa-flask"></i></a>
            <?php if (can('report.audit')): ?>
            <button class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#auditModal"
              data-id="<?=$r['id']?>" data-status="approved"><i class="fas fa-check"></i></button>
            <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#auditModal"
              data-id="<?=$r['id']?>" data-status="rejected"><i class="fas fa-times"></i></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="auditModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">报告审核</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="id" id="auditId">
        <input type="hidden" name="audit_status" id="auditStatus">
        <div class="form-group"><label>审核意见</label><textarea name="audit_comment" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">确认</button>
        <button class="btn btn-secondary" data-dismiss="modal">取消</button>
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
