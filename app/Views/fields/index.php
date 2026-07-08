<div class="page-header">
    <h2>Custom sahələr</h2>
</div>

<?php if ($fields === []): ?>
    <p class="empty-state">Hələ custom sahə istifadə olunmayıb. Sahələr "input" tipli node-larda avtomatik yaranır.</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th>Field key</th><th>İstifadə sayı</th></tr></thead>
        <tbody>
        <?php foreach ($fields as $f): ?>
            <tr><td><code><?= e($f['field_key']) ?></code></td><td><?= (int) $f['usage_count'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
