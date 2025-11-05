<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    protected $fillable = [
        'nama', 'nim', 'program_studi_id', 'email', 'alamat', 'tanggal_lahir', 'jenis_kelamin', 'nomor_hp', 'golongan_darah', 'tempat_lahir'
    ];
}
