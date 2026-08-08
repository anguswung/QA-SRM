<div class="page-header">
  <h4><i class="fas fa-poll"></i> 问卷调查</h4>
  <?php if (canManage('survey.create')): ?>
  <div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#surveyModal"><i class="fas fa-plus"></i> 创建问卷</button>
  </div>
  <?php endif; ?>
</div>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="fas fa-list"></i> 问卷列表</div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0" data-server="1" data-url="/survey?action=data">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:18%">标题</th><th>描述</th><th style="width:9%">状态</th><th style="width:12%">截止日期</th><th style="width:9%">回复/题数</th><th style="width:10%">操作</th></tr></thead>
          <tbody>
            <tr><td colspan="7" class="text-center text-muted py-4">加载中...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
  <?php if (isSupplier()): ?>
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="fas fa-reply"></i> 我的回复</div>
      <div class="card-body p-0">
        <table class="table table-fixed table-hover">
          <thead><tr><th class="col-id">ID</th><th>问卷</th><th style="width:13%">提交状态</th><th style="width:18%">提交时间</th><th style="width:12%">操作</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($myResponses as $r): ?>
            <tr>
              <td class="col-id"><?=h($r['id'] ?? '')?></td><td class="text-truncate"><?=h($r['title'])?></td>
              <td><?=$r['submit_status'] === '1' ? '<span class="status-tag success">已提交</span>' : '<span class="status-tag warning">草稿</span>'?></td>
              <td><small><?=h($r['created_at'])?></small></td>
              <td><button class="btn-icon btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailModal" data-id="<?=(int)$r['id']?>"><i class="fas fa-eye"></i></button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
  <?php $activeSurveys = array_values(array_filter($surveys, fn($s) => $s['status'] === 'active')); ?>
  <?php if (count($activeSurveys) > 0): ?>
  <!-- 答卷弹窗：点击问卷列表的"填写"按钮打开 -->
  <div class="modal fade" id="replyModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">提交问卷回复</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
          <div class="modal-body">
            <?php foreach ($activeSurveys as $i => $s): ?>
            <div class="sq-answer-block <?=($i===0?'':'d-none')?>" data-survey="<?=$s['id']?>">
              <form method="post" enctype="multipart/form-data" class="sq-reply-form" data-survey-id="<?=$s['id']?>"><?=csrf_field()?>
                <input type="hidden" name="action" value="submit">
                <input type="hidden" name="survey_id" value="<?=$s['id']?>">
                <h4 class="mb-3"><?=h($s['title'])?></h4>
                <?php $sqs = $questionsBySurvey[$s['id']] ?? []; ?>
                <?php if ($sqs): ?>
                  <?php foreach ($sqs as $q):
                    $opts = in_array($q['question_type'], ['radio','checkbox','select']) ? (json_decode((string)$q['options'], true) ?: []) : [];
                    $defaultAnswer = in_array($q['question_type'], ['text','textarea']) ? ((string)$q['options']) : '';
                    $reqAttr = (int)$q['is_required'] ? 'required' : '';
                    $reqTag = (int)$q['is_required'] ? ' <span class="text-danger">*</span>' : '';
                    $typeTag = '';
                    if ($q['question_type'] === 'radio') $typeTag = ' <span class="badge bg-light text-dark border">单选</span>';
                    elseif ($q['question_type'] === 'checkbox') $typeTag = ' <span class="badge bg-light text-dark border">多选</span>';
                    $base = 'answer[' . (int)$q['id'] . ']';
                  ?>
                  <div class="mb-3">
                    <label class="form-label fw-semibold"><?=h($q['question_title'])?><?=$typeTag?><?=$reqTag?></label>
                    <?php if ($q['question_type'] === 'text'): ?>
                      <input type="text" name="<?=$base?>" class="form-control" value="<?=h($defaultAnswer)?>" <?=$reqAttr?>>
                    <?php elseif ($q['question_type'] === 'textarea'): ?>
                      <textarea name="<?=$base?>" class="form-control" rows="3" <?=$reqAttr?>><?=h($defaultAnswer)?></textarea>
                    <?php elseif ($q['question_type'] === 'select'): ?>
                      <select name="<?=$base?>" class="form-control" <?=$reqAttr?>>
                        <option value="">请选择</option>
                        <?php foreach ($opts as $o): ?><option value="<?=h($o)?>"><?=h($o)?></option><?php endforeach; ?>
                      </select>
                    <?php elseif ($q['question_type'] === 'radio'): ?>
                      <?php foreach ($opts as $o): ?>
                      <div class="form-check"><input class="form-check-input" type="radio" name="<?=$base?>" value="<?=h($o)?>" <?=$reqAttr?>><label class="form-check-label"><?=h($o)?></label></div>
                      <?php endforeach; ?>
                    <?php elseif ($q['question_type'] === 'checkbox'): ?>
                      <?php $firstOpt = true; ?>
                      <?php foreach ($opts as $o): ?>
                      <div class="form-check"><input class="form-check-input" type="checkbox" name="<?=$base?>[]" value="<?=h($o)?>" <?=$firstOpt ? $reqAttr : ''?>><label class="form-check-label"><?=h($o)?></label></div>
                      <?php $firstOpt = false; ?>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-muted">该问卷无结构化问题，可直接上传文件提交。</p>
                <?php endif; ?>
                <div class="mb-3"><label>上传附件（可选）</label><input type="file" name="response_file" class="form-control"></div>
                <div class="d-flex justify-content-end gap-2 pb-4">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                  <button type="submit" class="btn btn-primary">提交</button>
                </div>
              </form>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <?php endif; ?>
  <?php if (isAdmin()): ?>
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="fas fa-reply-all"></i> 供应商回答</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:16%">问卷</th><th style="width:12%">供应商</th><th style="width:9%">文件</th><th style="width:9%">状态</th><th style="width:16%">操作</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($responses as $r): ?>
            <tr>
              <td class="col-id"><?=h($r['id'] ?? '')?></td><td><small><?=h($r['title'])?></small></td>
              <td><?=h($r['supplier_name'])?></td>
              <td><?=$r['file_path'] ? '<a href="' . h(fileUrl($r['file_path'])) . '" target="_blank"><i class="fas fa-download"></i></a>' : '-'?></td>
              <td><?=$r['submit_status'] === '1' ? '<span class="status-tag success">已提交</span>' : ($r['submit_status'] === '2' ? '<span class="status-tag success">已通过</span>' : '<span class="status-tag warning">待审核</span>')?></td>
              <td>
                <button class="btn-icon btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailModal" data-id="<?=$r['id']?>"><i class="fas fa-eye"></i></button>
                <button class="btn-icon btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#reviewModal" data-id="<?=$r['id']?>" data-status="2"><i class="fas fa-check"></i></button>
                <button class="btn-icon btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reviewModal" data-id="<?=$r['id']?>" data-status="3"><i class="fas fa-times"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
  <?php endif; ?>

