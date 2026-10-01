<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Data extends ResourceController
{
    protected $format = 'json';

    /**
     * POST /api/data  (Authorization: Bearer <access_token>)
     * Upsert berdasarkan serial_hardware:
     *  - belum ada  -> INSERT (created_at & last_update diisi)
     *  - sudah ada  -> semua field ditimpa, last_update diperbarui
     */
    public function create()
    {
        if (! preg_match('/^Bearer\s+(.+)$/i', $this->request->getHeaderLine('Authorization'), $m)) {
            return $this->failUnauthorized('Header Authorization Bearer tidak ditemukan');
        }

        try {
            $payload = JWT::decode($m[1], new Key(env('JWT_SECRET'), 'HS256'));
        } catch (\Throwable $e) {
            return $this->failUnauthorized('Token tidak valid atau kedaluwarsa');
        }

        if (($payload->typ ?? '') !== 'access') {
            return $this->failUnauthorized('Gunakan access token, bukan refresh token');
        }

        $tokenSerial = (string) ($payload->sub ?? '');

        // Alat harus masih aktif (supaya alat yang dinonaktifkan langsung ditolak)
        $active = db_connect()->table('device_credentials')
            ->where('serial_hardware', $tokenSerial)
            ->where('active', true)
            ->countAllResults();

        if (! $active) {
            return $this->respond([
                'status'    => 403,
                'message'   => 'Alat tidak terdaftar atau dinonaktifkan',
                'is_enable' => false,
            ], 403);
        }

        $body = $this->request->getJSON(true);
        if (! is_array($body)) {
            return $this->failValidationErrors('Body harus berupa JSON object');
        }

        if (! $this->validateData($body, [
            'serial_hardware'      => 'required|max_length[100]',
            'device_os'            => 'permit_empty|max_length[50]',
            'device_product_model' => 'permit_empty|max_length[100]',
            'device_board_model'   => 'permit_empty|max_length[100]',
            'os_version'           => 'permit_empty|max_length[50]',
            'device_manufacturer'  => 'permit_empty|max_length[100]',
            'site_id'              => 'permit_empty|max_length[50]',
            'site_name'            => 'permit_empty|max_length[200]',
            'site_address'         => 'permit_empty',
            'serial_number_ss'     => 'permit_empty|max_length[100]',
            'version_hardware'     => 'permit_empty|max_length[100]',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Serial di body wajib sama dengan identitas di token
        if (! hash_equals($tokenSerial, (string) $body['serial_hardware'])) {
            return $this->failForbidden('serial_hardware tidak sesuai dengan token');
        }

        $pumps = $body['pumps'] ?? [];
        if (! is_array($pumps)) {
            return $this->failValidationErrors(['pumps' => 'pumps harus berupa array']);
        }

        $sql = <<<'SQL'
INSERT INTO devices (
    serial_hardware, device_os, device_product_model, device_board_model,
    os_version, device_manufacturer, pumps, site_id, site_name,
    site_address, serial_number_ss, created_at, last_update, version_hardware
) VALUES (?, ?, ?, ?, ?, ?, CAST(? AS jsonb), ?, ?, ?, ?, NOW(), NOW(),?)
ON CONFLICT (serial_hardware) DO UPDATE SET
    device_os            = EXCLUDED.device_os,
    device_product_model = EXCLUDED.device_product_model,
    device_board_model   = EXCLUDED.device_board_model,
    os_version           = EXCLUDED.os_version,
    device_manufacturer  = EXCLUDED.device_manufacturer,
    pumps                = EXCLUDED.pumps,
    site_id              = EXCLUDED.site_id,
    site_name            = EXCLUDED.site_name,
    site_address         = EXCLUDED.site_address,
    serial_number_ss     = EXCLUDED.serial_number_ss,
    last_update          = NOW(),
    version_hardware     = EXCLUDED.version_hardware

RETURNING id, (xmax = 0) AS inserted, created_at, last_update
SQL;

        $row = db_connect()->query($sql, [
            $body['serial_hardware'],
            $body['device_os'] ?? null,
            $body['device_product_model'] ?? null,
            $body['device_board_model'] ?? null,
            $body['os_version'] ?? null,
            $body['device_manufacturer'] ?? null,
            json_encode($pumps),
            $body['site_id'] ?? null,
            $body['site_name'] ?? null,
            $body['site_address'] ?? null,
            $body['serial_number_ss'] ?? null,
            $body['version_hardware'] ?? null,
        ])->getRow();

        $inserted = in_array($row->inserted, [true, 't', 'true', 1, '1'], true);

        $result = [
            'message'         => $inserted ? 'Data baru berhasil disimpan' : 'Data lama berhasil ditimpa',
            'is_enable'       => true,
            'action'          => $inserted ? 'inserted' : 'updated',
            'id'              => (int) $row->id,
            'serial_hardware' => $body['serial_hardware'],
            'created_at'      => $row->created_at,
            'last_update'     => $row->last_update,
        ];

        return $inserted ? $this->respondCreated($result) : $this->respond($result);
    }
}
