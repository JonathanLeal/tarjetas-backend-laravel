<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarjeta extends Model
{
    //use HasFactory;
    protected $table = "tarjeta";
    protected $primaryKey = "id";
    public $timestamps = false;
}
