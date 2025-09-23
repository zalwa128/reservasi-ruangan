<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'nama_ruangan'        => 'Ruang Rapat 1',
                'capacity'    => 20,
                'deskripsi' => 'Ruang rapat untuk meeting kecil',
            ],
            [
                'nama_ruangan'        => 'Ruang Rapat 2',
                'capacity'    => 25,
                'deskripsi' => 'Ruang rapat dengan proyektor',
            ],
            [
                'nama_ruangan'        => 'Aula Utama',
                'capacity'    => 100,
                'deskripsi' => 'Ruang besar untuk acara dan seminar',
            ],
            [
                'nama_ruangan'        => 'Ruang Training',
                'capacity'    => 40,
                'deskripsi' => 'Ruang pelatihan karyawan',
            ],
            [
                'nama_ruangan'        => 'Ruang Diskusi A',
                'capacity'    => 10,
                'deskripsi' => 'Ruang kecil untuk diskusi tim',
            ],
            [
                'nama_ruangan'        => 'Ruang Diskusi B',
                'capacity'    => 12,
                'deskripsi' => 'Ruang diskusi dengan papan tulis',
            ],
            [
                'nama_ruangan'        => 'Ruang Presentasi',
                'capacity'    => 50,
                'deskripsi' => 'Ruang untuk presentasi dan demo produk',
            ],
            [
                'nama_ruangan'        => 'Ruang Kreatif',
                'capacity'    => 15,
                'deskripsi' => 'Ruang dengan desain santai untuk brainstorming',
            ],
            [
                'nama_ruangan'        => 'Ruang IT Support',
                'capacity'    => 8,
                'deskripsi' => 'Ruang kerja tim IT support',
            ],
            [
                'nama_ruangan'        => 'Ruang Manajemen',
                'capacity'    => 30,
                'deskripsi' => 'Ruang meeting manajemen perusahaan',
            ],
        ];

        foreach ($rooms as $room) {
            Room::create([
                'nama_ruangan'        => $room['nama_ruangan'],
                'capacity'    => $room['capacity'],
                'deskripsi' => $room['deskripsi'],
                'status'      => 'non-aktif', // default
            ]);
        }
    }
}
