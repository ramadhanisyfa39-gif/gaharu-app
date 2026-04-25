<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    protected $fillable = ['nama', 'no_hp', 'alamat'];
    public $timestamps = false;
}
