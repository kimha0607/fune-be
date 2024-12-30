<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    protected $fillable = ['user_id', 'name', 'dob', 'gender'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


