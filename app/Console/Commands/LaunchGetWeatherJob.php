<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\WeatherService;

class LaunchGetWeatherJob extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'job:launch-get-weather-job';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command description';

  protected $weatherService;

  public function __construct(WeatherService $weatherService)
  {
    parent::__construct();

    $this->weatherService = $weatherService;
  }

  /**
   * Execute the console command.
   */
  public function handle()
  {
    Log::info('Weather gathering job is being executed');

    $locations = $this->weatherService->getAllLocations();

    foreach ($locations as $location) {
      $this->weatherService->getWeatherDataAndSave($location['id'], $location['name']);
    }

    Log::info('Weather gathering job is finished.');
  }
}
