<?php
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '未登录']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (!$q) { echo json_encode(['error' => '请输入搜索关键词']); exit; }

$isCas = preg_match('/^\d{2,7}-\d{2}-\d$/', $q);
$results = [];
$db = new PDO('sqlite:' . __DIR__ . '/../database/srm.db');
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// 1) Search local substance table first
if ($isCas) {
    $st = $db->prepare("SELECT * FROM substance WHERE cas_no=?");
    $st->execute([$q]);
    $row = $st->fetch();
    if ($row) $results[] = ['name' => $row['name_en'] ?: $row['name'], 'name_cn' => $row['name'], 'cas' => $row['cas_no'], 'formula' => $row['molecular_formula'] ?? '', 'source' => 'substance'];
} else {
    $qClean = str_replace(['-', ' ', '.'], '', $q);
    $st = $db->prepare("SELECT * FROM substance WHERE name LIKE ? OR name_en LIKE ? OR cas_no LIKE ? LIMIT 10");
    $st->execute(["%$q%", "%$q%", "%$q%"]);
    $rows = $st->fetchAll();
    if (empty($rows) && preg_match('/^\d+$/', $qClean)) {
        $st = $db->prepare("SELECT * FROM substance WHERE REPLACE(cas_no,'-','') LIKE ? LIMIT 10");
        $st->execute(["%$qClean%"]);
        $rows = $st->fetchAll();
    }
    foreach ($rows as $row) {
        $results[] = ['name' => $row['name_en'] ?: $row['name'], 'name_cn' => $row['name'], 'cas' => $row['cas_no'], 'formula' => $row['molecular_formula'] ?? '', 'source' => 'substance'];
    }
}

function autoSaveSubstance($db, $r) {
    if (empty($r['cas']) || !preg_match('/^\d{2,7}-\d{2}-\d$/', $r['cas'])) return;
    $cn = $r['name_cn'] ?? '';
    $en = $r['name'] ?? '';
    $db->execute("INSERT OR IGNORE INTO substance (cas_no, name, name_en, molecular_formula, is_controlled) VALUES (?,?,?,?,0)", [$r['cas'], $cn ?: $en, $en, $r['formula'] ?? '']);
    if ($cn) {
        $db->execute("UPDATE substance SET name=?, name_en=? WHERE cas_no=? AND (name IS NULL OR name='' OR name=name_en)", [$cn, $en, $r['cas']]);
    }
}

// 2) Search common substance map (covers polymers that PubChem may not resolve well)
if (empty($results)) {
    $results = searchCommonSubstances($q, $isCas);
    foreach ($results as $r) autoSaveSubstance($db, $r);
}

// 3) Search ichemistry.cn (Chinese chemical database, good for Chinese/English names)
if (empty($results)) {
    $results = searchIChemistry($q);
    foreach ($results as $r) autoSaveSubstance($db, $r);
}

// 4) Search PubChem (most reliable, works for most CAS numbers)
if (empty($results)) {
    $results = searchPubChemByName($q);
    foreach ($results as $r) autoSaveSubstance($db, $r);
}

echo json_encode($results ?: ['error' => '未找到相关物质'], JSON_UNESCAPED_UNICODE);

