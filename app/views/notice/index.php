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
      <thead><tr><th>#</th><th>标题</th><th>发布人</th><th>发布时间</th><?php if (isSupplier()): ?><th>已读</th><?php endif; ?><?php if (isAdmin()): ?><th>操作</th><?php endif; ?></tr></thead>
      <tbody>
        <?php $seq = 0; foreach ($notices as $n):
          $attachments = DB::getInstance()->query("SELECT * FROM notice_attachment WHERE notice_id=?", [$n['id']]);
        ?>
        <tr>
          <td class="text-muted"><?=++$seq?></td>
          <td><a href="javascript:;" onclick="showNotice(<?=htmlspecialchars(json_encode($n['title']), ENT_QUOTES)?>,<?=htmlspecialchars(json_encode($n['content']), ENT_QUOTES)?>,'<?=h($n['publisher_name'])?>','<?=h($n['publish_time'] ?: $n['created_at'])?>',<?=htmlspecialchars(json_encode($attachments), ENT_QUOTES)?>)"><?=h($n['title'])?></a></td>
          <td><?=h($n['publisher_name'])?></td>
          <td><small><?=h($n['publish_time'] ?: $n['created_at'])?></small></td>
          <?php if (isSupplier()): ?>
          <td><?=$n['is_read'] ? '<span class="status-tag success">已读</span>' : '<span class="status-tag warning">未读</span>'?></td>
          <?php endif; ?>
          <?php if (isAdmin() && can('notice.delete')): ?>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editNoticeModal"
              data-id="<?=$n['id']?>" data-title="<?=h($n['title'])?>" data-content="<?=h($n['content'])?>"
              data-has-attachment="<?=!empty($attachments) ? 1 : 0?>"><i class="fas fa-edit"></i></button>
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
        <h4 class="modal-title" id="viewTitle"></h4>
        <button class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="text-right text-muted mb-3 pb-2 border-bottom" style="font-size:13px">
          <span id="viewPubInfo"></span>
        </div>
        <div id="viewContent" class="ql-editor" style="padding:0"></div>
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
        <div class="form-group">
          <label>内容</label>
          <div id="notice-editor"></div>
          <input type="hidden" name="content" id="noticeContent">
        </div>
        <div class="form-group"><label>附件</label><input type="file" name="attachment" class="form-control-file"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" id="noticeSubmitBtn">发布</button><button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (isAdmin() && can('notice.create')): ?>
<div class="modal fade" id="editNoticeModal">
  <div class="modal-dialog modal-lg">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">编辑通知</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editNoticeId">
        <div class="form-group"><label>标题</label><input type="text" name="title" id="editNoticeTitle" class="form-control" required></div>
        <div class="form-group">
          <label>内容</label>
          <div id="edit-notice-editor"></div>
          <input type="hidden" name="content" id="editNoticeContent">
        </div>
        <div class="form-group"><label>附件（重新上传会替换旧附件）</label><input type="file" name="attachment" class="form-control-file"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" id="editNoticeSubmitBtn">保存</button><button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>
$(function() {
function imgHandler(quill) {
  return function() {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.click();
    input.onchange = function() {
      var file = input.files[0];
      if (!file) return;
      var fd = new FormData();
      fd.append('file', file);
      fetch('/upload-image.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          if (res.code === 0 && res.data && res.data.url) {
            var range = quill.getSelection(true);
            quill.insertEmbed(range.index, 'image', res.data.url);
          } else { alert('图片上传失败: ' + (res.msg || '未知错误')); }
        })
        .catch(function() { alert('图片上传失败'); });
    };
  };
}
var noticeQuill = null;
$('#noticeModal').on('shown.bs.modal', function() {
  if (typeof Quill === 'undefined') { alert('编辑器加载失败，请刷新页面重试'); return; }
  if (!noticeQuill) {
    noticeQuill = new Quill('#notice-editor', {
      theme: 'snow',
      modules: {
        toolbar: [
          [{ 'header': [1, 2, 3, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ 'color': [] }, { 'background': [] }],
          [{ 'size': ['small', false, 'large', 'huge'] }],
          ['link', 'image'],
          ['clean']
        ]
      },
      placeholder: '请输入通知内容...'
    });
    noticeQuill.getModule('toolbar').addHandler('image', imgHandler(noticeQuill));
  }
});
$(document).on('click', '#noticeSubmitBtn', function() {
  if (noticeQuill) document.getElementById('noticeContent').value = noticeQuill.root.innerHTML;
  document.querySelector('#noticeModal form').submit();
});
var editNoticeQuill = null;
$('#editNoticeModal').on('shown.bs.modal', function(e) {
  var btn = $(e.relatedTarget);
  $('#editNoticeId').val(btn.data('id'));
  $('#editNoticeTitle').val(btn.data('title'));
  if (typeof Quill === 'undefined') { alert('编辑器加载失败，请刷新页面重试'); return; }
  if (!editNoticeQuill) {
    editNoticeQuill = new Quill('#edit-notice-editor', {
      theme: 'snow',
      modules: {
        toolbar: [
          [{ 'header': [1, 2, 3, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ 'color': [] }, { 'background': [] }],
          [{ 'size': ['small', false, 'large', 'huge'] }],
          ['link', 'image'],
          ['clean']
        ]
      },
      placeholder: '请输入通知内容...'
    });
    editNoticeQuill.getModule('toolbar').addHandler('image', imgHandler(editNoticeQuill));
  }
  editNoticeQuill.root.innerHTML = btn.data('content') || '';
  editNoticeQuill.update();
});
$(document).on('click', '#editNoticeSubmitBtn', function() {
  if (editNoticeQuill) document.getElementById('editNoticeContent').value = editNoticeQuill.root.innerHTML;
  document.querySelector('#editNoticeModal form').submit();
});
});
function showNotice(title, content, publisher, pubTime, attachments) {
  $('#viewTitle').text(title);
  $('#viewPubInfo').text('发布于 ' + (publisher || '-') + ' | 时间: ' + (pubTime || '-'));
  $('#viewContent').html(content);
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
