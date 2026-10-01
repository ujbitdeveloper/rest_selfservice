<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Admin extends Controller
{
    public function index()
    {
        if (! session()->get('admin')) {
            return view('admin/login', ['error' => session()->getFlashdata('error')]);
        }

        return view('admin/dashboard', ['csrf' => (string) session()->get('csrf')]);
    }

    public function login()
    {
        $user = (string) $this->request->getPost('username');
        $pass = (string) $this->request->getPost('password');
        $u    = (string) env('ADMIN_USERNAME', '');
        $p    = (string) env('ADMIN_PASSWORD', '');

        if ($u !== '' && $p !== '' && hash_equals($u, $user) && hash_equals($p, $pass)) {
            session()->regenerate();
            session()->set(['admin' => true, 'csrf' => bin2hex(random_bytes(16))]);
        } else {
            usleep(500000); // perlambat brute force
            session()->setFlashdata('error', 'Username atau password salah');
        }

        return redirect()->to(base_url('admin'));
    }

    public function logout()
    {
        if ($r = $this->guard(true)) {
            return $r;
        }
        session()->destroy();

        return $this->response->setJSON(['ok' => true]);
    }

    /** GET /admin/devices */
    public function devices()
    {
        if ($r = $this->guard(false)) {
            return $r;
        }

        $rows = db_connect()->query(
            'SELECT c.serial_hardware, c.active, c.confirmed, c.created_at AS registered_at,
                    d.site_id, d.site_name, d.device_os, d.os_version, d.pumps, d.last_update, d.version_hardware
               FROM device_credentials c
               LEFT JOIN devices d ON d.serial_hardware = c.serial_hardware
              ORDER BY c.created_at DESC'
        )->getResultArray();

        $out = array_map(static function ($r) {
            $pumps = json_decode($r['pumps'] ?? '[]', true);

            return [
                'serial_hardware' => $r['serial_hardware'],
                'is_enable'       => in_array($r['active'], [true, 't', 'true', 1, '1'], true),
                'confirmed'       => in_array($r['confirmed'], [true, 't', 'true', 1, '1'], true),
                'registered_at'   => $r['registered_at'],
                'site_id'         => $r['site_id'],
                'site_name'       => $r['site_name'],
                'version_hardware'=> $r['version_hardware'],
                'device_os'       => trim(($r['device_os'] ?? '') . ' ' . ($r['os_version'] ?? '')),
                'pump_count'      => is_array($pumps) ? count($pumps) : 0,
                'last_update'     => $r['last_update'],
            ];
        }, $rows);

        return $this->response->setJSON($out);
    }

    /** POST /admin/devices/toggle  {serial_hardware, is_enable} */
    public function toggle()
    {
        if ($r = $this->guard(true)) {
            return $r;
        }

        $body   = $this->request->getJSON(true) ?? [];
        $serial = (string) ($body['serial_hardware'] ?? '');
        $enable = filter_var($body['is_enable'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($serial === '' || $enable === null) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'serial_hardware dan is_enable wajib diisi']);
        }

        $db = db_connect();
        $db->table('device_credentials')->where('serial_hardware', $serial)->update(['active' => $enable]);

        if ($db->affectedRows() === 0) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Alat tidak ditemukan']);
        }

        if (! $enable) {
            // Nonaktif -> refresh token yang beredar ikut dicabut
            $db->table('refresh_tokens')->where('serial_hardware', $serial)->update(['revoked' => true]);
        }

        return $this->response->setJSON(['serial_hardware' => $serial, 'is_enable' => $enable]);
    }

    private function guard(bool $checkCsrf)
    {
        if (! session()->get('admin')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Belum login']);
        }

        if ($checkCsrf && ! hash_equals((string) session()->get('csrf'), $this->request->getHeaderLine('X-CSRF-Token'))) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'CSRF token tidak valid']);
        }

        return null;
    }
}
