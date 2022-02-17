<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'food_id', 'user_id', 'quantity', 'total', 'status' . 'payment_url',
    ];

    // untuk membuat relasi food dan user
    public function food()
    {
        return $this->hasOne(Food::class, 'id', 'food_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    //asesor -> mengubah field yang ada di db dan dikeluarkan sesuai keinginan kita
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    //Update
    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }
}
