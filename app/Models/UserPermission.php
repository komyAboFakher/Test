<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;
    Protected $table='user_permissions';

    protected $fillable=[
        'user_id',
        'permission_id',
    ];

    public function User(){
        return $this->belongsToMany(User::class);
    }
    public function Permission(){
        return $this->belongsTomany(Permission::class);
    }

    
}
