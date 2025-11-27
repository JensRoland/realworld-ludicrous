<?php if ($compact): ?>
<button class="btn btn-sm<?= $alignClass ?> <?= $buttonClass ?>"
        hx-post="/article/<?= htmlspecialchars($slug) ?>/<?= $action ?><?= $queryString ?>"
        hx-target="this"
        hx-swap="outerHTML">
    <i class="ion-heart"></i> <?= $count ?>
</button>
<?php else: ?>
<button class="btn btn-sm<?= $alignClass ?> <?= $buttonClass ?>"
        hx-post="/article/<?= htmlspecialchars($slug) ?>/<?= $action ?><?= $queryString ?>"
        hx-target="this"
        hx-swap="outerHTML">
    <i class="ion-heart"></i>
    &nbsp;
    <?= $label ?> Article <span class="counter">(<?= $count ?>)</span>
</button>
<?php endif; ?>