<!-- 创建问卷弹窗 -->
<div class="modal fade" id="surveyModal">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content" id="surveyCreateForm"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">创建问卷</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="mb-3"><label>标题</label><input type="text" name="title" class="form-control" required></div>
        <div class="mb-3"><label>描述</label><div id="survey-editor"></div><input type="hidden" name="description" id="surveyDescription"></div>
        <div class="mb-3"><label>截止日期</label><input type="date" name="expiration_date" class="form-control"></div>
        <label class="fw-semibold">问题设置</label>
        <div id="sqCreateList"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="sqAdd('sqCreateList')"><i class="fas fa-plus"></i> 添加问题</button>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" onclick="sqCreateSubmit()">创建</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>

<!-- 编辑问卷弹窗 -->
<div class="modal fade" id="editSurveyModal">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content" id="surveyEditForm"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">编辑问卷</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editSurveyId">
        <div class="mb-3"><label>标题</label><input type="text" name="title" id="editSurveyTitle" class="form-control" required></div>
        <div class="mb-3"><label>描述</label><div id="edit-survey-editor"></div><input type="hidden" name="description" id="editSurveyDescription"></div>
        <div class="mb-3"><label>截止日期</label><input type="date" name="expiration_date" id="editSurveyExpire" class="form-control"></div>
        <div class="mb-3"><label>状态</label>
          <select name="status" id="editSurveyStatus" class="form-control">
            <option value="active">活跃</option>
            <option value="closed">已关闭</option>
          </select>
        </div>
        <label class="fw-semibold">问题设置 <small class="text-muted">（保存后将覆盖原有问题）</small></label>
        <div id="sqEditList"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="sqAdd('sqEditList')"><i class="fas fa-plus"></i> 添加问题</button>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" onclick="sqEditSubmit()">保存</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>

<!-- 审核弹窗 -->
<div class="modal fade" id="reviewModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">审核回复</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="review">
        <input type="hidden" name="id" id="reviewId">
        <input type="hidden" name="submit_status" id="reviewStatus">
        <div class="mb-3"><label>审核意见</label><textarea name="review_comment" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">确认</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>

