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
    // Also search by cleaned CAS
    if (empty($rows) && preg_match('/^\d+$/', $qClean)) {
        $st = $db->prepare("SELECT * FROM substance WHERE REPLACE(cas_no,'-','') LIKE ? LIMIT 10");
        $st->execute(["%$qClean%"]);
        $rows = $st->fetchAll();
    }
    foreach ($rows as $row) {
        $results[] = ['name' => $row['name_en'] ?: $row['name'], 'name_cn' => $row['name'], 'cas' => $row['cas_no'], 'formula' => $row['molecular_formula'] ?? '', 'source' => 'substance'];
    }
}

// Auto-save helper
function autoSaveSubstance($db, $r) {
    if (empty($r['cas']) || !preg_match('/^\d{2,7}-\d{2}-\d$/', $r['cas'])) return;
    // Skip unconfirmed PubChem results (xref/RN returned CID but CAS not in compound synonyms)
    if (isset($r['confirmed']) && !$r['confirmed']) return;
    $st = $db->prepare("SELECT id FROM substance WHERE cas_no=?");
    $st->execute([$r['cas']]);
    if (!$st->fetch()) {
        $ins = $db->prepare("INSERT OR IGNORE INTO substance (cas_no, name, name_en, molecular_formula, is_controlled) VALUES (?,?,?,?,0)");
        $ins->execute([$r['cas'], $r['name_cn'] ?: $r['name'], $r['name'], $r['formula'] ?? '']);
    }
}

if ($isCas) {
    // CAS: local -> iChemistry -> PubChem -> Chemsrc
    if (empty($results)) {
        $results = scrapeIChemistry($q, true);
        foreach ($results as $r) autoSaveSubstance($db, $r);
    }
    if (empty($results)) {
        $results = searchPubChemByName($q);
        foreach ($results as $r) autoSaveSubstance($db, $r);
    }
    if (empty($results)) {
        $results = scrapeChemsrcCasindex($q);
    }
} else {
    // Non-CAS: local -> PubChem -> iChemistry search
    if (empty($results)) {
        $results = searchPubChemByName($q);
        foreach ($results as $r) autoSaveSubstance($db, $r);
    }
    if (empty($results)) {
        $results = scrapeIChemistry($q, false);
    }
}

// Strip internal fields before output
$results = array_map(function($r) { unset($r['confirmed']); return $r; }, $results);

echo json_encode($results ?: ['error' => '未找到相关物质'], JSON_UNESCAPED_UNICODE);

function pubchemGetCids($path) {
    $url = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/' . $path . '/cids/JSON';
    $resp = fetchUrl($url);
    if (!$resp) return [];
    $data = json_decode($resp, true);
    // compound endpoint: { IdentifierList: { CID: [...] } }
    if (!empty($data['IdentifierList']['CID'])) return $data['IdentifierList']['CID'];
    // substance endpoint: { InformationList: { Information: [{ SID, CID }, ...] } }
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
            // xref/RN is authoritative 鈥?trust its results even if CAS not in compound synonyms
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

function scrapeChemsrcCasindex($cas) {
    $results = [];
    foreach (['https://www.chemsrc.com/casindex/','https://www.chemsrc.com/en/cas/'] as $base) {
        $html = fetchUrl($base . urlencode($cas) . '.html');
        if (!$html) continue;
        $name = ''; $nameCn = ''; $formula = '';
        if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
            $title = trim(preg_replace('/\s*\|.*$/', '', $m[1]));
            $title = preg_replace('/^CAS\s+\S+\s+/i', '', $title);
            if (preg_match('/^([A-Za-z0-9\s\/\(\)\-,.]+)\s+([\x{4e00}-\x{9fff}].*)$/u', $title, $p)) {
                $name = trim($p[1]); $nameCn = trim($p[2]);
            } elseif (preg_match('/^[\x{4e00}-\x{9fff}]/u', $title)) { $nameCn = $title; $name = $cas; }
            else { $name = $title; }
        }
        if (preg_match('/鍒嗗瓙寮廩锛?]\s*([^<>&\n]{1,30})/i', $html, $m)) $formula = trim($m[1]);
        elseif (preg_match('/Molecular\s*Formula[^:]*:\s*([^<>&\n]{1,30})/i', $html, $m)) $formula = trim($m[1]);
        $results[] = ['name' => $name ?: $cas, 'name_cn' => $nameCn, 'cas' => $cas, 'formula' => $formula, 'source' => 'chemsrc'];
        break;
    }
    return $results;
}

function scrapeIChemistry($q, $isCas) {
    $results = [];
    $url = $isCas ? 'http://www.ichemistry.cn/chemical/' . urlencode($q) . '.htm'
                 : 'http://www.ichemistry.cn/search.asp?keyword=' . urlencode($q) . '&st=all&search=%CB%D1%CB%F7';
    $html = fetchUrl($url);
    if (!$html) return $results;
    if ($isCas) {
        $name = ''; $nameCn = ''; $formula = '';
        if (preg_match('/<(h1|h2)[^>]*>([^<]+)<\/\1>/i', $html, $m)) {
            $title = trim($m[2]);
            if (preg_match('/^([A-Za-z0-9\s\/\(\)\-,.]+)\s+([\x{4e00}-\x{9fff}].*)$/u', $title, $p)) {
                $name = trim($p[1]); $nameCn = trim($p[2]);
            } elseif (preg_match('/^[\x{4e00}-\x{9fff}]/u', $title)) { $nameCn = $title; $name = $q; }
            else { $name = $title; }
        }
        if (preg_match('/鍒嗗瓙寮廩锛?]\s*([^<>&\n]{1,30})/i', $html, $m)) $formula = trim($m[1]);
        $results[] = ['name' => $name ?: $q, 'name_cn' => $nameCn, 'cas' => $q, 'formula' => $formula, 'source' => 'ichemistry'];
    } else {
        if (preg_match_all('/<td[^>]*>\s*(\d{2,7}-\d{2}-\d)\s*<\/td>\s*<td[^>]*>\s*<a[^>]*>([^<]+)</i', $html, $matches, PREG_SET_ORDER)) {
            $seen = [];
            foreach ($matches as $m) {
                $cas = $m[1]; if (isset($seen[$cas])) continue;
                $seen[$cas] = true;
                $results[] = ['name' => trim(strip_tags($m[2])), 'name_cn' => '', 'cas' => $cas, 'formula' => '', 'source' => 'ichemistry'];
                if (count($results) >= 10) break;
            }
        }
    }
    return $results;
}

function fetchUrl($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($http >= 200 && $http < 400) ? $resp : null;
}
