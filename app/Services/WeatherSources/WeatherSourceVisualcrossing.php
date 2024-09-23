<?php

namespace App\Services\WeatherSources;

use App\Classes\Results\WeatherResult;

class WeatherSourceVisualcrossing extends BaseWeatherSource
{
  protected $sourceCode = 'visualcrossing_001';

  public function getWeatherData(string $location): WeatherResult|null
  {
    $retData = $this->getDataFromUrl($this->getUrlForLocation($location));

    if (!$retData || isset($retData->error) || !($retData?->currentConditions?->temp ?? null)) {
      return null;
    }

    $result = new WeatherResult();
    $result->location = $location;
    $result->temperature = $retData->currentConditions->temp;
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
    return 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/'
            . $location
            . '/today?unitGroup=metric&include=current%2Cremote%2Cstats&key='
            . $this->getApiKey()
            . '&contentType=json';
  }
}
