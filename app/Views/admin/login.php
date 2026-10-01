<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Admin</title>
<style>
:root{--bg:#f4f5f7;--card:#fff;--fg:#1b1f24;--muted:#6b7280;--line:#e2e5ea;--accent:#2563eb}
@media (prefers-color-scheme:dark){:root{--bg:#0f1216;--card:#181c22;--fg:#e8eaed;--muted:#8b93a1;--line:#2a2f37;--accent:#5b8def}}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);color:var(--fg);font:15px system-ui,sans-serif}
form{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:28px;width:min(340px,92vw)}
h1{font-size:18px;margin:0 0 18px}label{display:block;font-size:13px;color:var(--muted);margin:12px 0 4px}
input{width:100%;padding:10px;border:1px solid var(--line);border-radius:8px;background:var(--bg);color:var(--fg);font:inherit}
button{margin-top:18px;width:100%;padding:10px;border:0;border-radius:8px;background:var(--accent);color:#fff;font:inherit;cursor:pointer}
.err{color:#dc2626;font-size:13px;margin-top:12px}
</style>
</head>
<body>
<form method="post" action="/admin/login">
  <h1>Dashboard Perangkat IoT</h1>
  <label>Username</label><input name="username" autocomplete="username" required autofocus>
  <label>Password</label><input name="password" type="password" autocomplete="current-password" required>
  <button type="submit">Masuk</button>
  <?php if (! empty($error)): ?><div class="err"><?= esc($error) ?></div><?php endif; ?>
</form>
</body>
</html>
