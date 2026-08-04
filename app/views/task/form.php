<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><i class="fas fa-plus"></i> 下发任务</div>
      <div class="card-body">
        <form method="post"><?=csrf_field()?>
          <input type="hidden" name="action" value="create">
          <div class="form-group"><label>任务标题 <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required placeholder="如：2026年3月RoHS资料补充">
          </div>
          <div class="row">
            <div class="col-6">
              <div class="form-group"><label>供应商<span class="text-danger">*</span></label>
                <select name="supplier_id" class="form-control" required>
                  <option value="">选择供应商</option>
                  <?php foreach ($suppliers as $s): ?>
                  <option value="<?=$s['id']?>"><?=h($s['code'])?> - <?=h($s['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-6">
              <div class="form-group"><label>流程模板 <span class="text-danger">*</span></label>
                <select name="template_id" class="form-control" required>
                  <option value="">选择模板</option>
                  <?php foreach ($templates as $t): ?>
                  <option value="<?=$t['id']?>"><?=h($t['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-4">
              <div class="form-group"><label>优先级</label>
                <select name="priority" class="form-control">
                  <option value="0">普通</option>
                  <option value="1">紧急</option>
                  <option value="2">高优先</option>
                </select>
              </div>
            </div>
            <div class="col-4">
              <div class="form-group"><label>截止日期</label>
                <input type="date" name="due_date" class="form-control">
              </div>
            </div>
          </div>
          <div class="form-group"><label>任务说明</label>
            <textarea name="description" class="form-control" rows="3" placeholder="详细描述任务要求，供应商可见"></textarea>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> 下发</button>
          <a href="/task" class="btn btn-secondary">取消</a>
        </form>
      </div>
    </div>
  </div>
</div>