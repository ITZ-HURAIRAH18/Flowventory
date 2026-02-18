<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'manager_id'];

    // Manager relationship
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Branch users
    public function users()
    {
        return $this->hasMany(User::class, 'branch_id');
    }
}