<?php /** @var string|null $error */ ?>
<div class="container" style="max-width:420px;margin:3rem auto;">
  <h1 class="h4 mb-3">Login</h1>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= base_url('/login') ?>">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input name="email" type="email" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input name="password" type="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">Sign in</button>
  </form>
</div>
