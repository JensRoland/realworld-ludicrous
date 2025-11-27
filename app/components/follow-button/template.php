<button class="btn btn-sm btn-outline-secondary action-btn"
        hx-post="/profile/<?= htmlspecialchars($username) ?>/<?= $action ?>"
        hx-target="this"
        hx-swap="outerHTML">
    <i class="ion-plus-round"></i>
    &nbsp;
    <?= $label ?> <?= htmlspecialchars($username) ?>
</button>