function searchCommonSubstances($q, $isCas) {
    $common = [
        '9002-86-2' => ['name' => 'Polyvinyl chloride', 'name_cn' => '聚氯乙烯', 'formula' => '(C2H3Cl)n'],
        '9003-07-0' => ['name' => 'Polypropylene', 'name_cn' => '聚丙烯', 'formula' => '(C3H6)n'],
        '9002-88-4' => ['name' => 'Polyethylene', 'name_cn' => '聚乙烯', 'formula' => '(C2H4)n'],
        '9003-55-8' => ['name' => 'Polystyrene', 'name_cn' => '聚苯乙烯', 'formula' => '(C8H8)n'],
        '25038-59-9' => ['name' => 'Polyethylene terephthalate', 'name_cn' => '聚对苯二甲酸乙二醇酯', 'formula' => '(C10H8O4)n'],
        '9003-20-7' => ['name' => 'Polyvinyl acetate', 'name_cn' => '聚醋酸乙烯酯', 'formula' => '(C4H6O2)n'],
        '9003-35-4' => ['name' => 'Phenolic resin', 'name_cn' => '酚醛树脂', 'formula' => ''],
        '25085-99-6' => ['name' => 'Epoxy resin', 'name_cn' => '环氧树脂', 'formula' => ''],
        '9003-31-0' => ['name' => 'Polyisoprene', 'name_cn' => '聚异戊二烯', 'formula' => '(C5H8)n'],
        '9010-98-4' => ['name' => 'Polychloroprene', 'name_cn' => '氯丁橡胶', 'formula' => '(C4H5Cl)n'],
        '25034-71-3' => ['name' => 'Polyoxymethylene', 'name_cn' => '聚甲醛', 'formula' => '(CH2O)n'],
        '24969-26-4' => ['name' => 'Polytetrahydrofuran', 'name_cn' => '聚四氢呋喃', 'formula' => ''],
        '9002-83-9' => ['name' => 'Polychlorotrifluoroethylene', 'name_cn' => '聚三氟氯乙烯', 'formula' => '(C2ClF3)n'],
        '9002-84-0' => ['name' => 'Polytetrafluoroethylene', 'name_cn' => '聚四氟乙烯', 'formula' => '(C2F4)n'],
        '9002-85-1' => ['name' => 'Polyvinylidene fluoride', 'name_cn' => '聚偏二氟乙烯', 'formula' => '(C2H2F2)n'],
        '9002-89-5' => ['name' => 'Polyvinyl alcohol', 'name_cn' => '聚乙烯醇', 'formula' => '(C2H4O)n'],
        '9003-01-4' => ['name' => 'Polyacrylic acid', 'name_cn' => '聚丙烯酸', 'formula' => '(C3H4O2)n'],
        '9003-05-8' => ['name' => 'Polyacrylamide', 'name_cn' => '聚丙烯酰胺', 'formula' => '(C3H5NO)n'],
        '9011-14-7' => ['name' => 'Polycarbonate', 'name_cn' => '聚碳酸酯', 'formula' => ''],
        '25037-45-0' => ['name' => 'Polybutylene terephthalate', 'name_cn' => '聚对苯二甲酸丁二醇酯', 'formula' => '(C12H12O4)n'],
        '9003-11-6' => ['name' => 'Polypropylene glycol', 'name_cn' => '聚丙二醇', 'formula' => '(C3H8O2)n'],
        '25322-68-3' => ['name' => 'Polyethylene glycol', 'name_cn' => '聚乙二醇', 'formula' => '(C2H6O2)n'],
        '26655-00-5' => ['name' => 'Polyamide', 'name_cn' => '聚酰胺(尼龙)', 'formula' => ''],
    ];
    $results = [];
    if ($isCas) {
        if (isset($common[$q])) {
            $r = $common[$q];
            $results[] = ['name' => $r['name'], 'name_cn' => $r['name_cn'], 'cas' => $q, 'formula' => $r['formula'], 'source' => 'common'];
        }
    } else {
        foreach ($common as $cas => $r) {
            if (stripos($r['name'], $q) !== false || mb_strpos($r['name_cn'], $q) !== false) {
                $results[] = ['name' => $r['name'], 'name_cn' => $r['name_cn'], 'cas' => $cas, 'formula' => $r['formula'], 'source' => 'common'];
            }
        }
    }
    return $results;
}

function searchIChemistry($query) {
    $results = [];
    $isCas = preg_match('/^\d{2,7}-\d{2}-\d$/', $query);

    if ($isCas) {
        $html = fetchUrl('http://www.ichemistry.cn/cas/' . urlencode($query) . '.html');
        if (!$html) return $results;
        $html = @mb_convert_encoding($html, 'UTF-8', 'GBK,GB2312,GB18030');

        if (!preg_match('/<table[^>]*id=["\']container-right["\'][^>]*>(.*?)<\/table>/s', $html, $m)) return $results;
        if (!preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $m[1], $rows)) return $results;
        if (count($rows[1]) < 2) return $results;

        preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $rows[1][1], $tds);
        if (count($tds[1]) < 5) return $results;

        $nameCn = trim(explode(';', str_replace('；', ';', strip_tags(str_replace(['<br>','<br/>','<br />'], ';', $tds[1][1]))))[0]);
        $name = trim(explode(';', str_replace('；', ';', strip_tags(str_replace(['<br>','<br/>','<br />'], ';', $tds[1][2]))))[0]);
        $formula = trim(strip_tags($tds[1][4]));

        $results[] = ['name' => $name, 'name_cn' => $nameCn, 'cas' => $query, 'formula' => $formula, 'source' => 'ichemistry'];
    } else {
        $html = fetchUrl('http://search.ichemistry.cn?keys=' . urlencode($query) . '&types=2');
        if (!$html) return $results;
        $html = @mb_convert_encoding($html, 'UTF-8', 'GBK,GB2312,GB18030');

        if (!preg_match('/<table[^>]*id=["\']container-right["\'][^>]*>(.*?)<\/table>/s', $html, $m)) return $results;
        if (!preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $m[1], $rows)) return $results;
        for ($i = 1; $i < count($rows[1]) && count($results) < 5; $i++) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $rows[1][$i], $tds);
            if (count($tds[1]) < 5) continue;
            $casHtml = strip_tags($tds[1][0]);
            if (!preg_match('/\b(\d{2,7}-\d{2}-\d)\b/', $casHtml, $casM)) continue;
            $nameCn = trim(explode(';', str_replace('；', ';', strip_tags(str_replace(['<br>','<br/>','<br />'], ';', $tds[1][1]))))[0]);
            $name = trim(explode(';', str_replace('；', ';', strip_tags(str_replace(['<br>','<br/>','<br />'], ';', $tds[1][2]))))[0]);
            $formula = trim(strip_tags($tds[1][4]));
            $results[] = ['name' => $name, 'name_cn' => $nameCn, 'cas' => $casM[1], 'formula' => $formula, 'source' => 'ichemistry'];
        }
    }

    return $results;
}

