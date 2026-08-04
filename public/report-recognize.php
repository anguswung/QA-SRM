<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers.php';
$config = require __DIR__ . '/../config/app.php';

spl_autoload_register(function ($class) {
    foreach (['controllers','models'] as $dir) {
        $file = __DIR__ . "/../app/$dir/$class.php";
        if (file_exists($file)) { require $file; return; }
    }
});

session_start();
if (!isset($_SESSION['user_id'])) { header('Content-Type: application/json'); echo json_encode(['error' => '未登录']); exit; }
if (($_POST['_csrf_token'] ?? '') === '' || empty($_SESSION['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'], $_POST['_csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'CSRF token validation failed']);
    exit;
}

$result = ['success' => false, 'message' => '', 'data' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file']['tmp_name'])) {
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext === 'pdf') {
        // Try poppler pdftotext first, then smalot fallback, then Tesseract OCR
        $text = '';
        $pdftotext = $config['poppler']['pdftotext'] ?? 'pdftotext';
        if (file_exists($pdftotext)) {
            $tmpFile = $file['tmp_name'];
            $outputFile = $tmpFile . '.txt';
            $cmd = escapeshellcmd($pdftotext) . ' ' . escapeshellarg($tmpFile) . ' ' . escapeshellarg($outputFile);
            exec($cmd . ' 2>&1', $output, $exitCode);
            if ($exitCode === 0 && file_exists($outputFile)) {
                $text = file_get_contents($outputFile);
                @unlink($outputFile);
            }
        }

        if (empty($text)) {
            // Fallback to smalot/pdfparser
            try {
                require_once __DIR__ . '/../vendor/autoload.php';
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file['tmp_name']);
                $text = $pdf->getText();
            } catch (Exception $e) {
                // Ignore, will try Tesseract next
            }
        }

        if (empty($text)) {
            // Fallback to Tesseract OCR (for scanned PDFs)
            $tesseract = $config['poppler']['tesseract'] ?? '';
            if ($tesseract && file_exists($tesseract)) {
                $tmpDir = sys_get_temp_dir() . '/tesseract_' . md5($file['name']);
                if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
                // Convert PDF to images using poppler pdftoppm
                $pdftoppm = $config['poppler']['pdftoppm'] ?? '';
                if ($pdftoppm && file_exists($pdftoppm)) {
                    $cmd = '"' . $pdftoppm . '" -png -r 300 "' . $file['tmp_name'] . '" "' . $tmpDir . '/page"';
                    exec($cmd . ' 2>&1', $output, $exitCode);
                    if ($exitCode === 0) {
                        $images = glob($tmpDir . '/page*.png');
                        sort($images);
                        foreach ($images as $img) {
                            $txtFile = $img . '.txt';
                            $cmd = '"' . $tesseract . '" "' . $img . '" "' . substr($txtFile, 0, -4) . '" -l chi_sim+eng';
                            exec($cmd . ' 2>&1', $output, $exitCode);
                            if ($exitCode === 0 && file_exists($txtFile)) {
                                $text .= file_get_contents($txtFile) . "\n";
                                @unlink($txtFile);
                            }
                            @unlink($img);
                        }
                    }
                }
                // Cleanup
                @rmdir($tmpDir);
            }
        }

        if (!empty($text)) {
            // Parse report data from text
            $data = parseReportText($text, $file['name']);
            $result['success'] = true;
            $result['data'] = $data;
            $result['message'] = '识别成功';
        } else {
            $result['message'] = '未能从PDF中提取文字内容';
        }
    } elseif (in_array($ext, ['xls', 'xlsx'])) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $reader = $ext === 'xlsx' ? new \PhpOffice\PhpSpreadsheet\Reader\Xlsx() : new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            $spreadsheet = $reader->load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $data = parseExcelReport($rows, $file['name']);
            $result['success'] = true;
            $result['data'] = $data;
            $result['message'] = '识别成功';
        } catch (Exception $e) {
            $result['message'] = 'Excel解析失败: ' . $e->getMessage();
        }
    } else {
        $result['message'] = '不支持的文件格式: ' . $ext;
    }
} else {
    $result['message'] = '请上传文件';
}

