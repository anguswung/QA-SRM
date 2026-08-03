<div class="page-header">
  <h5><i class="fas fa-poll"></i> 问卷调查</h5>
  <?php if (canManage('survey.create')): ?>
  <div>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#surveyModal"><i class="fas fa-plus"></i> 创建问卷</button>
  </div>
  <?php endif; ?>
</div>
<div class="row">
  <div class="col-md-<?=isAdmin() ? 6 : 12?>">
    <div class="card">
      <div class="card-header"><i class="fas fa-list"></i> 问卷列表</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th>标题</th><th>描述</th><th>状态</th><th>截止日期</th><th>回复数</th><?php if (isAdmin()): ?><th>操作</th><?php endif; ?></tr></thead>
          <tbody>
            <?php foreach ($surveys as $s): ?>
            <tr>
              <td><?=h($s['title'])?></td>
              <td><?=mb_substr(strip_tags($s['description']), 0, 80)?></td>
              <td><?=statusBadge($s['status'])?></td>
              <td><small><?=h($s['expiration_date'] ?? '-')?></small></td>
              <td><span class="badge badge-soft-info"><?=(int)$s['response_count']?></span></td>
              <?php if (isAdmin()): ?>
              <td>
                <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editSurveyModal"
                  data-id="<?=$s['id']?>" data-title="<?=h($s['title'])?>" data-desc="<?=h($s['description'])?>"
                  data-expire="<?=h($s['expiration_date'])?>" data-status="<?=$s['status']?>"><i class="fas fa-edit"></i></button>
                <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')">
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$s['id']?>">
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
  </div>
  <?php if (isSupplier()): ?>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-reply"></i> 我的回复</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th>问卷</th><th>提交状态</th><th>提交时间</th><th>操作</th></tr></thead>
          <tbody>
            <?php foreach ($myResponses as $r): ?>
            <tr>
              <td><?=h($r['title'])?></td>
              <td><?=$r['submit_status'] === '1' ? '<span class="status-tag success">已提交</span>' : '<span class="status-tag warning">草稿</span>'?></td>
              <td><small><?=h($r['created_at'])?></small></td>
              <td>-</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><i class="fas fa-upload"></i> 提交问卷回复</div>
      <div class="card-body">
        <?php $activeSurveys = array_filter($surveys, fn($s) => $s['status'] === 'active'); ?>
        <?php if (count($activeSurveys) > 0): ?>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="submit">
          <div class="form-group">
            <label>选择问卷</label>
            <select name="survey_id" class="form-control">
              <?php foreach ($activeSurveys as $s): ?>
              <option value="<?=$s['id']?>"><?=h($s['title'])?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>上传文件</label><input type="file" name="response_file" class="form-control-file" required></div>
          <button class="btn btn-primary">提交</button>
        </form>
        <?php else: ?>
        <p class="text-muted text-center py-3">暂无活跃问卷</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php if (isAdmin()): ?>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="fas fa-reply-all"></i> 供应商回答</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th>问卷</th><th>供应商</th><th>文件</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
            <?php foreach ($responses as $r): ?>
            <tr>
              <td><small><?=h($r['title'])?></small></td>
              <td><?=h($r['supplier_name'])?></td>
              <td><?=$r['file_path'] ? '<a href="' . h(fileUrl($r['file_path'])) . '" target="_blank"><i class="fas fa-download"></i></a>' : '-'?></td>
              <td><?=$r['submit_status'] === '1' ? '<span class="status-tag success">已提交</span>' : ($r['submit_status'] === '2' ? '<span class="status-tag success">已通过</span>' : '<span class="status-tag warning">待审核</span>')?></td>
              <td>
                <button class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#reviewModal"
                  data-id="<?=$r['id']?>" data-status="2"><i class="fas fa-check"></i></button>
                <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#reviewModal"
                  data-id="<?=$r['id']?>" data-status="3"><i class="fas fa-times"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<div class="modal fade" id="surveyModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">创建问卷</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label>标题</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group">
          <label>描述</label>
          <div id="survey-editor"></div>
          <input type="hidden" name="description" id="surveyDescription">
        </div>
        <div class="form-group"><label>截止日期</label><input type="date" name="expiration_date" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" id="surveySubmitBtn">创建</button><button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<div class="modal fade" id="editSurveyModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">编辑问卷</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editSurveyId">
        <div class="form-group"><label>标题</label><input type="text" name="title" id="editSurveyTitle" class="form-control" required></div>
        <div class="form-group">
          <label>描述</label>
          <div id="edit-survey-editor"></div>
          <input type="hidden" name="description" id="editSurveyDescription">
        </div>
        <div class="form-group"><label>截止日期</label><input type="date" name="expiration_date" id="editSurveyExpire" class="form-control"></div>
        <div class="form-group"><label>状态</label>
          <select name="status" id="editSurveyStatus" class="form-control">
            <option value="active">活跃</option>
            <option value="closed">已关闭</option>
          </select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" id="editSurveySubmitBtn">保存</button><button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
<div class="modal fade" id="reviewModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">审核回复</h5><button class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="review">
        <input type="hidden" name="id" id="reviewId">
        <input type="hidden" name="submit_status" id="reviewStatus">
        <div class="form-group"><label>审核意见</label><textarea name="review_comment" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">确认</button><button class="btn btn-secondary" data-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>
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
var surveyQuill = null;
$('#surveyModal').on('shown.bs.modal', function() {
  if (typeof Quill === 'undefined') { alert('编辑器加载失败，请刷新页面重试'); return; }
  if (!surveyQuill) {
    surveyQuill = new Quill('#survey-editor', {
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
      placeholder: '请输入问卷描述...'
    });
    surveyQuill.getModule('toolbar').addHandler('image', imgHandler(surveyQuill));
  }
});
$(document).on('click', '#surveySubmitBtn', function() {
  if (surveyQuill) document.getElementById('surveyDescription').value = surveyQuill.root.innerHTML;
  document.querySelector('#surveyModal form').submit();
});
var editSurveyQuill = null;
$('#editSurveyModal').on('shown.bs.modal', function(e) {
  var btn = $(e.relatedTarget);
  $('#editSurveyId').val(btn.data('id'));
  $('#editSurveyTitle').val(btn.data('title'));
  $('#editSurveyExpire').val(btn.data('expire'));
  $('#editSurveyStatus').val(btn.data('status'));
  if (typeof Quill === 'undefined') { alert('编辑器加载失败，请刷新页面重试'); return; }
  if (!editSurveyQuill) {
    editSurveyQuill = new Quill('#edit-survey-editor', {
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
      placeholder: '请输入问卷描述...'
    });
    editSurveyQuill.getModule('toolbar').addHandler('image', imgHandler(editSurveyQuill));
  }
  editSurveyQuill.root.innerHTML = btn.data('desc') || '';
  editSurveyQuill.update();
});
$(document).on('click', '#editSurveySubmitBtn', function() {
  if (editSurveyQuill) document.getElementById('editSurveyDescription').value = editSurveyQuill.root.innerHTML;
  document.querySelector('#editSurveyModal form').submit();
});
});
$('#reviewModal').on('show.bs.modal', function(e){
  var btn = $(e.relatedTarget);
  $(this).find('#reviewId').val(btn.data('id'));
  $(this).find('#reviewStatus').val(btn.data('status'));
});
</script>