function pubchemGetCids($path) {
    $url = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/' . $path . '/cids/JSON';
    $resp = fetchUrl($url);
    if (!$resp) return [];
    $data = json_decode($resp, true);
    if (!empty($data['IdentifierList']['CID'])) return $data['IdentifierList']['CID'];
    $cids = [];
    foreach ($data['InformationList']['Information'] ?? [] as $info) {
        if (isset($info['CID'][0])) $cids[] = $info['CID'][0];
    }
    return array_unique($cids);
}

function searchPubChemByName($query) {
    $results = [];
    $isCas = preg_match('/^\d{2,7}-\d{2}-\d$/', $query);
    $cids = []; $xrefFound = false;

    if ($isCas) {
        $cids = pubchemGetCids('compound/xref/RN/' . urlencode($query));
        if (!empty($cids)) $xrefFound = true;
        if (empty($cids)) $cids = pubchemGetCids('compound/name/' . urlencode($query));
        if (empty($cids)) $cids = pubchemGetCids('substance/name/' . urlencode($query));
    } else {
        $cids = pubchemGetCids('compound/name/' . urlencode($query));
        if (empty($cids)) $cids = pubchemGetCids('substance/name/' . urlencode($query));
    }
    if (empty($cids)) return $results;

    foreach (array_slice($cids, 0, 5) as $cid) {
        $propUrl = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/' . $cid . '/property/MolecularFormula,IUPACName/JSON';
        $synUrl = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/' . $cid . '/synonyms/JSON';
        $propResp = fetchUrl($propUrl);
        $synResp = fetchUrl($synUrl);
        $props = json_decode($propResp, true);
        $syns = json_decode($synResp, true);
        $formula = $props['PropertyTable']['Properties'][0]['MolecularFormula'] ?? '';
        $iupac = $props['PropertyTable']['Properties'][0]['IUPACName'] ?? '';
        $synonyms = $syns['InformationList']['Information'][0]['Synonym'] ?? [];
        $name = $synonyms[0] ?? $iupac;
        $nameCn = '';

        if ($isCas) {
            $confirmed = $xrefFound;
            if (!$confirmed) {
                foreach ($synonyms as $s) {
                    if (trim($s) === $query) { $confirmed = true; break; }
                }
                if (!$confirmed) continue;
            }
            $cas = $query;
        } else {
            $confirmed = true;
            $cas = $query;
            foreach ($synonyms as $s) {
                if (preg_match('/^\d{2,7}-\d{2}-\d$/', trim($s))) { $cas = trim($s); break; }
            }
        }

        foreach ($synonyms as $s) {
            $s = trim($s);
            if (preg_match('/[\x{4e00}-\x{9fff}]/u', $s) && mb_strlen($s) <= 30) { $nameCn = $s; break; }
        }

        $results[] = ['name' => $name, 'name_cn' => $nameCn, 'cas' => $cas, 'formula' => $formula, 'source' => 'pubchem', 'confirmed' => $confirmed];
    }
    return $results;
}

function fetchUrl($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    if (!$resp && strpos($err, 'SSL') !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }
    curl_close($ch);
    return ($http >= 200 && $http < 400) ? $resp : null;
}
