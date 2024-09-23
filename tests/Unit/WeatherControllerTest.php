<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\WeatherController;
use App\Services\WeatherService;
use Mockery;
use Illuminate\Support\Facades\Cache;


class WeatherControllerTest extends TestCase
{
    public function test_add_location_success()
    {
        // Mock the WeatherService
        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldReceive('addLocation')
            ->once()
            ->andReturn((object)[
                'doesNotExists' => false,
                'alreadyAdded' => false,
                'success' => true,
            ]);

        // Instantiate the controller with the mocked service
        $controller = new WeatherController($weatherService);

        // Create a request with a valid location
        $request = Request::create('/api/add-location', 'POST', [
            'location' => 'London'
        ]);

        // Call the method and get the response
        $response = $controller->addLocation($request);

        // Assert the response status and structure
        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            'success' => true,
            'location' => 'London'
        ], $response->getData(true));
    }

    public function test_get_weather_for_period_success()
    {
        // Mock the WeatherService
        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldReceive('getAverageWeatherForPeriod')
            ->once()
            ->andReturn((object)[
                'alreadyAdded' => true,
                'success' => true,
                'noData' => false,
                'averageData' => (object)[
                    'temperature' => 20,
                    'humidity' => 80,
                ],
            ]);

        // Instantiate the controller with the mocked service
        $controller = new WeatherController($weatherService);

        // Mock request and cache behavior
        $location = 'London';
        $dateTimeFrom = '2024-09-21T14:00:00Z';
        $dateTimeTo = '2024-09-21T15:00:00Z';

        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('put')->once();

        // Create a request
        $request = Request::create('/api/get-average-weather', 'GET', [
            'location' => $location,
            'dateTimeFrom' => $dateTimeFrom,
            'dateTimeTo' => $dateTimeTo
        ]);

        // Call the method and get the response
        $response = $controller->getWeatherForPeriod($request);

        // Assert the response status and structure
        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            'success' => true,
            'data' => [
                'temperature' => 20,
                'humidity' => 80,
            ]
        ], $response->getData(true));
    }
}
?>