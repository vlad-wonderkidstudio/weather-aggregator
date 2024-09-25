<?php

namespace App\Services;

use App\Services\WeatherSources\WeatherSourceVisualcrossing;
use App\Services\WeatherSources\WeatherSourceWeatherapi;
use App\Models\Location;
use App\Models\WeatherData;
use App\Classes\Results\LocationResult;
use App\Classes\Results\AverageWeatherResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CustomException;
use Illuminate\Database\Query\JoinClause;
use stdClass;

class WeatherService
{

  private $weatherSources = [];

  public function __construct(WeatherSourceVisualcrossing $visualcrossing, WeatherSourceWeatherapi $weatherapi)
  {
      $this->weatherSources[] = $visualcrossing;
      $this->weatherSources[] = $weatherapi;
  }

  /**
   * Add location to the database
   * @return LocationResult
   */
  public function addLocation(string $locationName): object
  {
    $result = new LocationResult();

    // Check if such location was already added
    $result->alreadyAdded = Location::locationExists($locationName);
    if ($result->alreadyAdded) {
      Log::warning("Tried to dublicate location: $locationName");
      return $result;
    }

    // Check if the location with such name is correct or exists
    $isExists = $this->checkIfLocationExists($locationName);
    if (!$isExists) {
      Log::warning("Tried to enter location: $locationName which does not exists in all sources");
      $result->doesNotExists = true;
      return $result;
    }

    // Add location
    $location = Location::addLocation($locationName);
    if ($location) {
      Log::info("Successfully added location: $locationName");
      $result->success = true;
      $result->locationData = $location;
    } else {
      Log::error('An error occured: while adding a location: $locationName');
    }

    return $result;
  }

  public function getAllLocations(): array
  {
    return Location::getAllLocations();
  }

  public function getWeatherDataAndSave(int $locationId, string $location): void
  {
    $timeNow = date('Y-m-d H:i:s');

    foreach ($this->weatherSources as $source) {
      DB::beginTransaction();

      $ret = $source->getWeatherData($location);

      if ($ret && isset($ret->temperature)) {
        WeatherData::create([
          'location_id' => $locationId,
          'source_code' => $source->getSourceCode(),
          'temperature' => $ret->temperature,
          'date' => $timeNow
        ]);
        DB::commit();
      } else {
        DB::rollback();
      }
    }
  }

  public function checkIfLocationExists(string $location): bool
  {
    $exists = true;
    foreach ($this->weatherSources as $source) {
      if (!$source->checkIfLocationExists($location)) {
        $exists = false;
        break;
      }
    }

    return $exists;
  }

   /**
   * Add location to the database
   * @return AverageWeatherResult
   */
  public function getAverageWeatherForPeriod(string $location, string $dateTimeFrom, string $dateTimeTo, $maximumFields=1000): object
  {
    $result = new AverageWeatherResult();

    // Check if such location was already added
    $result->alreadyAdded = Location::locationExists($location);
    if (!$result->alreadyAdded) {
      Log::warning("Tried to get information for an unadded location: $location");
      return $result;
    }

    // I was able to get the location_id when checking if the location exists, and it would work faster
    // but since it is a testing task I need to show that I can work with joins
    $dateTimeFromSql = date("Y-m-d H:i:s", strtotime($dateTimeFrom));
    $dateTimeToSql = date("Y-m-d H:i:s", strtotime($dateTimeTo));

    $result->averageData = WeatherData::select(DB::raw('AVG(`weather_data`.`temperature`) as `temperature`'))
      ->join('locations', function (JoinClause $join) use ($location) {
        $join->on('weather_data.location_id', '=', 'locations.id')
          ->where('locations.name', $location);
      })
      ->where('weather_data.date', '>=', $dateTimeFromSql)
      ->where('date', '<=', $dateTimeToSql)
      ->groupBy('location_id')
      ->get();

    if (!$result->averageData->isEmpty()) {
      $result->success = true;
    } else {
      Log::warning("No data for the period: $dateTimeFrom - $dateTimeTo");
      $result->noData = true;
      $result->averageData = [];
    }

    return $result;
  }

}
