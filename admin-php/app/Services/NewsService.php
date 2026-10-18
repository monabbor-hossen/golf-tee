<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ActivityRepository;
use App\Repositories\NewsRepository;

class NewsService
{
    private NewsRepository $newsRepo;
    private ActivityRepository $activityRepo;

    public function __construct(
        ?NewsRepository $newsRepo = null,
        ?ActivityRepository $activityRepo = null
    ) {
        $this->newsRepo = $newsRepo ?? new NewsRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
    }

    public function listNews(?string $category = null, ?int $selectedId = null): array
    {
        $articles = $this->newsRepo->getAll($category);
        $counts = $this->newsRepo->getCategoryCounts();
        $total = array_sum($counts);
        $publishedCount = $this->newsRepo->getPublishedCount();

        $selectedNews = null;
        if ($selectedId !== null) {
            $selectedNews = $this->newsRepo->findById($selectedId);
        } elseif (!empty($articles)) {
            $selectedNews = $articles[0];
        }

        return [
            'articles'       => $articles,
            'selectedNews'   => $selectedNews,
            'selectedId'     => $selectedNews ? (int)$selectedNews['id'] : null,
            'category'       => $category ?? 'All',
            'counts'         => $counts,
            'total'          => $total,
            'publishedCount' => $publishedCount,
            'categories'     => ['All', 'Event', 'Course Update', 'Pro Shop', 'General'],
        ];
    }

    public function createNews(array $data): int
    {
        $id = $this->newsRepo->create([
            'title'        => trim($data['title'] ?? ''),
            'category'     => $data['category'] ?? 'General',
            'badge_text'   => trim($data['badge_text'] ?? '') ?: null,
            'summary'      => trim($data['summary'] ?? ''),
            'content'      => trim($data['content'] ?? '') ?: null,
            'image_url'    => trim($data['image_url'] ?? '') ?: null,
            'is_published' => !empty($data['is_published']),
            'published_at' => $data['published_at'] ?? date('Y-m-d'),
        ]);

        $this->activityRepo->log(
            'Club news published',
            trim($data['title'] ?? 'New announcement'),
            'forest'
        );

        return $id;
    }

    public function updateNews(int $id, array $data): bool
    {
        return $this->newsRepo->update($id, [
            'title'        => trim($data['title'] ?? ''),
            'category'     => $data['category'] ?? 'General',
            'badge_text'   => trim($data['badge_text'] ?? '') ?: null,
            'summary'      => trim($data['summary'] ?? ''),
            'content'      => trim($data['content'] ?? '') ?: null,
            'image_url'    => trim($data['image_url'] ?? '') ?: null,
            'is_published' => !empty($data['is_published']),
            'published_at' => $data['published_at'] ?? date('Y-m-d'),
        ]);
    }

    public function togglePublish(int $id): bool
    {
        return $this->newsRepo->togglePublish($id);
    }

    public function deleteNews(int $id): bool
    {
        return $this->newsRepo->delete($id);
    }

    public function getPublicNews(): array
    {
        return $this->newsRepo->getAll(null, true);
    }
}
