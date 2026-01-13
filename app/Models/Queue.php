<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Queue extends Model
{
    protected $fillable = [
        'service_id',
        'floor_id',
        'counter_id',
        'number',
        'full_number',
        'status',
        'called_at',
        'served_at',
        'created_at',
        'photo_path',
        'barcode_token',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    protected $appends = ['photo_url'];

    /**
     * Get the full URL for the photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        return asset('storage/' . $this->photo_path);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
