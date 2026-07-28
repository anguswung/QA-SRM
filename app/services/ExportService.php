<?php
class ExportService {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance();
    }

    public function createTask($userId, $type) {
        $this->db->execute(
            "INSERT INTO export_tasks (user_id, type, status, created_at) VALUES (?,?, 'pending', datetime('now','localtime'))",
            [$userId, $type]
        );
        return $this->db->lastInsertId();
    }

    public function updateTask($taskId, $status, $filePath = null, $fileSize = null, $errorMsg = null) {
        $this->db->execute(
            "UPDATE export_tasks SET status=?, file_path=?, file_size=?, error_msg=?, completed_at=datetime('now','localtime') WHERE id=?",
            [$status, $filePath, $fileSize, $errorMsg, $taskId]
        );
    }

    public function exportMaterials($filters = []) {
        $sql = "SELECT m.*, s.name as supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id WHERE 1=1";
        $params = [];
        if (!empty($filters['supplier_id'])) {
            $sql .= " AND m.supplier_id=?";
            $params[] = $filters['supplier_id'];
        }
        if (!empty($filters['env_status'])) {
            $sql .= " AND m.env_status=?";
            $params[] = $filters['env_status'];
        }
        $sql .= " ORDER BY m.id DESC";
        return $this->db->query($sql, $params);
    }

    public function exportComplianceReport($materialId) {
        $material = $this->db->single("SELECT * FROM material WHERE id=?", [$materialId], true);
        if (!$material) return null;
        $records = $this->db->query(
            "SELECT ar.*, rl.name as regulation_name FROM analysis_record ar LEFT JOIN regulation_lib rl ON ar.regulation_id=rl.id WHERE ar.material_id=?",
            [$materialId]
        );
        return ['material' => $material, 'records' => $records];
    }

    public function generateExcel($data, $filename) {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            return null;
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if (!empty($data) && is_array($data)) {
            $headers = array_keys($data[0]);
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }
            foreach ($data as $row => $item) {
                foreach ($item as $col => $value) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $row + 2, $value);
                }
            }
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $uploadPath = (require __DIR__ . '/../../config/app.php')['upload']['path'];
        $dir = $uploadPath . '/exports';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filePath = $dir . '/' . $filename;
        $writer->save($filePath);
        return ['path' => $filePath, 'size' => filesize($filePath)];
    }
}
