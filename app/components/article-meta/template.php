<a href="/profile/<?= htmlspecialchars($authorUsername) ?>" title="Author Profile">
    <img src="<?= htmlspecialchars($authorImageThumb) ?>" width="32" height="32" alt="author avatar" />
</a>
<div class="info">
    <a href="/profile/<?= htmlspecialchars($authorUsername) ?>" class="author"><?= htmlspecialchars($authorUsername) ?></a>
    <span class="date"><?= $date ?></span>
</div>
