<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LetterSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('letters')->insert([
            [
                'user_id' => 1, // pastikan ada employee dengan id 1
                'letter_name' => 'Surat Cuti Tahunan',
                'status' => 'Pending',
                'path_content' => 'storage/letters/cuti-tahunan.pdf',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 1,
                'letter_name' => 'Surat Keterangan Kerja',
                'status' => 'Approved',
                'path_content' => 'storage/letters/keterangan-kerja.pdf',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 1,
                'letter_name' => 'Surat Izin Tidak Masuk',
                'status' => 'Rejected',
                'path_content' => 'storage/letters/izin.pdf',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}