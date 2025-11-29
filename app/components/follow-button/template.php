<button class="btn btn-sm btn-outline-secondary action-btn"
        fx-action="/profile/<?= htmlspecialchars($username) ?>/<?= $action ?>"
        fx-method="POST"
        fx-swap="outerHTML">
    <i class="ion-plus-round"></i>
    &nbsp;
    <?= $label ?> <?= htmlspecialchars($username) ?>
</button>
