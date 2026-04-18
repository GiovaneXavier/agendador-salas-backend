<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomModel extends Model
{
    protected $table = 'rooms';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'color_bg',
        'color_accent',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingModel::class, 'room_id', 'id');
    }
}
