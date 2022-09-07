<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    protected $table = 'flights';
    protected $dates = ['departure_date', 'arrival_date'];

    public function airplane()
    {
        return $this->belongsTo(Airplane::class);
    }

    public function departureAirport()
    {
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }

    public function arrivalAirport()
    {
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeByAirport($query, $airportType, $airportCode)
    {
        return $query->with($airportType . ':id,iata_code')->wherehas($airportType, function ($q) use ($airportCode) {
            if (getType($airportCode) === 'array') {
                return $q->whereIn('iata_code', $airportCode);
            }
            return $q->where('iata_code', $airportCode);
        });
    }

    public function scopeByDepartureAirport($query, $airportCode)
    {
        return $query->byAirport('departureAirport', $airportCode);
    }

    public function scopeByArrivalAirport($query, $airportCode)
    {
        return $query->byAirport('arrivalAirport', $airportCode);
    }

    public function scopeScheduledByDatesAirportsAndType($query, $departureAirport, $arrivalAirport, $dates = [], $type)
    {
        $query = $query->byDepartureAirport($departureAirport)
            ->byArrivalAirport($arrivalAirport)
            ->scheduled()
            ->whereBetween('departure_date', $dates)
            ->whereHas('airplane', function ($q) use ($type) {
                $q->whereHas('airline', function ($q2) use ($type) {
                    if ($type === 'firstclass') {
                        $q2->where('first_class_seats', '>', 0);
                    }
                });
            })
            ->with(['airplane' => function ($q) {
                $q->with('airline:id,name');
            }])
            ->orderBy('base_price');
    }
}
