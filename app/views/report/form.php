<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><i class="fas fa-edit"></i> 编辑报告</div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data"><?=csrf_field()?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?=$item['id']?>">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3"><label>报告编号</label><input type="text" name="report_no" class="form-control" value="<?=h($item['report_no'])?>" readonly></div>
            </div>
            <div class="col-md-6">
              <div class="mb-3"><label>物料</label>
                <select name="material_id" class="form-control">
                  <option value="">无</option>
                  <?php foreach ($materials as $m): ?>
                  <option value="<?=$m['id']?>" <?=$item['material_id'] == $m['id'] ? 'selected' : ''?>><?=h($m['material_no'])?> - <?=h($m['material_name'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3"><label>报告类型</label>
                <select name="report_type" class="form-control">
                  <option value="RoHS" <?=$item['report_type']==='RoHS'?'selected':''?>>RoHS</option>
                  <option value="REACH" <?=$item['report_type']==='REACH'?'selected':''?>>REACH</option>
                  <option value="HF" <?=$item['report_type']==='HF'?'selected':''?>>HF</option>
                  <option value="MSDS" <?=$item['report_type']==='MSDS'?'selected':''?>>MSDS</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label>检测机构</label><input type="text" name="test_agency" class="form-control" value="<?=h($item['test_agency'])?>"></div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label>检测日期</label><input type="date" name="test_date" class="form-control" value="<?=h($item['test_date'])?>"></div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3"><label>生效日期</label><input type="date" name="effective_date" class="form-control" value="<?=h($item['effective_date'])?>"></div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label>有效期</label><input type="date" name="expiry_date" class="form-control" value="<?=h($item['expiry_date'])?>"></div>
            </div>
            <div class="col-md-4">
              <div class="mb-3"><label>版本</label><input type="text" name="report_version" class="form-control" value="<?=h($item['report_version'])?>"></div>
            </div>
          </div>
          <div class="mb-3"><label>替换文件</label><input type="file" name="report_file" class="form-control" accept=".pdf"></div>
          <button type="submit" class="btn btn-primary">保存</button>
          <a href="/report" class="btn btn-secondary">取消</a>
        </form>
      </div>
    </div>
  </div>
</div>