header('Content-Type: application/json');
echo json_encode($result);

function parseReportText($text, $filename) {
    $data = [
        'report_no' => '',
        'test_date' => '',
        'test_agency' => '',
        'expiry_date' => '',
        'elements' => [],
    ];

    $text = preg_replace('/[\r\n]+/', "\n", $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);

    // Detect garbage text (PDF commands vs readable text)
    $isGarbage = strlen($text) > 100 && preg_match('/\b(cm|q|BT|ET|Tm|Td|TJ|Tf|Do|gs|w)\b/i', substr($text, 0, 500));

    // === REPORT NO (sequential checks, no short-circuit) ===
    $rn = '';
    if (!$isGarbage) {
        // Same line first: "Report No. A2240815857101004"
        if (preg_match('/Report\s*No\.\s+([A-Z]\w{5,50})/i', $text, $m)) { $rn = trim($m[1]); }
        // CTI Chinese: "报告编号\nReport No.\n\n编号值"  
        if (!$rn && preg_match('/报告编号\s*Report\s*No\.[^\n]*\n\s*([A-Z]\w{5,50})/i', $text, $m)) { $rn = trim($m[1]); }
        // Number on NEXT line after "Report No." (not same line)
        if (!$rn && preg_match('/Report\s*No\.[^\n]*\n\s*([A-Z]\w{5,50})/i', $text, $m)) { $rn = trim($m[1]); }
        // SGS: "No. : CE/2014/94382" or "编号(No.) : CE/2014/B2852"
        if (!$rn && preg_match('/(?:No\.|编号)\s*[:：]?\s*(CE\/\d{4}\/\w+)/i', $text, $m)) { $rn = trim($m[1]); }
        // Chinese "编号：" format (SGS/CTI Chinese reports)
        if (!$rn && preg_match('/^(?:编号|报告编号)\s*[:：]+\s*([A-Z][A-Z0-9\/\-]{7,40})/im', $text, $m)) { $rn = trim($m[1]); }
        // General "No. CANEC2301145801"
        if (!$rn && preg_match('/No\.\s+([A-Z]\w{5,50})/i', $text, $m)) { $rn = trim($m[1]); }
        // Long code: A2230060852101014 (must not be partial - prefer longer match)
        if (!$rn && preg_match('/[A-Z][A-Z0-9]{8,30}/', $text, $m)) { $rn = $m[0]; }
    }
    // Fallback: extract from filename
    if (!$rn) {
        // Try to find report number in filename: ET R24 C06410 M10 or similar
        if (preg_match('/[A-Z]{2,6}\d{2,6}[-_][A-Z\d]{3,15}[-_]\d{3,10}/', $filename, $m)) { $rn = $m[0]; }
        elseif (preg_match('/[A-Z]{2,6}\d{2,4}[A-Z]\d{5,10}\w*/', $filename, $m)) { $rn = $m[0]; }
        elseif (preg_match('/(ETR\w+|CE\d+\w+)/i', $filename, $m)) { $rn = $m[1]; }
    }
    if ($rn) $data['report_no'] = $rn;

    // Extract test date: collect ALL dates near date labels, pick the LATEST (test completion / issue date)
    $td = '';
    $monthMap = ['jan'=>'01','feb'=>'02','mar'=>'03','apr'=>'04','may'=>'05','jun'=>'06','jul'=>'07','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12'];
    $allFound = [];
    if (!$isGarbage) {
        $lines = preg_split('/\r?\n/', $text);
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            $hasLabel = (bool)preg_match('/(?:批准[日期]?|签发[日期]?|Approved by|Issue Date|检测日期|测试日期|Test Date|测试周期|Testing Period|Date\s*[:：]|日期\s*[:：]|样品检测日期|样品接收日期|收样日期|Sample Received|Receive Date)/i', $line);
            if (!$hasLabel) continue;
            for ($j = $i; $j <= min($i + 3, count($lines) - 1); $j++) {
                $l = trim($lines[$j]);
                // "Dec. 26, 2024" / "Jan 8, 2025" - use preg_match_all to catch multiple dates on one line
                if (preg_match_all('/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.aeiou]*\s+(\d{1,2})[,\s]*(\d{4})/i', $l, $m)) {
                    for ($k = 0; $k < count($m[0]); $k++) {
                        $mn = $monthMap[strtolower(substr($m[1][$k],0,3))] ?? '01';
                        $y = (int)$m[3][$k]; $mo = (int)$mn; $d = (int)$m[2][$k];
                        if ($y >= 2000 && $y <= 2030 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
                            $allFound[] = sprintf('%04d-%02d-%02d', $y, $mo, $d);
                        }
                    }
                }
                // "2024.12.26" / "2024-12-26"
                if (preg_match_all('/(\d{4})[.\-\/年](\d{1,2})[.\-\/月](\d{1,2})/', $l, $m)) {
                    for ($k = 0; $k < count($m[0]); $k++) {
                        $y = (int)$m[1][$k]; $mo = (int)$m[2][$k]; $d = (int)$m[3][$k];
                        if ($y >= 2000 && $y <= 2030 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
                            $allFound[] = sprintf('%04d-%02d-%02d', $y, $mo, $d);
                        }
                    }
                }
            }
            // Also try "Testing Period" line which may contain a range like "Dec. 26, 2024 to Jan. 8, 2025"
            if (preg_match('/Testing Period|测试周期/i', $line)) {
                if (preg_match_all('/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.aeiou]*\s+(\d{1,2})[,\s]*(\d{4})/i', $lines[$i], $m2)) {
                    for ($k = 0; $k < count($m2[0]); $k++) {
                        $mn = $monthMap[strtolower(substr($m2[1][$k],0,3))] ?? '01';
                        $y = (int)$m2[3][$k]; $mo = (int)$mn; $d = (int)$m2[2][$k];
                        if ($y >= 2000 && $y <= 2030 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
                            $allFound[] = sprintf('%04d-%02d-%02d', $y, $mo, $d);
                        }
                    }
                }
            }
        }
        // Also scan for "Date" near "Approved by" / "绛惧彂" signature block (last page)
        $sigBlock = substr($text, -3000);
        if (preg_match('/Approved by[^\n]*\n\s*([^\n]*)\n\s*Date[^\n]*\n\s*(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[.aeiou]*\s+(\d{1,2})[,\s]*(\d{4})/i', $sigBlock, $m)) {
            $mn = $monthMap[strtolower(substr($m[2],0,3))] ?? '01';
            $y = (int)$m[4]; $mo = (int)$mn; $d = (int)$m[3];
            if ($y >= 2000 && $y <= 2030 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
                $allFound[] = sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
        }
    }
    // Pick the LATEST date from all found
    if (!empty($allFound)) {
        sort($allFound);
        $td = end($allFound);
    }
    if (!$td && preg_match('/(\d{4})[\.\-\/](\d{1,2})[\.\-\/](\d{1,2})/', $filename, $m)) {
        $y = (int)$m[1]; $mo = (int)$m[2]; $d = (int)$m[3];
        if ($y >= 2000 && $y <= 2030 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
            $td = sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
    }
    if ($td) $data['test_date'] = $td;

    // Extract test agency
    $ta = '';
    // Chinese labs (CTI/华测/SGS etc.)
    if (preg_match_all('/([^\n]{2,30}(?:检测|测试|实验|检验认证|中心|研究院|研究所)[^\n]{0,30}(?:公司|集团|有限公司|中心))/i', $text, $m)) {
        foreach ($m[1] as $c) { $v = preg_replace('/\s+/', '', $c); if (mb_strlen($v) >= 8) { $ta = $c; break; } }
    }
    // SGS: extract company name from "Test Report" header area
    if (!$ta && preg_match('/Test Report[.\s]*\n[.\s]*No\.[^\n]*\n[.\s]*Date[^\n]*\n[.\s]*([A-Z][A-Z\s&\.]+(?:CORPORATION|INC|LTD|CO|GROUP|COMPANY))/i', $text, $m)) {
        $ta = trim($m[1]);
    }
    // SGS simplified or other English labs (exclude "工作编号" which is a job reference, not the agency)
    if (!$ta && preg_match('/(?:\bSGS(?:[-\s][A-Z][A-Z\s.]+)?(?:Co\.[,\s]*Ltd\.|Inc\.|Group|Corporation|LLC|SA|Limited)|\bSGS[-\s]?CSTC|Centre Testing International|TUV|Intertek|Bureau Veritas|Eurofins|UL\s+LLC)[^\n]{0,60}(?:Co\.[,\s]*Ltd\.|Inc\.|Group|Corporation|LLC)?/i', $text, $m)) {
        $ta = trim($m[0]);
    }
    // Chinese SGS entity names (通标标准技术服务有限公司)
    if (!$ta && preg_match('/通标标准技术[^\n]{0,40}(?:有限公司|分公司)/i', $text, $m)) {
        $ta = trim($m[0]);
    }
    // CTI (全称或简称、检测等关键词)
    if (!$ta && preg_match('/Centre Testing[^\n]{0,40}(?:Co\.|Ltd\.|Group)/i', $text, $m)) {
        $ta = trim($m[0]);
    }
    // "批准" signature block
    if (!$ta && preg_match_all('/批准[^\n]*\n[^\n]*\n([^\n]{4,50})/i', $text, $m)) {
        foreach ($m[1] as $c) { $v = trim($c); if (mb_strlen($v) >= 6) { $ta = $v; break; } }
    }
    if ($ta) $data['test_agency'] = $ta;

    // Extract elements from test result tables
    // Look for patterns like "Lead (Pb) N.D. 2 mg/kg" or "閾?Lead (Pb) N.D. 2 mg/kg"
    $tableRegex = '/(?:铅|镉|汞|六价铬|PBB|PBDE|DBP|BBP|DEHP|DIBP|锑|砷|铍|氟|氯|溴)\s*[\(（]?[A-Za-z0-9+（）\s]+[\)）]?[^\n]*?(N\.D\.|[0-9.]+)\s*(mg\/kg|ppm|%|mg\/L|ug\/kg|ug\/cm2)?/i';
    if (preg_match_all($tableRegex, $text, $matches, PREG_SET_ORDER)) {
        $nameMap = ['鉛'=>'Pb', '鎘'=>'Cd', '汞'=>'Hg', '六价鉻'=>'Cr6+', '銻'=>'Sb', '砷'=>'As', '鈹'=>'Be', '氟'=>'F', '氯'=>'Cl', '溴'=>'Br'];
        foreach ($matches as $match) {
            $line = $match[0];
            $value = trim($match[1]);
            $unit = !empty($match[2]) ? $match[2] : 'mg/kg';
            $detected = '';
            foreach ($nameMap as $cn => $en) {
                if (mb_strpos($line, $cn) !== false || stripos($line, $en) !== false) { $detected = $en; break; }
            }
            if (mb_strpos($line, 'PBB') !== false) $detected = 'PBB';
            if (mb_strpos($line, 'PBDE') !== false) $detected = 'PBDE';
            if (stripos($line, 'pfos') !== false) $detected = 'PFOS';
            if (stripos($line, 'pfoa') !== false) $detected = 'PFOA';
            if ($detected) {
                $numVal = (strtoupper($value) === 'N.D.') ? 0 : (float)$value;
                $data['elements'][] = ['substance_name' => $detected, 'content_value' => $numVal, 'unit' => $unit];
            }
        }
    }

    // Fallback: extract from 检测结果 table section
    if (empty($data['elements'])) {
        $elementPatterns = [
            'Pb' => ['Lead\s*\(Pb\)', '铅.*Pb', 'Pb'],
            'Cd' => ['Cadmium\s*\(Cd\)', '镉.*Cd', 'Cd'],
            'Hg' => ['Mercury\s*\(Hg\)', '汞.*Hg', 'Hg'],
            'Cr6+' => ['Cr\(VI\)', 'Hexavalent\s*Chromium', '六价铬'],
            'PBB' => ['PBB'],
            'PBDE' => ['PBDE'],
            'PFOS' => ['PFOS', 'Perfluorooctane\s*Sulfonates'],
            'PFOA' => ['PFOA'],
            'DEHP' => ['DEHP'],
        ];
        foreach ($elementPatterns as $elemName => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match('/' . $pattern . '[^\n]*?((?:N\.D\.)|[0-9.]+)\s*(mg\/kg|ppm|%|mg\/L|渭g\/kg|渭g\/cm虏)?/i', $text, $m)) {
                    $numVal = (strtoupper(trim($m[1])) === 'N.D.') ? 0 : (float)$m[1];
                    $unit = !empty($m[2]) ? $m[2] : 'mg/kg';
                    $data['elements'][] = ['substance_name' => $elemName, 'content_value' => $numVal, 'unit' => $unit];
                    break;
                }
            }
        }
    }

    if (empty($data['expiry_date']) && !empty($data['test_date'])) {
        $data['expiry_date'] = date('Y-m-d', strtotime($data['test_date'] . ' +1 year'));
    }

    if (empty($data['expiry_date']) && !empty($data['test_date'])) {
        $data['expiry_date'] = date('Y-m-d', strtotime($data['test_date'] . ' +1 year'));
    }

    if (empty($data['report_no'])) {
        $data['report_no'] = pathinfo($filename, PATHINFO_FILENAME);
    }

    return $data;
}

