<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingModel extends Model
{
    protected $table = 'bookings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'room_id',
        'date',
        'start_minute',
        'duration_minutes',
        'username',
    ];

    protected $casts = [
        'start_minute'     => 'integer',
        'duration_minutes' => 'integer',
        'date'             => 'string',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(RoomModel::class, 'room_id', 'id');
    }
}
