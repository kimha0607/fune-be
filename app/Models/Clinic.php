<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address'];

    public function doctors()
    {
        return $this->belongsToMany(User::class, 'doctor_clinic', 'clinic_id', 'doctor_id');
    }
}