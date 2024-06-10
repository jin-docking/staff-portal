<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' =>'datetime',
        'complimentary_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'created_by',
        'title',
        'category',
        'start_date',
        'end_date',
        'complimentary_date',
        'description',
        'approval_status',
        'leave_count',
        'loss_of_pay',
        'leave_session'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
