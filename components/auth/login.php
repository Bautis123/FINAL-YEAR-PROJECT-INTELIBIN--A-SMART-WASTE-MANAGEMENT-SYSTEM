<?php
/**
 * InteliBin login component.
 *
 * Configure with a $login array:
 * - action: form action URL
 * - fields: ['user' => 'user_id', 'pass' => 'password', 'remember' => 'remember_me']
 * - csrf: ['name' => 'login_csrf_token', 'value' => '...']
 * - error: failed login message or null
 * - username: submitted username/email after failure
 * - user_label: label for the first field
 * - show_remember: bool
 * - forgot_href: URL string or null
 * - help_text: helper copy string or null
 * - features: array of brand panel feature lines
 * - project_note: project credit string or null
 */
if (!function_exists('ib_e')) {
  function ib_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

$login = array_replace_recursive([
  'action' => '',
  'fields' => ['user' => 'user', 'pass' => 'password', 'remember' => 'remember'],
  'csrf' => ['name' => '', 'value' => ''],
  'error' => null,
  'username' => '',
  'user_label' => 'Email or username',
  'show_remember' => false,
  'forgot_href' => null,
  'help_text' => null,
  'features' => [],
  'project_note' => null,
], $login ?? []);

$ibLgHasError = trim((string)$login['error']) !== '';
?>
<main class="ib-lg-shell" data-lg="shell">
  <section class="ib-lg-brand" aria-label="InteliBin overview" data-lg="brand">
    <div class="ib-lg-brand-inner">
      <div class="ib-lg-mark" aria-hidden="true" data-lg="mark">
        <div class="ib-lg-mark-lid"></div>
        <div class="ib-lg-mark-body">
          <span class="ib-lg-mark-line"></span>
          <span class="ib-lg-mark-line"></span>
          <span class="ib-lg-mark-line"></span>
        </div>
      </div>

      <p class="ib-lg-kicker">Smart Waste Management</p>
      <h1 class="ib-lg-title">Inteli<span class="ib-lg-title-accent">Bin</span></h1>

      <?php if (!empty($login['features'])): ?>
      <div class="ib-lg-features" aria-label="Key features" data-lg="features">
        <?php foreach ($login['features'] as $feature): ?>
        <div class="ib-lg-feature">
          <span class="ib-lg-feature-dot" aria-hidden="true"></span>
          <span class="ib-lg-feature-text"><?= ib_e($feature) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($login['project_note']): ?>
      <p class="ib-lg-project-note"><?= ib_e($login['project_note']) ?></p>
      <?php endif; ?>
    </div>
  </section>

  <section class="ib-lg-panel" data-lg="panel">
    <form class="ib-lg-card" method="POST" action="<?= ib_e($login['action']) ?>" novalidate data-lg="form">
      <input type="hidden" name="<?= ib_e($login['csrf']['name']) ?>" value="<?= ib_e($login['csrf']['value']) ?>">

      <div class="ib-lg-heading">
        <p class="ib-lg-eyebrow">Administration Portal</p>
        <h2 class="ib-lg-heading-title">Admin Login</h2>
        <p class="ib-lg-heading-copy">Sign in to access the InteliBin administration panel.</p>
      </div>

      <div class="ib-lg-alert<?= $ibLgHasError ? '' : ' ib-lg-alert-hidden' ?>" role="alert" data-lg="error-box">
        <?= ib_e($login['error'] ?? '') ?>
      </div>

      <div class="ib-lg-field-group" data-lg="user-group">
        <label class="ib-lg-label" for="ib-lg-user"><?= ib_e($login['user_label']) ?></label>
        <div class="ib-lg-input-wrap">
          <span class="ib-lg-input-icon" aria-hidden="true">ID</span>
          <input class="ib-lg-field" id="ib-lg-user" name="<?= ib_e($login['fields']['user']) ?>" type="text" value="<?= ib_e($login['username']) ?>" placeholder="Enter email or username" autocomplete="username" data-lg="user">
        </div>
        <p class="ib-lg-inline-error" id="ib-lg-user-error" data-lg="user-error"></p>
      </div>

      <div class="ib-lg-field-group" data-lg="pass-group">
        <label class="ib-lg-label" for="ib-lg-pass">Password</label>
        <div class="ib-lg-input-wrap">
          <span class="ib-lg-input-icon" aria-hidden="true">KEY</span>
          <input class="ib-lg-field" id="ib-lg-pass" name="<?= ib_e($login['fields']['pass']) ?>" type="password" placeholder="Enter your password" autocomplete="current-password" data-lg="pass">
          <button class="ib-lg-ghost-button" type="button" aria-label="Show password" aria-pressed="false" data-lg="toggle">Show</button>
        </div>
        <p class="ib-lg-inline-error" id="ib-lg-pass-error" data-lg="pass-error"></p>
        <p class="ib-lg-caps" data-lg="caps">Caps Lock is on</p>
      </div>

      <?php if ($login['show_remember'] || $login['forgot_href']): ?>
      <div class="ib-lg-options">
        <?php if ($login['show_remember']): ?>
        <label class="ib-lg-check">
          <input class="ib-lg-check-input" type="checkbox" name="<?= ib_e($login['fields']['remember']) ?>" value="1">
          <span class="ib-lg-check-box" aria-hidden="true"></span>
          <span class="ib-lg-check-text">Keep me signed in</span>
        </label>
        <?php endif; ?>
        <?php if ($login['forgot_href']): ?>
        <a class="ib-lg-link" href="<?= ib_e($login['forgot_href']) ?>">Forgot password?</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <button class="ib-lg-submit" type="submit" data-lg="submit">
        <span class="ib-lg-submit-text" data-lg="submit-text">Sign in</span>
        <span class="ib-lg-submit-busy" data-lg="submit-busy">Signing in...</span>
      </button>

      <?php if ($login['help_text']): ?>
      <p class="ib-lg-help"><?= ib_e($login['help_text']) ?></p>
      <?php endif; ?>

      <?php if ($login['project_note']): ?>
      <p class="ib-lg-mobile-note"><?= ib_e($login['project_note']) ?></p>
      <?php endif; ?>
    </form>
  </section>
</main>
