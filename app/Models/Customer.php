<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $fillable = ['nama', 'jenis', 'no_hp', 'alamat'];
    public $timestamps = false;
}
