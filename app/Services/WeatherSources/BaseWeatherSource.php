<?php

namespace App\Services\WeatherSources;

use App\Services\WeatherSources\WeatherSourceInterface;
use LogicException;
use App\Classes\Results\WeatherResult;

abstract class BaseWeatherSource
{
  protected $sourceCode;

  public function __construct()
  {
    if(!isset($this->sourceCode)) {
      throw new LogicException(get_class($this) . ' must have a $sourceCode');
    }
  }

  abstract public function getWeatherData(string $location): WeatherResult|null;
  abstract public function checkIfLocationExists(string $location): bool;
  abstract protected function getUrlForLocation(string $location): string;

  public function getSourceCode(): string
  {
    return $this->sourceCode;
  }

  protected function getApiKey(): string
  {
    $apiKeyEnvVar = strtoupper($this->sourceCode) . '_API_KEY';
    return getenv($apiKeyEnvVar);
  }

  protected function getDataFromUrl($url): object|null
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Accept: application/json',
    ]);

    $response = curl_exec($ch);

    if (!$response) {
        curl_close($ch);
        return (object)['error' => curl_error($ch)];
    }

    curl_close($ch);
    return json_decode($response);
  }
}