function parseExcelReport($rows, $filename) {
    $data = [
        'report_no' => '',
        'test_date' => '',
        'test_agency' => '',
        'expiry_date' => '',
        'elements' => [],
    ];

    // Search through all rows for header info
    foreach ($rows as $row) {
        $rowText = implode(' ', array_filter($row, fn($v) => !empty($v)));
        if (empty($data['report_no']) && preg_match('/(?:报告编号|报告号|Report\s*(?:No|#|Number))[:：\s]*([A-Z0-9\-_\/]+)/i', $rowText, $m)) {
            $data['report_no'] = trim($m[1]);
        }
        if (empty($data['test_date']) && preg_match('/(?:检测日期|收样日期|样品接收日期|Test\s*Date|Receive\s*Date)[:：\s]*(\d{4}[-\.\/]\d{1,2}[-\.\/]\d{1,2})/', $rowText, $m)) {
            $data['test_date'] = $m[1];
        }
        if (empty($data['test_agency']) && preg_match('/(?:检测机构|测试机构|实验室|Lab|Laboratory|Client|Issued\s*by)[:：\s]*([^\n\r]{2,60})/', $rowText, $m)) {
            $data['test_agency'] = trim($m[1]);
        }
    }

    // Find element data rows (skip headers)
    $headerRow = null;
    foreach ($rows as $idx => $row) {
        $rowText = implode(' ', array_filter($row, fn($v) => !empty($v)));
        if (preg_match('/(?:物质|元素|名称|Substance|Element|Analyte)/i', $rowText) &&
            preg_match('/(?:含量|值|结果|Value|Result|Content|Concentration)/i', $rowText)) {
            $headerRow = $idx;
            break;
        }
    }

    if ($headerRow !== null) {
        $headers = $rows[$headerRow];
        $nameCol = null; $valueCol = null; $unitCol = null;
        foreach ($headers as $ci => $h) {
            $h = strtolower(trim($h ?? ''));
            if (preg_match('/(物质|元素|名称|substance|element|analyte|parameter|test)/', $h)) $nameCol = $ci;
            if (preg_match('/(含量|值|结果|value|result|content|concentration|amount)/', $h)) $valueCol = $ci;
            if (preg_match('/(单位|unit)/', $h)) $unitCol = $ci;
        }

        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $name = $nameCol !== null ? trim($row[$nameCol] ?? '') : '';
            $value = $valueCol !== null ? (float)($row[$valueCol] ?? 0) : 0;
            $unit = $unitCol !== null ? trim($row[$unitCol] ?? '') : 'mg/kg';
            if (!empty($name) && $value > 0) {
                $data['elements'][] = [
                    'substance_name' => $name,
                    'content_value' => $value,
                    'unit' => $unit ?: 'mg/kg',
                ];
            }
        }
    }

    if (empty($data['report_no'])) {
        $data['report_no'] = pathinfo($filename, PATHINFO_FILENAME);
    }

    return $data;
}
