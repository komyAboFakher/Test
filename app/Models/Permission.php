<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    Protected $fillabe=[
        'Permission',
        'description',
    ];
   public function userPermission(){
    return $this->hasMany(UserPermission::class);
   }
}
