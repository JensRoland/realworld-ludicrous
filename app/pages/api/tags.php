<?php

use App\Lib\Database;

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

$db = Database::getConnection();
$qb = $db->createQueryBuilder();

// Get all tags, ordered by usage count (most popular first)
$result = $qb->select('t.name', 'COUNT(DISTINCT at.article_id) as count')
    ->from('tags', 't')
    ->leftJoin('t', 'article_tags', 'at', 't.id = at.tag_id')
    ->groupBy('t.id', 't.name')
    ->orderBy('count', 'DESC')
    ->addOrderBy('t.name', 'ASC')
    ->executeQuery()
    ->fetchAllAssociative();

$tags = array_column($result, 'name');

// Filter by query if provided
if ($query !== '') {
    $queryLower = strtolower($query);
    $tags = array_values(array_filter($tags, fn($tag) => str_contains(strtolower($tag), $queryLower)));
}

echo json_encode($tags);
