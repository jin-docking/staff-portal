<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by',
        'title',
        'category',
        'start_date',
        'end_date',
        'description',
        'approval_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
