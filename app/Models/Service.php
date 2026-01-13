<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $fillable = ['floor_id', 'name', 'code', 'start_number', 'last_number'];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
