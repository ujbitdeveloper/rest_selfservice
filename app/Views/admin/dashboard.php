<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= esc($csrf) ?>">
<title>Dashboard Perangkat</title>
<style>
:root{--bg:#f4f5f7;--card:#fff;--fg:#1b1f24;--muted:#6b7280;--line:#e2e5ea;--accent:#2563eb;--ok:#16a34a;--off:#9ca3af;--bad:#dc2626}
@media (prefers-color-scheme:dark){:root{--bg:#0f1216;--card:#181c22;--fg:#e8eaed;--muted:#8b93a1;--line:#2a2f37;--accent:#5b8def;--ok:#4ade80;--off:#4b5563;--bad:#f87171}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--fg);font:14px system-ui,sans-serif}
header{display:flex;justify-content:space-between;align-items:center;padding:14px 22px;background:var(--card);border-bottom:1px solid var(--line)}
h1{font-size:17px;margin:0}main{max-width:1150px;margin:0 auto;padding:22px}
.btn{padding:7px 12px;border:1px solid var(--line);border-radius:8px;background:transparent;color:var(--fg);cursor:pointer;font:inherit}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:14px}
.stat b{display:block;font-size:26px;margin-top:2px}.stat span{color:var(--muted);font-size:12px}
.bar{display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap}
.bar input,.bar select{padding:8px 10px;border:1px solid var(--line);border-radius:8px;background:var(--card);color:var(--fg);font:inherit}
.bar input{flex:1;min-width:200px}
.wrap{background:var(--card);border:1px solid var(--line);border-radius:10px;overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:760px}
th,td{text-align:left;padding:10px 14px;border-bottom:1px solid var(--line);white-space:nowrap}
th{font-size:12px;color:var(--muted);font-weight:600}tr:last-child td{border-bottom:0}
.mono{font-family:ui-monospace,monospace;font-size:13px}.muted{color:var(--muted)}
.pill{padding:2px 8px;border-radius:99px;font-size:12px;border:1px solid var(--line)}
.on{color:var(--ok)}.offc{color:var(--muted)}
.sw{position:relative;display:inline-block;width:40px;height:22px}.sw input{opacity:0;width:0;height:0}
.sw i{position:absolute;inset:0;background:var(--off);border-radius:22px;cursor:pointer;transition:.15s}
.sw i:before{content:"";position:absolute;width:16px;height:16px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.15s}
.sw input:checked+i{background:var(--ok)}.sw input:checked+i:before{transform:translateX(18px)}
.empty{padding:30px;text-align:center;color:var(--muted)}
</style>
</head>
<body>
<header><h1>Dashboard Perangkat IoT</h1><button class="btn" id="logout">Keluar</button></header>
<main>
  <div class="stats">
    <div class="stat"><span>Total terdaftar</span><b id="s-all">0</b></div>
    <div class="stat"><span>Aktif</span><b id="s-on" class="on">0</b></div>
    <div class="stat"><span>Nonaktif</span><b id="s-off" class="offc">0</b></div>
  </div>
  <div class="bar">
    <input id="q" placeholder="Cari serial, site id, atau nama site...">
    <select id="f"><option value="all">Semua status</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select>
    <button class="btn" id="reload">Muat ulang</button>
  </div>
  <div class="wrap"><table>
    <thead><tr><th>Serial</th><th>Site</th><th>OS</th><th>Pump</th><th>versi</th><th>terakhir di update</th><th>Terdaftar</th><th>Status</th></tr></thead>
    <tbody id="rows"></tbody>
  </table></div>
</main>
<script>
const csrf = document.querySelector('meta[name=csrf]').content;
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = t => t ? new Date(t.replace(' ', 'T') + 'Z').toLocaleString('id-ID') : '-';
let devices = [];

async function load() {
  const r = await fetch('/admin/devices');
  if (r.status === 401) return location.reload();
  devices = await r.json();
  render();
}

function render() {
  const q = $('#q').value.trim().toLowerCase(), f = $('#f').value;
  const list = devices.filter(d =>
    (f === 'all' || (f === 'on') === d.is_enable) &&
    [d.serial_hardware, d.site_id, d.site_name].join(' ').toLowerCase().includes(q));
  $('#s-all').textContent = devices.length;
  $('#s-on').textContent = devices.filter(d => d.is_enable).length;
  $('#s-off').textContent = devices.filter(d => !d.is_enable).length;
  $('#rows').innerHTML = list.length ? list.map(d => `
    <tr>
      <td class="mono">${esc(d.serial_hardware)}${d.confirmed ? '' : ' <span class="pill" title="Belum terkonfirmasi">baru</span>'}</td>
      <td>${d.site_name ? esc(d.site_name) + ' <span class="muted">(' + esc(d.site_id) + ')</span>' : '<span class="muted">-</span>'}</td>
      <td>${esc(d.device_os) || '-'}</td>
      <td>${d.pump_count}</td>
      <td>${d.version_hardware}</td>
      <td>${fmt(d.last_update)}</td>
      <td>${fmt(d.registered_at)}</td>
      <td><label class="sw"><input type="checkbox" data-s="${esc(d.serial_hardware)}" ${d.is_enable ? 'checked' : ''}><i></i></label>
          <span class="${d.is_enable ? 'on' : 'offc'}">${d.is_enable ? 'Aktif' : 'Nonaktif'}</span></td>
    </tr>`).join('') : '<tr><td colspan="7" class="empty">Tidak ada perangkat</td></tr>';
}

$('#rows').addEventListener('change', async e => {
  const cb = e.target, serial = cb.dataset.s, want = cb.checked;
  if (!want && !confirm('Nonaktifkan ' + serial + '? Alat ini akan ditolak saat request berikutnya.')) { cb.checked = true; return; }
  cb.disabled = true;
  const r = await fetch('/admin/devices/toggle', {
    method: 'POST',
    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
    body: JSON.stringify({serial_hardware: serial, is_enable: want})
  });
  if (r.ok) { devices.find(d => d.serial_hardware === serial).is_enable = want; }
  else { alert('Gagal mengubah status (' + r.status + ')'); }
  render();
});

$('#q').addEventListener('input', render);
$('#f').addEventListener('change', render);
$('#reload').addEventListener('click', load);
$('#logout').addEventListener('click', async () => {
  await fetch('/admin/logout', {method: 'POST', headers: {'X-CSRF-Token': csrf}});
  location.reload();
});
load(); setInterval(load, 30000);
</script>
</body>
</html>
