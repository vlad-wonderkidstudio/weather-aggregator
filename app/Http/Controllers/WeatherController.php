<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Services\WeatherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Info(title="My API", version="1.0")
 */
class WeatherController extends BaseController
{
  use AuthorizesRequests, ValidatesRequests;

  protected $weatherService;

  public function __construct(WeatherService $weatherService)
  {
    $this->weatherService = $weatherService;
  }


  /**
   * @OA\Post(
   *     path="/api/add-location",
   *     summary="Add Location",
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"location"},
   *             @OA\Property(property="location", type="string", example="London")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Success",
   *         @OA\JsonContent(
   *             @OA\Property(property="success", type="boolean"),
   *             @OA\Property(property="location", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Location not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="error", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=500,
   *         description="An error occurred",
   *         @OA\JsonContent(
   *             @OA\Property(property="error", type="string"),
   *             @OA\Property(property="message", type="string")
   *         )
   *     )
   * )
   */
  public function addLocation(Request $request)
  {
    try {
      $request->validate([
        'location' => 'required|string',
      ]);

      $location = trim($request->input('location'));
      if (!$location) {
        return response()->json(['error' => 'Wrong location string provided'], 422);
      }

      Log::info("Trying to add location: $location");
      $res = $this->weatherService->addLocation($location);

      if ($res->doesNotExists) {
        return response()->json(['error' => 'Location not found'], 404);
      }
      if ($res->alreadyAdded) {
        return response()->json(['error' => 'Such location already exists in our list'], 409);
      }
      if (!$res->success) {
        return response()->json(['error' => 'An unexpected error occurred'], 500);
      }

      if ($location) {
        return response()->json(['success' => true, 'location' => $location], 200);
      }
    } catch (\Exception $e) {
      Log::error('An error occured: ' . $e->getMessage());
      //TODO - remove errorMessage in production
      return response()->json(['error' => 'An error occurred', 'errorMessage' => $e->getMessage()], 500);
    }
  }


  /**
   * @OA\Get(
   *     path="/api/get-average-weather",
   *     summary="Get weather data for a specified period",
   *     @OA\Parameter(
   *         name="location",
   *         in="query",
   *         required=true,
   *         @OA\Schema(type="string"),
   *         description="Location to get weather data for"
   *     ),
   *     @OA\Parameter(
   *         name="dateTimeFrom",
   *         in="query",
   *         required=true,
   *         @OA\Schema(type="string", example="2024-09-23T00:00:00+0300"),
   *         description="Start datetime in ISO format (UTC)"
   *     ),
   *     @OA\Parameter(
   *         name="dateTimeTo",
   *         in="query",
   *         required=true,
   *         @OA\Schema(type="string", example="2024-09-23T23:59:59+0300"),
   *         description="End datetime in ISO format (UTC)"
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Success",
   *         @OA\JsonContent(
   *             @OA\Property(property="success", type="boolean"),
   *             @OA\Property(property="cached", type="boolean"),
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Location not added yet",
   *         @OA\JsonContent(
   *             @OA\Property(property="error", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Wrong location string provided",
   *         @OA\JsonContent(
   *             @OA\Property(property="error", type="string")
   *         )
   *     ),
   *     @OA\Response(
   *         response=500,
   *         description="An error occurred",
   *         @OA\JsonContent(
   *             @OA\Property(property="error", type="string"),
   *             @OA\Property(property="message", type="string")
   *         )
   *     )
   * )
   */
  public function getWeatherForPeriod(Request $request)
  {
    try {
      // Validate the incoming request
      $request->validate([
        'location' => 'required|string',
        'dateTimeFrom' => 'required|iso_date:utc',
        'dateTimeTo' => 'required|iso_date:utc',
      ]);

      $location = trim($request->query('location'));
      $dateTimeFrom = $request->query('dateTimeFrom');
      $dateTimeTo = $request->query('dateTimeTo');

      $dateTimeFromSeconds = strtotime($dateTimeFrom);
      $dateTimeToSeconds = strtotime($dateTimeTo);
      $currentTimeSeconds = time();
      $cacheKey = "{$location}_{$dateTimeFromSeconds}_{$dateTimeToSeconds}";

      if (!$location) {
        return response()->json(['error' => 'Wrong location string provided'], 422);
      }

      if (Cache::has($cacheKey)) {
        return response()->json([
          'success' => true,
          'cached' => true,
          'data' => Cache::get($cacheKey)
        ], 200);
      }

      $ret = $this->weatherService->getAverageWeatherForPeriod($location, $dateTimeFrom, $dateTimeTo);
      if (!$ret->alreadyAdded) {
        return response()->json(['error' => 'This location is not added yet'], 404);
      }

      if ($ret->success || $ret->noData) {
        if ($dateTimeToSeconds < $currentTimeSeconds) {
          // If the timeTo is not in the future we cash result for 1 hour
          Cache::put($cacheKey, $ret->averageData, now()->addMinutes(60));
        }

        return response()->json([
          'success' => true,
          'data' => $ret->averageData
        ], 200);
      } else {
        return response()->json(['error' => 'An unexpected error occurred'], 500);
      }

    } catch (\Exception $e) {
      //TODO - remove errorMessage in production
      Log::error('An error occured: ' . $e->getMessage());
      return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
    }
  }
}
