<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;


    protected $fillable = [
        'team_name',
        'description',
        'project_manager_id',
        'frontend_team_lead_id',
        'backend_team_lead_id',
    ];

    public function user()
    {
        return $this->belongsToMany(User::class);
    }
}
