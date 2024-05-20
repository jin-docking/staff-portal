<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
