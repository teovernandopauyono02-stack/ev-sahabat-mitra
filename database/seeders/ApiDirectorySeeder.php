<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApiDirectory;
use Carbon\Carbon;

class ApiDirectorySeeder extends Seeder
{
    public function run(): void
    {
        $apis = [
            [
                'nama_perusahaan' => 'PLN EV Charging',
                'kode_perusahaan' => 'PLN-EV',
                'deskripsi'       => 'API monitoring stasiun pengisian PLN',
                'api_url'         => 'https://api.pln.co.id/ev/stations',
                'api_key'         => null,
                'metode'          => 'GET',
                'status'          => 'active',
                'kategori'        => 'charging',
            ],
            [
                'nama_perusahaan' => 'Shell Recharge',
                'kode_perusahaan' => 'SHELL-RC',
                'deskripsi'       => 'API data pengisian Shell Recharge Indonesia',
                'api_url'         => 'https://api.shell.com/ev/recharge',
                'api_key'         => null,
                'metode'          => 'GET',
                'status'          => 'active',
                'kategori'        => 'charging',
            ],
        ];

        foreach ($apis as $api) {
            ApiDirectory::updateOrCreate(
                ['kode_perusahaan' => $api['kode_perusahaan']],
                $api
            );
        }
    }
}
