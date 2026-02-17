<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    protected $fillable = ['name', 'code', 'base_latitude', 'base_longitude'];

    protected function casts(): array
    {
        return [
            'base_latitude' => 'float',
            'base_longitude' => 'float',
        ];
    }

    public function hasBaseLocation(): bool
    {
        return $this->base_latitude !== null && $this->base_longitude !== null;
    }
}
