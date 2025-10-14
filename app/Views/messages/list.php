<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $messages */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><i class="bi bi-chat-dots me-2"></i> <?= e($title ?? 'Üzeneteim') ?></h3>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card border-primary-subtle shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-regular fa-envelope me-1"></i> Beérkezett üzenetek
        </h5>
        <span class="text-body-secondary small">Összesen: <?= count($messages) ?></span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:70px;">Kép</th>
                <th>Cím</th>
                <th>Üzenet</th>
                <th>Dátum</th>
                <th>Állapot</th>
                <th class="text-end" style="width:150px;">Művelet</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($messages)): ?>
                <tr>
                  <td colspan="6" class="text-center text-secondary py-4">
                    <i class="fa-regular fa-envelope-open-text me-1"></i> Nincsenek üzeneteid
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($messages as $m): ?>
                  <?php
                    $avatarSrc = !empty($m['sender_avatar'])
                        ? e($m['sender_avatar'])
                        : base_url('public/assets/adminlte/img/user.png');
                  ?>
                  <tr class="<?= $m['is_read'] ? 'text-secondary' : 'fw-semibold' ?>">
                    <td>
                      <img src="<?= $avatarSrc ?>" referrerpolicy="no-referrer"
                           class="rounded-circle shadow-sm" width="40" height="40" alt="Avatar">
                    </td>
                    <td><?= e((string)$m['title']) ?></td>
                    <td><?= e((string)$m['body']) ?></td>
                    <td><?= date('Y.m.d H:i', strtotime($m['created_at'])) ?></td>
                    <td><?= $m['is_read'] ? 'Olvasott' : 'Olvasatlan' ?></td>
                    <td class="text-end">
                      <?php if (!$m['is_read']): ?>
                        <form method="post" action="<?= base_url('/messages/' . $m['id'] . '/read') ?>" class="d-inline">
                          <?= csrf_field() ?>
                          <button type="submit" class="btn btn-sm btn-success" alt="Olvasottnak jelölés" title="Olvasottnak jelöl">
                            <i class="fa-solid fa-envelope-open-text"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-body-secondary small">
        <i class="fa-solid fa-circle-info me-1"></i> Az <strong>olvasatlan</strong> üzenetek félkövérrel jelennek meg.
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
