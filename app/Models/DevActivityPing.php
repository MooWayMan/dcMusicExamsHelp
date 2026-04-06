<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevActivityPing extends Model
{
    public $timestamps = false;

    protected $fillable = ['pinged_at', 'path'];

    protected function casts(): array
    {
        return [
            'pinged_at' => 'datetime',
        ];
    }
}
