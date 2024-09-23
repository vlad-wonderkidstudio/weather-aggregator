<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Adds a new location to the database if does not exists and returns its record. If exists just returns its record.
     * @param string $name
     * @return Location|null
     */
    public static function addLocation(string $name): Location|null
    {
      try {
        return self::create([
          'name' => $name
        ]);
      } catch (\Exception $e) {
        Log::error($e->getMessage());
        return null;
      }
    }

    /**
     * Checks if a location already exists in the database.
     * @param string $name
     * @return bool
     */
    public static function locationExists(string $name): bool
    {
      return self::where('name', $name)->exists();
    }

    /**
     * Returns all locations in the database.
     * @return array
     */
    public static function getAllLocationsNames(): array
    {
      return self::all()->pluck('name')->toArray();
    }

    /**
     * Returns all locations in the database as an array.
     * @return array
     */
    public static function getAllLocations(): array
    {
      return self::all()->toArray();
    }
}
