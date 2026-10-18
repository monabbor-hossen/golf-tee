<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\NewsService;

class NewsController
{
    private NewsService $newsService;

    public function __construct(?NewsService $newsService = null)
    {
        $this->newsService = $newsService ?? new NewsService();
    }

    public function index(Request $request): void
    {
        Session::requireAuth();

        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            if ($action === 'create') {
                $id = $this->newsService->createNews($request->all());
                Response::redirect('news.php?id=' . $id);
            }

            if ($action === 'update') {
                $id = $request->getInt('id');
                $this->newsService->updateNews($id, $request->all());
                Response::redirect('news.php?id=' . $id);
            }

            if ($action === 'toggle_publish') {
                $id = $request->getInt('id');
                $this->newsService->togglePublish($id);
                Response::redirect('news.php?id=' . $id);
            }

            if ($action === 'delete') {
                $id = $request->getInt('id');
                $this->newsService->deleteNews($id);
                Response::redirect('news.php');
            }
        }

        $category = $request->getString('category', 'All');
        $selectedId = $request->getInt('id');

        $data = $this->newsService->listNews($category === 'All' ? null : $category, $selectedId ?: null);
        $data['currentPage'] = 'news';

        View::render('news', $data);
    }
}
