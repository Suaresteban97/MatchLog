<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutionPlatform extends Model
{
    protected $fillable = ['name', 'icon_url'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_execution_platforms')
            ->withPivot('account_identifier', 'created_at')
            ->withTimestamps();
    }
}
