<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\NewsService;
use finfo;

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
                $data = $request->all();
                $uploadedUrl = $this->handleUploadedImage();
                if ($uploadedUrl) {
                    $data['image_url'] = $uploadedUrl;
                }
                $id = $this->newsService->createNews($data);
                Response::redirect('news.php?id=' . $id);
            }

            if ($action === 'update') {
                $id = $request->getInt('id');
                $data = $request->all();
                $uploadedUrl = $this->handleUploadedImage();
                if ($uploadedUrl) {
                    $data['image_url'] = $uploadedUrl;
                }
                $this->newsService->updateNews($id, $data);
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

    private function handleUploadedImage(): ?string
    {
        if (empty($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['image_file'];
        $maxBytes = 8 * 1024 * 1024; // 8MB
        if ($file['size'] > $maxBytes) {
            return null;
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fileInfo->file($file['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        if (!isset($allowed[$mime])) {
            return null;
        }

        $ext = $allowed[$mime];
        $filename = 'news_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $targetDir = dirname(__DIR__, 2) . '/uploads/news';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/news/' . $filename;
        }

        return null;
    }
}
