<?php
require __DIR__ . '/_inc.php';
header('Content-Type: application/json');
if (!is_admin()) { http_response_code(403); echo json_encode(['error' => 'Not logged in']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
csrf_check();

switch ($_GET['do'] ?? '') {
    case 'preview':
        $toc = [];
        echo json_encode(['html' => md_to_html((string)($_POST['content'] ?? ''), $toc)]);
        break;
    case 'upload':
        try { echo json_encode(['url' => handle_upload($_FILES['file'] ?? [])]); }
        catch (RuntimeException $e) { http_response_code(400); echo json_encode(['error' => $e->getMessage()]); }
        break;
    default:
        http_response_code(400); echo json_encode(['error' => 'Unknown action']);
}
