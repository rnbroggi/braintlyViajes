<?php

namespace App\Services;

use App\Models\Distance;
use App\Models\Flight;
use Carbon\Carbon;

class FlightService
{
    private $distance;
    private $type;

    public function getFlights($request)
    {
        $this->distance = Distance::where('airport_1', $request->departure_airport)
            ->where('airport_2', $request->arrival_airport)
            ->value('kilometers');

        extract($request->all());

        $this->type = $type;

        $departures = $this->getAvailableFlights($departure_airport, $arrival_airport, $check_in);
        $returns = $this->getAvailableFlights($arrival_airport, $departure_airport, $check_out);

        return [
            'departures' => $departures,
            'returns' => $returns,
        ];
    }

    private function getAvailableFlights($departureAirport, $arrivalAirport, $date)
    {
        $dates = $this->getClosestDates($date);

        $nonStopFlights = Flight::scheduledByDatesAirportsAndType($departureAirport, $arrivalAirport, $dates, $this->type)->get();
        $flights = [];

        foreach ($nonStopFlights as $flight) {
            if (count($flights) === 3) {
                break;
            }
            
            $flights[] = $this->formatFlights([$flight]);
        }

        if ($this->distance > 5000) {
            $closestAirportsToDestination = Distance::where('airport_1', '!=', $departureAirport)
                ->where('airport_2', $arrivalAirport)
                ->where('kilometers', '<', 5000)
                ->orderBy('kilometers')
                ->pluck('airport_1')
                ->toArray();

            $originClosestFlights = Flight::scheduledByDatesAirportsAndType($departureAirport, $closestAirportsToDestination, $dates, $this->type)->get();
            $destinationClosestFlights = Flight::scheduledByDatesAirportsAndType($closestAirportsToDestination, $arrivalAirport, $dates, $this->type)->get();

            $stopoverCount = 3 - count($flights);

            foreach ($originClosestFlights as $flight) {
                $availableFlight = $destinationClosestFlights->where('departure_date', '>', $flight->arrival_date)->first();
                if ($availableFlight && $availableFlight->departure_airport_id === $flight->arrival_airport_id) {
                    if (count($flights) < 5 && $stopoverCount < 3) {
                        $flights[] = $this->formatFlights([$flight, $availableFlight]);
                        $stopoverCount++;
                    }
                }
            }
        }

        return $flights ?? [];
    }

    private function formatFlights($flights)
    {
        $finalPrice = 0;
        $flightsList = [];
        $hasStopover = count($flights) > 1;

        foreach ($flights as $flight) {

            $updatedPrice = $flight->base_price;

            if ($flight->departure_date->diffInDays(now()) <= 7) {
                if ($flight->departure_date->diffInHours(now()) <= 24) {
                    $updatedPrice *= 1.35; // 35% increase
                } else {
                    $updatedPrice *= 1.2; // 20% increase
                }
            }

            if ($this->type === 'firstclass') {
                $updatedPrice *= 1.4; // 40% increase
            }

            $finalPrice += $updatedPrice;

            $flightsList[] = [
                'airline' => $flight->airplane->airline->name,
                'flight_number' => $flight->code,
                'departure_airport' => $flight->departureAirport->iata_code,
                'arrival_airport' => $flight->arrivalAirport->iata_code,
                'departure_date' => $flight->departure_date->format('Y-m-d H:i:s'),
                'arrival_date' => $flight->arrival_date->format('Y-m-d H:i:s'),
                'price' => +$flight->base_price,
                'type' => $this->type,
            ];
        }

        if ($hasStopover) {
            $finalPrice *= 0.6; // 40% discount
        }

        return [
            'total_price' => $finalPrice,
            'type' => $hasStopover ? 'Stopover flight' : 'Non-stop flight',
            'flights' => $flightsList,
        ];
    }

    private function getClosestDates($date)
    {
        $date = Carbon::parse($date);

        return [
            $date->copy()->subDays(10000),
            $date->copy()->addDays(10000),
        ];
    }
}
