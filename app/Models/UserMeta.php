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
        'contact_no',
        'gender',
        'join_date',
        'date_of_birth',
        'work_title',
        'father',
        'mother',
        'marital_status',
        'pincode',
        'aadhar',
        'pan',
        'profile_pic',
    ];

    protected $casts = [
        'join_date' => 'datetime',
        'date_of_birth' =>'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getWorkExperienceAttribute()
    {
        $joinDate = $this->join_date;
        $currentDate = now();
        $diff = $joinDate->diff($currentDate);

        $years = $diff->y;
        $months = $diff->m;

        return [
            'years' => $years,
            'months' => $months,
        ];

    }
}
