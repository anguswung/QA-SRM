<?php
class ApiExportController extends ApiBaseController {
    public function materials() {
        $filters = [
            'supplier_id' => ApiRequest::input('supplier_id'),
            'env_status' => ApiRequest::input('env_status'),
        ];
        $exportService = new ExportService();
        $taskId = $exportService->createTask($this->userId(), 'materials');
        try {
            $data = $exportService->exportMaterials($filters);
            if (empty($data)) {
                $exportService->updateTask($taskId, 'failed', null, null, '无数据可导出');
                ApiResponse::error('无数据可导出');
            }
            $filename = 'materials_' . date('Ymd_His') . '.xlsx';
            $result = $exportService->generateExcel($data, $filename);
            if (!$result) {
                $exportService->updateTask($taskId, 'failed', null, null, 'Excel生成失败，请安装 phpoffice/phpspreadsheet');
                ApiResponse::error('Excel生成失败，请安装 phpoffice/phpspreadsheet');
            }
            $exportService->updateTask($taskId, 'done', $result['path'], $result['size']);
            ApiResponse::success([
                'task_id' => $taskId,
                'file_path' => $result['path'],
                'file_size' => $result['size'],
                'download_url' => '/api/v1/download?path=' . urlencode($result['path']),
            ]);
        } catch (Exception $e) {
            $exportService->updateTask($taskId, 'failed', null, null, $e->getMessage());
            ApiResponse::serverError('导出失败: ' . $e->getMessage());
        }
    }

    public function compliance() {
        $materialId = ApiRequest::input('material_id');
        if (!$materialId) ApiResponse::error('缺少 material_id 参数');
        $exportService = new ExportService();
        $taskId = $exportService->createTask($this->userId(), 'compliance');
        try {
            $data = $exportService->exportComplianceReport($materialId);
            if (!$data) {
                $exportService->updateTask($taskId, 'failed', null, null, '物料不存在');
                ApiResponse::notFound('物料不存在');
            }
            $records = $data['records'];
            if (empty($records)) {
                $exportService->updateTask($taskId, 'failed', null, null, '无合规分析记录');
                ApiResponse::error('无合规分析记录');
            }
            $filename = 'compliance_' . $materialId . '_' . date('Ymd_His') . '.xlsx';
            $result = $exportService->generateExcel($records, $filename);
            if (!$result) {
                $exportService->updateTask($taskId, 'failed', null, null, 'Excel生成失败');
                ApiResponse::error('Excel生成失败');
            }
            $exportService->updateTask($taskId, 'done', $result['path'], $result['size']);
            ApiResponse::success([
                'task_id' => $taskId,
                'file_path' => $result['path'],
                'file_size' => $result['size'],
                'download_url' => '/api/v1/download?path=' . urlencode($result['path']),
            ]);
        } catch (Exception $e) {
            $exportService->updateTask($taskId, 'failed', null, null, $e->getMessage());
            ApiResponse::serverError('导出失败: ' . $e->getMessage());
        }
    }

    public function tasks() {
        $db = DB::getInstance();
        $tasks = $db->query(
            "SELECT * FROM export_tasks WHERE user_id=? ORDER BY id DESC LIMIT 50",
            [$this->userId()]
        );
        ApiResponse::success($tasks);
    }
}
