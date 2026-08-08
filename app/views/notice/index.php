<div class="page-header">
  <h4><i class="fas fa-bullhorn"></i> 通知公告</h4>
  <?php if (canManage('notice.create')): ?>
  <div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#noticeModal"><i class="fas fa-plus"></i> 发布通知</button>
  </div>
  <?php endif; ?>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/notice?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th class="text-start">标题</th><th style="width:9%">发布人</th><th style="width:15%">发布时间</th><?php if (isSupplier()): ?><th style="width:7%">状态</th><?php endif; ?><?php if (isAdmin()): ?><th style="width:11%">操作</th><?php endif; ?></tr></thead>
      <tbody>
        <tr><td colspan="8" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="viewModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="viewTitle"></h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="text-end text-muted mb-3 pb-2 border-bottom fs-13">
          <span id="viewPubInfo"></span>
        </div>
        <div id="viewContent" class="ql-editor p-0-force"></div>
        <div id="viewAttachments" class="mt-3 pt-2 border-top"></div>
      </div>
    </div>
  </div>
</div>
<?php if (canManage('notice.create')): ?>
<div class="modal fade" id="noticeModal">
  <div class="modal-dialog modal-lg">
    <form method="post" enctype="multipart/form-data" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">发布通知</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="mb-3"><label>标题</label><input type="text" name="title" class="form-control" required></div>
        <div class="mb-3">
          <label>内容</label>
          <div id="notice-editor"></div>
          <input type="hidden" name="content" id="noticeContent">
        </div>
        <div class="mb-3"><label>附件</label><input type="file" name="attachment" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" id="noticeSubmitBtn">发布</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (canManage('notice.create')): ?>
<div class="modal fade" id="editNoticeModal">
  <div class="modal-dialog modal-lg">
    <form method="post" enctype="multipart/form-data" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">编辑通知</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editNoticeId">
        <div class="mb-3"><label>标题</label><input type="text" name="title" id="editNoticeTitle" class="form-control" required></div>
        <div class="mb-3">
          <label>内容</label>
          <div id="edit-notice-editor"></div>
          <input type="hidden" name="content" id="editNoticeContent">
        </div>
        <div class="mb-3"><label>附件（重新上传会替换旧附件）</label><input type="file" name="attachment" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" id="editNoticeSubmitBtn">保存</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
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
      fd.append('_csrf_token', getCsrfToken());
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
function showNotice(id, title, content, publisher, pubTime, attachments) {
  $('#viewTitle').text(title);
  $('#viewPubInfo').text('发布于 ' + (publisher || '-') + ' | 时间: ' + (pubTime || '-'));
  $('#viewContent').html(content);
  var html = '';
  if (attachments && attachments.length > 0) {
    html = '<strong>附件:</strong><br>';
    for (var i = 0; i < attachments.length; i++) {
      var a = attachments[i];
      var path = a.file_path ? a.file_path.replace(/\\/g, '/') : '';
      html += '<a href="/' + path + '" target="_blank" class="btn btn-sm btn-outline-info ms-2 mt-1"><i class="fas fa-paperclip"></i> ' + (a.file_name || '下载') + '</a>';
    }
  }
  $('#viewAttachments').html(html);
  bootstrap.Modal.getOrCreateInstance(document.getElementById('viewModal')).show();
  <?php if (isSupplier()): ?>
  if (id) {
    fetch('/notice', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=read&id=' + encodeURIComponent(id) + '&_csrf_token=' + encodeURIComponent(getCsrfToken())
    }).then(function(r) { return r.json(); }).then(function(res) {
      if (res.ok) {
        var cell = document.getElementById('read-' + id);
        if (cell && cell.innerHTML.indexOf('success') === -1) {
          cell.innerHTML = '<span class="status-tag success">已读</span>';
        }
      }
    }).catch(function() {});
  }
  <?php endif; ?>
}
</script>
