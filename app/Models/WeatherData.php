<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'source_code',
        'temperature',
        'date',
    ];

    public function location()
    {
      return $this->belongsTo(Location::class);
    }
}
