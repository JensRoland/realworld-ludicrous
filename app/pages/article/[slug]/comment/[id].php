<?php

use App\Lib\Auth;
use App\Models\Comment;

// Only handle DELETE requests
if ($request->method !== 'DELETE') {
    http_response_code(405);
    echo "Method not allowed";
    return;
}

if (!Auth::check()) {
    header('Location: /login');
    exit;
}

if (Comment::delete((int)$id, Auth::userId())) {
    // Return empty string to remove element from DOM
    echo "";
} else {
    http_response_code(403);
    echo "Failed to delete comment";
}
