<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address',
        'gender',
        'designation',
        'join_date',
        'date_of_birth',
        'father',
        'mother',
        'pincode',
        'aadhar',
        'pan',
        'profile_pic',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
