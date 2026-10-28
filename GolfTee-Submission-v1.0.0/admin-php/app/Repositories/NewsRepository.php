<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class NewsRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function getAll(?string $category = null, ?bool $publishedOnly = null): array
    {
        $sql = 'SELECT * FROM club_news WHERE 1=1';
        $params = [];

        if ($category !== null && $category !== 'All' && $category !== '') {
            $sql .= ' AND category = :category';
            $params['category'] = $category;
        }

        if ($publishedOnly !== null) {
            $sql .= ' AND is_published = :is_published';
            $params['is_published'] = $publishedOnly ? 1 : 0;
        }

        $sql .= ' ORDER BY published_at DESC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM club_news WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO club_news (title, category, badge_text, summary, content, image_url, is_published, published_at)
                VALUES (:title, :category, :badge_text, :summary, :content, :image_url, :is_published, :published_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title'        => $data['title'],
            'category'     => $data['category'] ?? 'General',
            'badge_text'   => $data['badge_text'] ?? null,
            'summary'      => $data['summary'],
            'content'      => $data['content'] ?? null,
            'image_url'    => $data['image_url'] ?? null,
            'is_published' => !empty($data['is_published']) ? 1 : 0,
            'published_at' => $data['published_at'] ?? date('Y-m-d'),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE club_news SET
                title = :title,
                category = :category,
                badge_text = :badge_text,
                summary = :summary,
                content = :content,
                image_url = :image_url,
                is_published = :is_published,
                published_at = :published_at
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'           => $id,
            'title'        => $data['title'],
            'category'     => $data['category'] ?? 'General',
            'badge_text'   => $data['badge_text'] ?? null,
            'summary'      => $data['summary'],
            'content'      => $data['content'] ?? null,
            'image_url'    => $data['image_url'] ?? null,
            'is_published' => !empty($data['is_published']) ? 1 : 0,
            'published_at' => $data['published_at'] ?? date('Y-m-d'),
        ]);
    }

    public function togglePublish(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE club_news SET is_published = 1 - is_published WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM club_news WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function search(string $query, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM club_news WHERE (title LIKE :q OR summary LIKE :q2 OR category LIKE :q3) ORDER BY published_at DESC LIMIT :limit'
        );
        $param = '%' . $query . '%';
        $stmt->bindValue(':q', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $param, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPublishedCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM club_news WHERE is_published = 1');
        return (int)$stmt->fetchColumn();
    }

    public function getCategoryCounts(): array
    {
        $stmt = $this->pdo->query('SELECT category, COUNT(*) as cnt FROM club_news GROUP BY category');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $counts = [];
        foreach ($rows as $r) {
            $counts[$r['category']] = (int)$r['cnt'];
        }

        return $counts;
    }
}