<!-- 回复明细弹窗 -->
<div class="modal fade" id="detailModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">回复明细</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body" id="detailBody">
        <p class="text-muted text-center py-4">加载中...</p>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button></div>
    </div>
  </div>
</div>

<script>
var SQ_TYPES = {'text':'单行文本','textarea':'多行文本','radio':'单选','checkbox':'多选','select':'下拉选择'};
function isChoiceType(t){ return ['radio','checkbox','select'].indexOf(t) >= 0; }

function sqRowHtml(title, type, required, optionsText) {
  type = type || 'text';
  var isChoice = isChoiceType(type);
  var optLabel = isChoice ? '选项，每行一个（单选/多选/下拉填写）' : '默认答案/参考答案（可选，供应商填写时预填）';
  var reqChk = required ? ' checked' : '';
  return '<div class="card mb-2 border sq-row">' +
    '<div class="card-body py-2">' +
      '<div class="row g-2 align-items-center">' +
        '<div class="col-2"><select class="form-select form-select-sm sq-type" onchange="sqTypeChange(this)">' +
          Object.keys(SQ_TYPES).map(function(k){ return '<option value="'+k+'"'+(type===k?' selected':'')+'>'+SQ_TYPES[k]+'</option>'; }).join('') +
        '</select></div>' +
        '<div class="col"><input type="text" class="form-control form-control-sm sq-title" placeholder="问题标题" value="'+(title||'')+'"></div>' +
        '<div class="col-auto"><div class="form-check"><input type="checkbox" class="form-check-input sq-required"'+reqChk+'><label class="form-check-label">必填</label></div></div>' +
        '<div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger" onclick="sqRemove(this)"><i class="fas fa-times"></i></button></div>' +
      '</div>' +
      '<div class="mt-1 small text-muted sq-opt-label">' + optLabel + '</div>' +
      '<div class="sq-options">' +
        '<textarea class="form-control form-control-sm sq-options-input" rows="2" placeholder="' + optLabel + '">'+(optionsText||'')+'</textarea>' +
      '</div>' +
    '</div>' +
  '</div>';
}
function sqTypeChange(sel){
  var row = sel.closest('.sq-row');
  var isChoice = isChoiceType(sel.value);
  row.querySelector('.sq-opt-label').textContent = isChoice ? '选项，每行一个（单选/多选/下拉填写）' : '默认答案/参考答案（可选，供应商填写时预填）';
  row.querySelector('.sq-options-input').setAttribute('placeholder', isChoice ? '选项，每行一个（单选/多选/下拉填写）' : '默认答案/参考答案（可选，供应商填写时预填）');
}
function sqElFromHtml(html){ var d = document.createElement('div'); d.innerHTML = html; return d.firstElementChild; }
function sqAdd(listId){
  document.getElementById(listId).appendChild(sqElFromHtml(sqRowHtml('', 'text', false, '')));
}
function sqRemove(el){ el.closest('.sq-row').remove(); }

function serializeQuestions(listId){
  var list = document.getElementById(listId);
  list.querySelectorAll('.sq-row').forEach(function(row){
    [['question_title[]', row.querySelector('.sq-title').value],
     ['question_type[]', row.querySelector('.sq-type').value],
     ['question_required[]', row.querySelector('.sq-required').checked ? 1 : 0],
     ['question_options[]', row.querySelector('.sq-options-input').value || '']].forEach(function(p){
      var inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = p[0]; inp.value = p[1];
      list.appendChild(inp);
    });
  });
}

var surveyQuill = null;
var editSurveyQuill = null;

function initCreateQuill(){
  if (surveyQuill || typeof Quill === 'undefined') return;
  surveyQuill = new Quill('#survey-editor', {
    theme: 'snow',
    modules: { toolbar: [[{'header':[1,2,3,false]}],['bold','italic','underline','strike'],[{'color':[]},{'background':[]}],[{'size':['small',false,'large','huge']}],['link','image'],['clean']] },
    placeholder: '请输入问卷描述...'
  });
  surveyQuill.getModule('toolbar').addHandler('image', quillImgHandler(surveyQuill));
}
function initEditQuill(){
  if (editSurveyQuill || typeof Quill === 'undefined') return;
  editSurveyQuill = new Quill('#edit-survey-editor', {
    theme: 'snow',
    modules: { toolbar: [[{'header':[1,2,3,false]}],['bold','italic','underline','strike'],[{'color':[]},{'background':[]}],[{'size':['small',false,'large','huge']}],['link','image'],['clean']] },
    placeholder: '请输入问卷描述...'
  });
  editSurveyQuill.getModule('toolbar').addHandler('image', quillImgHandler(editSurveyQuill));
}
function quillImgHandler(quill){
  return function(){
    var input = document.createElement('input');
    input.type = 'file'; input.accept = 'image/*'; input.click();
    input.onchange = function(){
      var file = input.files[0]; if (!file) return;
      var fd = new FormData(); fd.append('file', file); fd.append('_csrf_token', getCsrfToken());
      fetch('/upload-image.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(res){
          if (res.code === 0 && res.data && res.data.url) {
            var range = quill.getSelection(true);
            quill.insertEmbed(range.index, 'image', res.data.url);
          } else { alert('图片上传失败: ' + (res.msg || '未知错误')); }
        }).catch(function(){ alert('图片上传失败'); });
    };
  };
}

