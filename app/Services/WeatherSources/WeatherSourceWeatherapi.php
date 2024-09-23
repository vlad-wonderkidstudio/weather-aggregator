<?php

namespace App\Services\WeatherSources;

use App\Classes\Results\WeatherResult;

class WeatherSourceWeatherapi extends BaseWeatherSource
{
  protected $sourceCode = 'weatherapi_001';

  public function getWeatherData(string $location): WeatherResult|null
  {
    $retData = $this->getDataFromUrl($this->getUrlForLocation($location));

    if (!$retData || isset($retData->error) || !($retData?->current?->temp_c ?? null)) {
      return null;
    }

    $result = new WeatherResult();
    $result->location = $location;
    $result->temperature = $retData->current->temp_c;
    $result->sourceCode = $this->getSourceCode();

    return $result;
  }

  public function checkIfLocationExists(string $location): bool
  {
    $retData = $this->getDataFromUrl($this->getUrlForLocation($location));

    if (!$retData || isset($retData->error)) {
      return false;
    }

    return true;
  }

  protected function getUrlForLocation(string $location): string
  {
    return 'https://api.weatherapi.com/v1/current.json?key='
            . $this->getApiKey()
            . '&q='
            . $location
            . '&aqi=no';
  }
}
