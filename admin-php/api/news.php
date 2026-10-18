<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\NewsService;

$request = Request::createFromGlobals();
$newsService = new NewsService();

if ($request->isGet()) {
    $publishedOnly = !Session::isAuthenticated() || $request->getString('mode') === 'public';
    $articles = $publishedOnly ? $newsService->getPublicNews() : $newsService->listNews($request->getString('category') ?: null)['articles'];
    Response::json([
        'status'   => 'success',
        'count'    => count($articles),
        'articles' => $articles,
    ]);
}

if ($request->isPost()) {
    Session::requireAuth();
    Session::validateRequestCsrf($request);
    $action = $request->getString('action');

    if ($action === 'toggle_publish') {
        $id = $request->getInt('id');
        $success = $newsService->togglePublish($id);
        Response::json(['status' => $success ? 'success' : 'error']);
    }

    Response::json(['status' => 'error', 'message' => 'Invalid action'], 400);
}
