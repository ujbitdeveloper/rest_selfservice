<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DeviceRegister extends BaseCommand
{
    protected $group       = 'Device';
    protected $name        = 'device:register';
    protected $description = 'Daftarkan alat IoT baru (atau rotate secret) dan tampilkan secret sekali saja.';
    protected $usage       = 'device:register <serial_hardware> [serial_hardware ...] [--rotate]';
    protected $arguments   = ['serial_hardware' => 'Satu atau lebih serial hardware alat'];
    protected $options     = ['--rotate' => 'Buat secret baru untuk alat yang sudah terdaftar (refresh token lama dicabut)'];

    public function run(array $params)
    {
        if ($params === []) {
            $params = [CLI::prompt('serial_hardware')];
        }

        $rotate = CLI::getOption('rotate') !== null;
        $db     = db_connect();

        CLI::write('serial_hardware  device_secret (simpan sekarang, tidak bisa dilihat lagi)', 'yellow');

        foreach ($params as $serial) {
            $serial = trim($serial);
            if ($serial === '') {
                continue;
            }

            $exists = $db->table('device_credentials')->where('serial_hardware', $serial)->countAllResults() > 0;

            if ($exists && ! $rotate) {
                CLI::error("$serial sudah terdaftar (pakai --rotate untuk membuat secret baru)");
                continue;
            }

            $secret = bin2hex(random_bytes(24)); // 48 karakter hex
            $hash   = password_hash($secret, PASSWORD_BCRYPT);

            if ($exists) {
                $db->table('device_credentials')->where('serial_hardware', $serial)->update([
                    'secret_hash'       => $hash,
                    'active'            => true,
                    'secret_rotated_at' => date('Y-m-d H:i:s'),
                ]);
                $db->table('refresh_tokens')->where('serial_hardware', $serial)->update(['revoked' => true]);
            } else {
                $db->table('device_credentials')->insert([
                    'serial_hardware' => $serial,
                    'secret_hash'     => $hash,
                ]);
            }

            CLI::write("$serial  $secret", 'green');
        }
    }
}
