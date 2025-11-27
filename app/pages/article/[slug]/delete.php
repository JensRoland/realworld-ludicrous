<?php

use App\Lib\Auth;
use App\Models\Article;

Auth::require();

if (Article::delete($slug, Auth::userId())) {
    header('Location: /');
    exit;
} else {
    http_response_code(403);
    echo "Failed to delete article";
}
