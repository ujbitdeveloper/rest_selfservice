<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#edf7f5">
<title>Masuk Admin • Self Service</title>
<style>
:root{color-scheme:light;--ink:#243c49;--muted:#71828d;--teal:#208f87;--line:#dfe8eb}*{box-sizing:border-box}body{margin:0;min-height:100vh;min-height:100svh;background:#f6f8fa;color:var(--ink);font:14px 'Segoe UI',sans-serif;display:grid;place-items:center;padding:32px}button,input{font:inherit}.shell{width:min(1040px,100%);display:grid;grid-template-columns:1.05fr 1fr;border:1px solid var(--line);border-radius:24px;overflow:hidden;background:white;box-shadow:0 24px 80px #243c490b}.story{background:#eaf6f3;padding:44px;position:relative;overflow:hidden;display:flex;flex-direction:column;min-height:620px}.brand{display:flex;align-items:center;gap:12px;font-size:18px;font-weight:700;position:relative;z-index:1}.logo{background:var(--teal);color:white;display:grid;place-items:center;width:42px;height:42px;border-radius:12px;font-size:23px}.story h2{font-size:36px;line-height:1.2;letter-spacing:-1px;font-weight:650;margin:56px 0 18px;max-width:330px}.story p{line-height:1.8;color:#5c7b7b;max-width:340px;margin:0}.illustration{margin:34px 0 28px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px;transform:rotate(-4deg);position:relative}.mini{height:112px;background:#fff;border:1px solid #dcebe7;border-top:5px solid #83c9bf;border-radius:10px;padding:16px;box-shadow:0 10px 22px #315c5710}.mini:nth-child(2){border-top-color:#b5add4;background:#f5f2fb;transform:translateY(14px)}.mini:nth-child(3){border-top-color:#ddd49b;background:#fbf9ed}.line{height:6px;width:75%;border-radius:4px;background:#dbe6e5;margin-bottom:10px}.line.short{width:45%}.dot{width:25px;height:14px;border-radius:20px;background:#4ec68c;margin-top:18px}.story footer{margin-top:auto;font-size:12px;color:#698883}.formside{padding:60px 52px;display:flex;flex-direction:column;justify-content:center}.eyebrow{font-size:11px;color:var(--teal);letter-spacing:1.7px;font-weight:700;margin:0 0 14px}h1{font-size:30px;letter-spacing:-.7px;margin:0 0 12px}.subtitle{color:var(--muted);line-height:1.7;margin:0 0 30px}label{display:block;font-size:13px;font-weight:600;margin:0 0 9px}.field{margin-bottom:21px}input{width:100%;padding:13px 14px;border:1px solid var(--line);border-radius:9px;color:var(--ink);background:#fcfdfd;min-height:48px}input::placeholder{color:#98a5ad}input:focus{outline:3px solid #208f8720;border-color:var(--teal);background:white}.password{position:relative}.password input{padding-right:92px}.reveal{position:absolute;right:7px;top:7px;min-height:34px;background:transparent;color:#507977;border:0;border-radius:6px;padding:0 9px;font-size:12px;cursor:pointer}.submit{width:100%;border:0;border-radius:9px;min-height:49px;background:var(--teal);color:white;font-weight:600;margin-top:5px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:18px}.submit:hover{background:#177970}.submit:disabled{opacity:.65;cursor:wait}button:focus-visible{outline:3px solid #659eac;outline-offset:3px}.note{font-size:12px;color:var(--muted);line-height:1.7;text-align:center;margin:23px 0 0}.err{border:1px solid #efcfcc;border-radius:9px;background:#fff3f1;color:#a43c35;padding:12px 14px;line-height:1.6;margin-bottom:23px;font-size:13px}.mobilebrand{display:none}@media(max-width:780px){body{padding:22px}.shell{max-width:460px;grid-template-columns:1fr}.story{display:none}.formside{padding:38px 30px}.mobilebrand{display:flex;margin-bottom:38px}h1{font-size:27px}}@media(max-width:380px){body{padding:14px}.formside{padding:30px 22px}}
</style>
</head>
<body>
<main class="shell">
<section class="story" aria-label="Self Service">
<div class="brand"><span class="logo" aria-hidden="true">S</span>Self Service</div>
<h2>Semua perangkat.<br>Satu kendali.</h2>
<p>Pantau informasi perangkat dan kelola akses setiap site melalui dashboard yang terhubung.</p>
<div class="illustration" aria-hidden="true"><div class="mini"><div class="line"></div><div class="line short"></div><div class="dot"></div></div><div class="mini"><div class="line"></div><div class="line short"></div><div class="dot"></div></div><div class="mini"><div class="line"></div><div class="line short"></div><div class="dot"></div></div></div>
<footer>Manajemen perangkat IoT · Portal administrator</footer>
</section>
<section class="formside" aria-labelledby="login-title">
<div class="brand mobilebrand"><span class="logo" aria-hidden="true">S</span>Self Service</div>
<p class="eyebrow">PORTAL ADMINISTRATOR</p>
<h1 id="login-title">Selamat datang kembali</h1>
<p class="subtitle">Masuk untuk mengelola perangkat<br>dan akses site Anda.</p>
<?php if (! empty($error)): ?><div class="err" id="login-error" role="alert"><?= esc($error) ?></div><?php endif; ?>
<form method="post" action="<?= esc(rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') . '/login', 'attr') ?>"<?= ! empty($error) ? ' aria-describedby="login-error"' : '' ?>>
<div class="field"><label for="username">Username</label><input id="username" name="username" placeholder="Masukkan username" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus></div>
<div class="field"><label for="password">Password</label><div class="password"><input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required><button class="reveal" type="button" aria-controls="password" aria-pressed="false" hidden>Tampilkan</button></div></div>
<button class="submit" type="submit"><span>Masuk ke dashboard</span><span aria-hidden="true">→</span></button>
</form>
<p class="note">Gunakan akun administrator yang telah<br>disiapkan untuk mengakses dashboard.</p>
</section>
</main>
<script>
const reveal = document.querySelector('.reveal');
const password = document.querySelector('#password');
reveal.hidden = false;
reveal.addEventListener('click', () => {
  const visible = password.type === 'password';
  password.type = visible ? 'text' : 'password';
  reveal.textContent = visible ? 'Sembunyikan' : 'Tampilkan';
  reveal.setAttribute('aria-pressed', String(visible));
});
document.querySelector('form').addEventListener('submit', () => {
  const button = document.querySelector('.submit');
  button.disabled = true;
  button.querySelector('span').textContent = 'Memproses…';
});
window.addEventListener('pageshow', () => {
  const button = document.querySelector('.submit');
  button.disabled = false;
  button.querySelector('span').textContent = 'Masuk ke dashboard';
});
</script>
</body>
</html>
