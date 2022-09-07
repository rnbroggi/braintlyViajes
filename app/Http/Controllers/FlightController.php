<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightRequest;
use App\Models\Flight;
use App\Services\FlightService;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function __construct(FlightService $service)
    {
        $this->flightService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(FlightRequest $request)
    {
        $data = $this->flightService->getFlights($request);
        return response()->json(['data' => $data]);
    }
}
