<div class="page-header">
  <h5><i class="fas fa-bullhorn"></i> 通知公告</h5>
  <?php if (isAdmin() && can('notice.create')): ?>
  <div>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#noticeModal"><i class="fas fa-plus"></i> 发布通知</button>
  </div>
  <?php endif; ?>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th>标题</th><th>发布人</th><th>发布时间</th><?php if (isSupplier()): ?><th>已读</th><?php endif; ?><?php if (isAdmin()): ?><th>操作</th><?php endif; ?></tr></thead>
      <tbody>
        <?php foreach ($notices as $n):
          $attachments = DB::getInstance()->query("SELECT * FROM notice_attachment WHERE notice_id=?", [$n['id']]);
        ?>
        <tr>
          <td><a href="javascript:;" onclick="showNotice(<?=htmlspecialchars(json_encode($n['title']), ENT_QUOTES)?>,<?=htmlspecialchars(json_encode($n['content']), ENT_QUOTES)?>,'<?=h($n['publisher_name'])?>','<?=h($n['publish_time'] ?: $n['created_at'])?>',<?=htmlspecialchars(json_encode($attachments), ENT_QUOTES)?>)"><?=h($n['title'])?></a></td>
          <td><?=h($n['publisher_name'])?></td>
          <td><small><?=h($n['publish_time'] ?: $n['created_at'])?></small></td>
          <?php if (isSupplier()): ?>
          <td><?=$n['is_read'] ? '<span class="status-tag success">已读</span>' : '<span class="status-tag warning">未读</span>'?></td>
          <?php endif; ?>
          <?php if (isAdmin() && can('notice.delete')): ?>
          <td>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$n['id']?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="viewModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewTitle"></h5>
        <button class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="text-muted mb-3 pb-2 border-bottom" style="font-size:13px">
          <span id="viewPubInfo"></span>
        </div>
        <div id="viewContent" style="white-space:pre-wrap"></div>
        <div id="viewAttachments" class="mt-3 pt-2 border-top"></div>
      </div>
    </div>
  </div>
</div>
<?php if (isAdmin() && can('notice.create')): ?>
<div class="modal fade" id="noticeModal">
  <div class="modal-dialog modal-lg">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">发布通知</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label>标题</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label>内容</label><textarea name="content" class="form-control" rows="6"></textarea></div>
        <div class="form-group"><label>附件</label><input type="file" name="attachment" class="form-control-file"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">发布</button><button class="btn btn-secondary" data-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>
function showNotice(title, content, publisher, pubTime, attachments) {
  $('#viewTitle').text(title);
  $('#viewPubInfo').text('发布于 ' + (publisher || '-') + ' | 时间: ' + (pubTime || '-'));
  $('#viewContent').text(content);
  var html = '';
  if (attachments && attachments.length > 0) {
    html = '<strong>附件:</strong><br>';
    for (var i = 0; i < attachments.length; i++) {
      var a = attachments[i];
      var path = a.file_path ? a.file_path.replace(/\\/g, '/') : '';
      html += '<a href="/' + path + '" target="_blank" class="btn btn-sm btn-outline-info mr-2 mt-1"><i class="fas fa-paperclip"></i> ' + (a.file_name || '下载') + '</a>';
    }
  }
  $('#viewAttachments').html(html);
  $('#viewModal').modal('show');
}
</script>