function sqCreateSubmit(){
  if (surveyQuill) document.getElementById('surveyDescription').value = surveyQuill.root.innerHTML;
  serializeQuestions('sqCreateList');
  document.getElementById('surveyCreateForm').submit();
}
function sqEditSubmit(){
  if (editSurveyQuill) document.getElementById('editSurveyDescription').value = editSurveyQuill.root.innerHTML;
  serializeQuestions('sqEditList');
  document.getElementById('surveyEditForm').submit();
}

$(function(){
  // 创建弹窗：清空问题列表并初始化编辑器
  $('#surveyModal').on('shown.bs.modal', function(){ $('#sqCreateList').empty(); initCreateQuill(); });

  // 编辑弹窗：回填字段/描述/问题
  $('#editSurveyModal').on('shown.bs.modal', function(e){
    var btn = $(e.relatedTarget);
    $('#editSurveyId').val(btn.data('id'));
    $('#editSurveyTitle').val(btn.data('title'));
    $('#editSurveyExpire').val(btn.data('expire'));
    $('#editSurveyStatus').val(btn.data('status'));
    $('#sqEditList').empty();
    initEditQuill();
    if (editSurveyQuill) { editSurveyQuill.root.innerHTML = btn.data('desc') || ''; editSurveyQuill.update(); }
    $.get('/survey?action=questions&id=' + btn.data('id'), function(rows){
      rows.forEach(function(q){
        var isChoice = ['radio','checkbox','select'].indexOf(q.question_type) >= 0;
        var optsText = isChoice ? (q.options ? (JSON.parse(q.options) || []).join('\n') : '') : (q.options || '');
        document.getElementById('sqEditList').appendChild(sqElFromHtml(sqRowHtml(q.question_title, q.question_type, q.is_required == 1, optsText)));
      });
    });
  });

  // 审核弹窗
  $('#reviewModal').on('show.bs.modal', function(e){
    var btn = $(e.relatedTarget);
    $(this).find('#reviewId').val(btn.data('id'));
    $(this).find('#reviewStatus').val(btn.data('status'));
  });

  // 回复明细弹窗
  var detailReqSeq = 0;
  $('#detailModal').on('show.bs.modal', function(e){
    var id = $(e.relatedTarget).data('id');
    var seq = ++detailReqSeq;
    var $body = $('#detailBody');
    $body.html('<p class="text-muted text-center py-4">加载中...</p>');
    $.get('/survey?action=detail&id=' + id, function(d){
      if (seq !== detailReqSeq) return;
      var html = '<p><strong>' + d.resp.title + '</strong> <span class="text-muted">(' + d.resp.supplier_name + ')</span></p>';
      if (d.resp.file_url) html += '<p class="mb-2"><a href="' + d.resp.file_url + '" target="_blank"><i class="fas fa-download"></i> 附件</a></p>';
      html += '<table class="table table-bordered table-sm mb-0"><thead><tr><th style="width:34%">问题</th><th>答案</th></tr></thead><tbody>';
      if (d.answers && d.answers.length) d.answers.forEach(function(a){ html += '<tr><td>' + a.question_title + '</td><td>' + (a.answer_value || '<span class="text-muted">未填写</span>') + '</td></tr>'; });
      else html += '<tr><td colspan="2" class="text-muted text-center">无逐题答案</td></tr>';
      html += '</tbody></table>';
      $body.html(html);
    });
  });

});

// 打开答卷弹窗，显示对应问卷的答题表单
var replyModalInstance = null;
function openReply(id){
  $('.sq-answer-block').each(function(){
    $(this).toggleClass('d-none', $(this).data('survey') != id);
  });
  var el = document.getElementById('replyModal');
  if (window.bootstrap && window.bootstrap.Modal) {
    if (!replyModalInstance) replyModalInstance = new bootstrap.Modal(el);
    replyModalInstance.show();
  } else {
    $(el).modal('show');
  }
  return false;
}
</script>