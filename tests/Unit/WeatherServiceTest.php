<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WeatherService;
use App\Services\WeatherSources\WeatherSourceVisualcrossing;
use App\Services\WeatherSources\WeatherSourceWeatherapi;
use App\Models\Location;
use App\Classes\Results\LocationResult;
use App\Classes\Results\AverageWeatherResult;
use Mockery;
use App\Models\WeatherData;

class WeatherServiceTest extends TestCase
{
  public function test_add_location_already_added()
  {
    $visualcrossing = Mockery::mock(WeatherSourceVisualcrossing::class);
    $weatherapi = Mockery::mock(WeatherSourceWeatherapi::class);

    $locationMock = Mockery::mock('alias:' . Location::class);
    $locationMock->shouldReceive('locationExists')
      ->once()
      ->with('London')
      ->andReturn(true);

    $service = new WeatherService($visualcrossing, $weatherapi);

    $result = $service->addLocation('London');

    $this->assertInstanceOf(LocationResult::class, $result);
    $this->assertTrue($result->alreadyAdded);
    $this->assertFalse($result->success);
  }

  public function test_add_location_does_not_exist()
  {
    $visualcrossing = Mockery::mock(WeatherSourceVisualcrossing::class);
    $weatherapi = Mockery::mock(WeatherSourceWeatherapi::class);

    $locationMock = Mockery::mock('overload:' . Location::class);
    $locationMock->shouldReceive('locationExists')
    ->once()
      ->with('NonexistentLocation')
      ->andReturn(false);

    $service = Mockery::mock(WeatherService::class . '[checkIfLocationExists]', [$visualcrossing, $weatherapi]);
    $service->shouldReceive('checkIfLocationExists')
    ->once()
      ->with('NonexistentLocation')
      ->andReturn(false);

    $result = $service->addLocation('NonexistentLocation');

    $this->assertInstanceOf(LocationResult::class, $result);
    $this->assertFalse($result->alreadyAdded);
    $this->assertTrue($result->doesNotExists);
    $this->assertFalse($result->success);
  }

  public function test_check_if_location_exists()
  {
    $visualcrossing = Mockery::mock(WeatherSourceVisualcrossing::class);
    $weatherapi = Mockery::mock(WeatherSourceWeatherapi::class);

    $visualcrossing->shouldReceive('checkIfLocationExists')
    ->once()
      ->with('London')
      ->andReturn(true);

    $weatherapi->shouldReceive('checkIfLocationExists')
    ->once()
      ->with('London')
      ->andReturn(true);

    $service = new WeatherService($visualcrossing, $weatherapi);

    $result = $service->checkIfLocationExists('London');
    $this->assertTrue($result);
  }

  public function test_get_average_weather_for_period_success()
  {
      $visualcrossing = Mockery::mock(WeatherSourceVisualcrossing::class);
      $weatherapi = Mockery::mock(WeatherSourceWeatherapi::class);

      $locationMock = Mockery::mock('overload:' . Location::class);
      $locationMock->shouldReceive('locationExists')
          ->once()
          ->with('London')
          ->andReturn(true);

      $weatherDataMock = Mockery::mock('alias:' . WeatherData::class);
      $weatherDataMock->shouldReceive('select')
          ->once()
          ->andReturnSelf()
          ->shouldReceive('fromSub')
          ->once()
          ->andReturnSelf()
          ->shouldReceive('get')
          ->once()
          ->andReturn(collect([(object)['temperature' => 22.5]]));

      $service = new WeatherService($visualcrossing, $weatherapi);

      // Act: Call the method
      $result = $service->getAverageWeatherForPeriod('London', '2024-09-21T14:00:00Z', '2024-09-21T15:00:00Z');

      // Assert: Check results
      $this->assertInstanceOf(AverageWeatherResult::class, $result);
      $this->assertTrue($result->success);
      $this->assertNotEmpty($result->averageData);
      $this->assertEquals(22.5, $result->averageData[0]->temperature); // Assuming the average temperature calculation logic is implemented
  }
    }

