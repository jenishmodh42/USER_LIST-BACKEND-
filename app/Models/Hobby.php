<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Hobby extends Model
{
    protected $fillable = [
        'name',
    ];
    
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
