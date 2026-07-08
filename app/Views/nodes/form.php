<div class="panel glass panel-narrow">
    <h2><?= $node ? 'Node redaktəsi' : 'Yeni node' ?></h2>
    <form method="post" action="<?= $node ? '/bots/' . (int) $bot['id'] . '/nodes/' . (int) $node['id'] : '/bots/' . (int) $bot['id'] . '/nodes' ?>">
        <?= \App\Core\Csrf::field() ?>

        <label>Node key (unikal)
            <input type="text" name="node_key" required value="<?= e($node['node_key'] ?? '') ?>" placeholder="məs. start, menu, about">
        </label>
        <label>Başlıq
            <input type="text" name="title" value="<?= e($node['title'] ?? '') ?>">
        </label>
        <label>Node tipi
            <select name="node_type" id="node_type">
                <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= ($node['node_type'] ?? 'text') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="field-group" data-types="text,buttons,input,image,final">
            <label>Mesaj mətni <span class="muted">({{field_key}} dəyişənləri işlədilə bilər)</span>
                <textarea name="message_text" rows="4"><?= e($node['message_text'] ?? '') ?></textarea>
            </label>
        </div>

        <div class="field-group" data-types="image">
            <label>Media URL
                <input type="text" name="media_url" value="<?= e($node['media_url'] ?? '') ?>">
            </label>
        </div>

        <div class="field-group" data-types="input">
            <label>Input field key
                <input type="text" name="input_field_key" value="<?= e($node['input_field_key'] ?? '') ?>" placeholder="məs. phone, email">
            </label>
            <label>Cavab sonrası hansı node-a keçsin
                <select name="input_next_node">
                    <option value="">— seçilməyib —</option>
                    <?php foreach ($nodes as $n): ?>
                        <option value="<?= (int) $n['id'] ?>" <?= (int) ($node['input_next_node'] ?? 0) === (int) $n['id'] ? 'selected' : '' ?>><?= e($n['node_key']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="field-group" data-types="delay">
            <label>Gecikmə (saniyə)
                <input type="number" name="delay_seconds" min="0" value="<?= e((string) ($node['delay_seconds'] ?? '')) ?>">
            </label>
        </div>

        <div class="field-group" data-types="delay,goto">
            <label>Növbəti node (goto)
                <select name="goto_node_id">
                    <option value="">— seçilməyib —</option>
                    <?php foreach ($nodes as $n): ?>
                        <option value="<?= (int) $n['id'] ?>" <?= (int) ($node['goto_node_id'] ?? 0) === (int) $n['id'] ? 'selected' : '' ?>><?= e($n['node_key']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="field-group" data-types="api">
            <label>API URL
                <input type="text" name="api_url" value="<?= e($node['api_url'] ?? '') ?>">
            </label>
            <label>API metodu
                <select name="api_method">
                    <option value="POST" <?= ($node['api_method'] ?? 'POST') === 'POST' ? 'selected' : '' ?>>POST</option>
                    <option value="GET" <?= ($node['api_method'] ?? 'POST') === 'GET' ? 'selected' : '' ?>>GET</option>
                </select>
            </label>
        </div>

        <button type="submit" class="btn btn-primary"><?= $node ? 'Yadda saxla' : 'Yarat' ?></button>
    </form>
</div>

<?php if ($node): ?>
<div class="panel glass panel-narrow">
    <h2>Düymələr</h2>
    <?php if ($buttons === []): ?>
        <p class="empty-state">Bu node üçün düymə yoxdur.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Mətn</th><th>Tip</th><th>Hədəf</th><th>Sıra</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($buttons as $btn): ?>
                <tr>
                    <td><?= e($btn['button_text']) ?></td>
                    <td><?= e($btn['button_type']) ?></td>
                    <td><?= $btn['button_type'] === 'url' ? e($btn['url'] ?? '') : e((string) ($btn['target_node_id'] ?? '—')) ?></td>
                    <td><?= (int) $btn['sort_order'] ?></td>
                    <td>
                        <form method="post" action="/bots/<?= (int) $bot['id'] ?>/nodes/<?= (int) $node['id'] ?>/buttons/<?= (int) $btn['id'] ?>/delete" onsubmit="return confirm('Düymə silinsin?');">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn-link btn-link-danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Yeni düymə</h3>
    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/nodes/<?= (int) $node['id'] ?>/buttons" class="inline-form">
        <?= \App\Core\Csrf::field() ?>
        <label>Mətn <input type="text" name="button_text" required></label>
        <label>Tip
            <select name="button_type" id="button_type">
                <option value="inline">inline (node-a keçid)</option>
                <option value="url">url (xarici link)</option>
            </select>
        </label>
        <label class="btn-target-node">Hədəf node
            <select name="target_node_id">
                <option value="">—</option>
                <?php foreach ($nodes as $n): ?>
                    <option value="<?= (int) $n['id'] ?>"><?= e($n['node_key']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="btn-target-url">URL <input type="text" name="url" placeholder="https://..."></label>
        <label>Tag təyin et
            <select name="action_tag_id">
                <option value="">—</option>
                <?php foreach ($tags as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Sıra <input type="number" name="sort_order" value="0"></label>
        <button type="submit" class="btn btn-primary">Əlavə et</button>
    </form>
</div>
<?php endif; ?>
