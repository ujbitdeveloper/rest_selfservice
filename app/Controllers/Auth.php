<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth extends ResourceController
{
    protected $format = 'json';

    /**
     * POST /api/token
     *  a) Login    : {serial_hardware, device_secret}
     *  b) Enroll   : {serial_hardware, enrollment_key}  -> alat didaftarkan otomatis,
     *                response memuat device_secret (SEKALI SAJA, wajib disimpan alat)
     */
    public function token()
    {
        $body   = $this->request->getJSON(true) ?? [];
        $serial = trim((string) ($body['serial_hardware'] ?? ''));
        $secret = (string) ($body['device_secret'] ?? '');
        $enroll = (string) ($body['enrollment_key'] ?? '');

        if ($serial === '' || ($secret === '' && $enroll === '')) {
            return $this->failValidationErrors('serial_hardware dan (device_secret atau enrollment_key) wajib diisi');
        }

        return $secret !== '' ? $this->login($serial, $secret) : $this->enroll($serial, $enroll);
    }

    private function login(string $serial, string $secret)
    {
        $db   = db_connect();
        $cred = $db->table('device_credentials')
            ->where('serial_hardware', $serial)
            ->get()->getRow();

        if (! $cred || ! $this->isTrue($cred->active)) {
            return $this->deny(403, 'Alat tidak terdaftar atau dinonaktifkan', false);
        }

        if (! password_verify($secret, $cred->secret_hash)) {
            return $this->deny(401, 'Kredensial alat tidak valid', true);
        }

        // Secret terbukti dipegang alat -> tutup jendela re-enroll
        $db->table('device_credentials')->where('serial_hardware', $serial)->update(['confirmed' => true]);

        return $this->respond($this->issueTokens($serial));
    }

    private function enroll(string $serial, string $enrollKey)
    {
        $configured = (string) env('ENROLLMENT_KEY', '');

        if ($configured === '' || ! hash_equals($configured, $enrollKey)) {
            return $this->deny(401, 'Enrollment key tidak valid atau enrollment dinonaktifkan', false);
        }

        $db   = db_connect();
        $cred = $db->table('device_credentials')->where('serial_hardware', $serial)->get()->getRow();

        // Alat yang sudah terkonfirmasi / dinonaktifkan tidak boleh di-enroll ulang lewat enrollment key
        if ($cred && ! $this->isTrue($cred->active)) {
            return $this->deny(403, 'Alat dinonaktifkan', false);
        }

        if ($cred && $this->isTrue($cred->confirmed)) {
            return $this->deny(409, 'Alat sudah terdaftar. Gunakan device_secret (atau minta admin --rotate)', true);
        }

        $deviceSecret = bin2hex(random_bytes(24));
        $hash         = password_hash($deviceSecret, PASSWORD_BCRYPT);

        if ($cred) {
            // Enroll sebelumnya belum terkonfirmasi (mis. response hilang di jaringan) -> terbitkan secret baru
            $db->table('device_credentials')->where('serial_hardware', $serial)->update([
                'secret_hash'       => $hash,
                'secret_rotated_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('refresh_tokens')->where('serial_hardware', $serial)->update(['revoked' => true]);
        } else {
            try {
                $ok = $db->table('device_credentials')->insert([
                    'serial_hardware' => $serial,
                    'secret_hash'     => $hash,
                    'confirmed'       => false,
                ]);
            } catch (\Throwable $e) {
                $ok = false; // mis. dua request enroll bersamaan untuk serial yang sama
            }

            if (! $ok) {
                return $this->failResourceExists('Alat sedang/sudah didaftarkan. Coba lagi.');
            }
        }

        $out                  = $this->issueTokens($serial);
        $out['device_secret'] = $deviceSecret;
        $out['note']          = 'Simpan device_secret di alat. Nilai ini tidak akan ditampilkan lagi.';

        return $this->respondCreated($out);
    }

    /** POST /api/refresh  {refresh_token, device_secret} */
    public function refresh()
    {
        $body   = $this->request->getJSON(true) ?? [];
        $token  = (string) ($body['refresh_token'] ?? '');
        $secret = (string) ($body['device_secret'] ?? '');

        if ($token === '' || $secret === '') {
            return $this->failValidationErrors('refresh_token dan device_secret wajib diisi');
        }

        try {
            $payload = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
        } catch (\Throwable $e) {
            return $this->failUnauthorized('Refresh token tidak valid atau kedaluwarsa');
        }

        if (($payload->typ ?? '') !== 'refresh') {
            return $this->failUnauthorized('Bukan refresh token');
        }

        $db = db_connect();

        // Alat harus aktif dan device_secret harus cocok dengan pemilik refresh token
        $cred = $db->table('device_credentials')
            ->where('serial_hardware', $payload->sub)
            ->get()->getRow();

        $row = $db->table('refresh_tokens')
            ->where('jti', $payload->jti)
            ->where('serial_hardware', $payload->sub)
            ->where('revoked', false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()->getRow();

        if (! $cred || ! $this->isTrue($cred->active)) {
            return $this->deny(403, 'Alat tidak terdaftar atau dinonaktifkan', false);
        }

        if (! $row || ! password_verify($secret, $cred->secret_hash)) {
            return $this->deny(401, 'Refresh token/device_secret tidak valid atau sudah dipakai', true);
        }

        // Rotasi: refresh token lama langsung dicabut
        $db->table('refresh_tokens')->where('jti', $payload->jti)->update(['revoked' => true]);

        // Alat terbukti menerima response enroll -> tutup jendela re-enroll
        $db->table('device_credentials')->where('serial_hardware', $payload->sub)->update(['confirmed' => true]);

        return $this->respond($this->issueTokens((string) $payload->sub));
    }

    /** Response error yang selalu memuat is_enable */
    private function deny(int $code, string $message, bool $isEnable)
    {
        return $this->respond([
            'status'    => $code,
            'message'   => $message,
            'is_enable' => $isEnable,
        ], $code);
    }

    private function isTrue($v): bool
    {
        return in_array($v, [true, 't', 'true', 1, '1'], true);
    }

    private function issueTokens(string $serial): array
    {
        $secret     = env('JWT_SECRET');
        $accessTtl  = (int) env('JWT_ACCESS_TTL', 900);
        $refreshTtl = (int) env('JWT_REFRESH_TTL', 604800);
        $now        = time();
        $jti        = bin2hex(random_bytes(16));

        $access = JWT::encode([
            'iat' => $now,
            'exp' => $now + $accessTtl,
            'sub' => $serial,
            'typ' => 'access',
        ], $secret, 'HS256');

        $refresh = JWT::encode([
            'iat' => $now,
            'exp' => $now + $refreshTtl,
            'sub' => $serial,
            'typ' => 'refresh',
            'jti' => $jti,
        ], $secret, 'HS256');

        db_connect()->table('refresh_tokens')->insert([
            'jti'             => $jti,
            'serial_hardware' => $serial,
            'expires_at'      => date('Y-m-d H:i:s', $now + $refreshTtl),
        ]);

        return [
            'is_enable'     => true,
            'token_type'    => 'Bearer',
            'access_token'  => $access,
            'expires_in'    => $accessTtl,
            'refresh_token' => $refresh,
        ];
    }
}
