<?php

namespace App\Services;

use App\Models\Airlines;
use App\Models\Airports;
use App\Models\DiscountFlight;
use App\Models\DiscountFlightCustomer;
use App\Models\FeaturedFlight;
use App\Models\Markup;
use App\Models\SessionFlight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str as SupportStr;

class FlightServices
{
    protected $amadeusService;

    public function __construct(AmadeusServices  $amadeusService)
    {
        $this->amadeusService = $amadeusService;
    }

    public function getAirline(string $iataCode)
    {
        $airline = Airlines::where('iataCode', $iataCode)->first();

        if (!$airline) {
            return null;
        }

        return [
            'iataCode' => $airline->iataCode,
            'airline'  => $airline->airline,
            'logo'     => secure_asset('images/airlines/' . $airline->iataCode . '.png'),
        ];
    }

    public static function getCityFromAirportCode(string $code): ?string
    {
        return Cache::remember("airport_city_{$code}", 60 * 60 * 24, function () use ($code) {
            return Airports::where('code', $code)->value('cityName');
        });
    }

    public function getFlightSession(string $sessionCode)
    {
        return Cache::remember(
            "flight_session_{$sessionCode}",
            60 * 60,
            function () use ($sessionCode) {
                return SessionFlight::where('session_code', $sessionCode)->first();
            }
        );
    }

    public function saveSessionFlight(
        string $sessionCode,
        ?string $amadeusClientRef,
        ?string $searchType,
        $payload,
        $response
    ): SessionFlight {

        return SessionFlight::create([
            'session_code'       => $sessionCode,
            'amadeus_client_ref' => $amadeusClientRef,
            'search_type'        => $searchType,
            'payload'            => is_array($payload) ? $payload : json_decode($payload, true),
            'response'           => is_array($response) ? $response : json_decode($response, true),
        ]);
    }

    public function searchFlights(string $searchType, string $supplier, array $searchParams): array
    {
        $sessionCode = SupportStr::uuid()->toString();
        session(['flight_search_type' => $searchType]);

        if ($supplier !== 'amadeus') {
            return [
                'status' => false,
                'message' => 'Unsupported supplier'
            ];
        }

        $amadeusClientRef = $this->amadeusService->generateClientRef();

        Log::info("Searching flight type: " . $searchType);

        switch ($searchType) {

            case 'oneway':

                $from       = $searchParams['from'];
                $to         = $searchParams['to'];
                $adults     = $searchParams['adult_number'];
                $children   = $searchParams['child_number'] ?? 0;
                $infants    = $searchParams['infants_number'] ?? 0;
                $travelDate = $searchParams['dateFrom'];

                session([
                    'flight.oneway' => [
                        'from'       => $from,
                        'from_name'  => $searchParams['flight_from'] ?? null,
                        'to'         => $to,
                        'to_name'    => $searchParams['flight_to'] ?? null,
                        'adults'     => $adults,
                        'children'   => $children,
                        'infants'    => $infants,
                        'date'       => $travelDate,
                    ]
                ]);

                $flightClass = $searchParams['flight_class'] ?? 'ECONOMY';
                $flightConnection = isset($searchParams['flight_connection']) ? 0 : 'any';
                $flexibleDates = isset($searchParams['flexible_dates']) &&
                    $searchParams['flexible_dates'] === 'yes';

                $payload = json_encode($searchParams);

                $response = $this->amadeusService->searchFlightOffers(
                    $from,
                    $to,
                    $adults,
                    $children,
                    $infants,
                    $searchType,
                    $travelDate,
                    $travelDate,
                    $flightClass,
                    $flightConnection,
                    'FSC',
                    $flexibleDates
                );

                break;

            case 'roundtrip':

                $from     = $searchParams['from'];
                $to       = $searchParams['to'];
                $adults   = $searchParams['adult_number'];
                $children = $searchParams['child_number'] ?? 0;
                $infants  = $searchParams['infants_number'] ?? 0;

                if (isset($searchParams['roundtrip-date'])) {
                    $travelDateFrom = substr($searchParams['roundtrip-date'], 0, 10);
                    $travelDateTo   = substr($searchParams['roundtrip-date'], 13, 10);
                } else {
                    $travelDateFrom = $searchParams['dateFrom'];
                    $travelDateTo   = $searchParams['dateTo'];
                }

                session([
                    'flight.roundtrip' => [
                        'from'       => $from,
                        'from_name'  => $searchParams['flight_from'] ?? null,
                        'to'         => $to,
                        'to_name'    => $searchParams['flight_to'] ?? null,
                        'adults'     => $adults,
                        'children'   => $children,
                        'infants'    => $infants,
                        'datefrom'   => $travelDateFrom,
                        'dateto'     => $travelDateTo,
                        'direct_flights' => $searchParams['direct_flights'] ?? null
                    ]
                ]);

                $flightClass = $searchParams['flight_class'] ?? 'ECONOMY';

                $flightConnection = isset($searchParams['flight_connection']) &&
                    $searchParams['flight_connection'] == "0"
                    ? 0 : 'any';

                $flexibleDates = isset($searchParams['flexible_dates']) &&
                    $searchParams['flexible_dates'] === 'yes';

                $payload = json_encode($searchParams);

                $response = $this->amadeusService->searchFlightOffers(
                    $from,
                    $to,
                    $adults,
                    $children,
                    $infants,
                    $searchType,
                    $travelDateFrom,
                    $travelDateTo,
                    $flightClass,
                    $flightConnection,
                    $searchParams['flight_option'] ?? 'FSC',
                    $flexibleDates
                );

                break;

            case 'multi':

                $froms  = $searchParams['from'];
                $tos    = $searchParams['to'];
                $dates  = $searchParams['daterange-single'];

                $lastDate = $dates[0];
                $travelFrom = [];
                $travelTo   = [];
                $travelDate = [];

                foreach ($froms as $key => $from) {

                    session([
                        "flight.multi.$key" => [
                            'from'      => $from,
                            'from_name' => $searchParams['flight_from'][$key] ?? null,
                            'to'        => $tos[$key] ?? null,
                            'to_name'   => $searchParams['flight_to'][$key] ?? null,
                            'date'      => $dates[$key]
                        ]
                    ]);

                    if (!empty($from) && !empty($tos[$key])) {

                        $travelFrom[] = $from;
                        $travelTo[]   = $tos[$key];

                        $travelDate[] = $dates[$key] >= $lastDate
                            ? $dates[$key]
                            : $lastDate;

                        $lastDate = end($travelDate);
                    }
                }

                $adults   = $searchParams['adult_number'];
                $children = $searchParams['child_number'] ?? 0;
                $infants  = $searchParams['infants_number'] ?? 0;

                session([
                    'flight.multi.adults'   => $adults,
                    'flight.multi.children' => $children,
                    'flight.multi.infants'  => $infants
                ]);

                $payload = json_encode($searchParams);

                $response = $this->amadeusService->searchMultiCityFlightOffers(
                    $travelFrom,
                    $travelTo,
                    $travelDate,
                    $adults,
                    $children,
                    $infants,
                    $searchParams['flight_class'] ?? 'ECONOMY',
                    $searchParams['flight_connection'] ?? 'any'
                );

                break;

            default:
                return [
                    'status' => false,
                    'message' => 'Invalid search type'
                ];
        }

        $this->saveSessionFlight(
            $sessionCode,
            $amadeusClientRef,
            $searchType,
            $payload,
            $response
        );

        return $this->searchFlightResult($sessionCode);
    }
    public function searchFlightResult(string $session_code): array
    {
        $session = DB::table('sessions_flights')
            ->where('session_code', $session_code)
            ->first();

        if (!$session) {
            throw new \Exception("Session not found");
        }

        $payload = json_decode($session->payload, true);
        $response = json_decode($session->response, true);

        $results = $response['data'] ?? [];

        $filter_airlines = [];
        $filter_stops = [];
        $filter_flight_classes = [];
        $filter_timefrom = [];
        $filter_timeto = [];
        $summary = [];

        $fastest_flight_time = PHP_INT_MAX;
        $cheapest_flight_amount = PHP_INT_MAX;

        foreach ($results as $result) {

            $itineraries = $result['itineraries'];
            $travelerPricings = $result['travelerPricings'];

            $segment_count = 0;
            $stops = 0;

            foreach ($itineraries as $itinerary) {
                $segments = $itinerary['segments'];
                $segment_count += count($segments);
                $stops += count($segments) - 1;
            }

            $first_itinerary = $itineraries[0];
            $last_itinerary = end($itineraries);
            $last_segment = end($last_itinerary['segments']);

            $durationRaw = $first_itinerary['duration'];
            $durationFormatted = $this->formatTimeFlight($durationRaw);
            $durationMinutes = $this->convertHoursToMinutes($durationFormatted);

            $amount = $this->calculateFlightPrice(
                $first_itinerary['segments'][0]['carrierCode'],
                $travelerPricings[0]['price']['base'],
                $travelerPricings[0]['price']['total']
            );


            if ($durationMinutes <= $fastest_flight_time) {
                $fastest_flight_time = $durationMinutes;
                $fastest_flight = $result;
            }


            if ($amount <= $cheapest_flight_amount) {
                $cheapest_flight_amount = $amount;
                $cheapest_flight = $result;
            }

            $flightClass = $travelerPricings[0]['fareDetailsBySegment'][0]['cabin'] ?? '';

            $time_from = $this->formatTimeAirport($first_itinerary['segments'][0]['departure']['at']);
            $time_to = $this->formatTimeAirport($last_segment['arrival']['at']);

            $filter_stops[$stops] = $stops;
            $filter_flight_classes[$flightClass] = $flightClass;
            $carrierCode = $first_itinerary['segments'][0]['carrierCode'];
            $airlineData = $this->getAirline($carrierCode);
            $filter_airlines[$carrierCode] = [
                'code' => $carrierCode,
                'name' => $airlineData['airline'] ?? null,
                'logo' => isset($airlineData['logo'])
                    ? secure_asset('storage/airlines/' . $airlineData['logo'])
                    : null,
            ];

            $filter_timefrom[$time_from['date']] = $time_from['date'];
            $filter_timeto[$time_to['date']] = $time_to['date'];

            $carrierCode = $first_itinerary['segments'][0]['carrierCode'];
            $summaryStops = ($stops >= 2) ? 2 : $stops;

            if (!isset($summary[$carrierCode][$summaryStops])) {
                $summary[$carrierCode][$summaryStops] = $amount;
            } else {
                if ($amount < $summary[$carrierCode][$summaryStops]) {
                    $summary[$carrierCode][$summaryStops] = $amount;
                }
            }
        }

        asort($filter_airlines);
        asort($filter_stops);
        asort($filter_flight_classes);
        asort($filter_timefrom);
        asort($filter_timeto);

        return [
            'flight_count' => count($results),
            'payload' => $payload,
            'recommended' => [
                'cheapest' => $cheapest_flight ?? null,
                'fastest' => $fastest_flight ?? null,
            ],
            'flights' => $results,
            'filter' => [
                'airlines' => $filter_airlines,
                'stops' => $filter_stops,
                'flight_classes' => $filter_flight_classes,
                'time_from' => $filter_timefrom,
                'time_to' => $filter_timeto,
            ],
            'summary' => $summary
        ];
    }

    public function formatTimeFlight(string $flightTime): string
    {
        // Remove the "PT" prefix
        $formatted = str_replace('PT', '', $flightTime);

        // Replace hours and minutes with readable format
        $formatted = str_replace('H', 'h ', $formatted);
        $formatted = str_replace('M', 'm', $formatted);

        return $formatted;
    }
    private function convertHoursToMinutes(string $duration): int
    {
        // Replace 'h ' with ':' and remove 'm'
        $duration = str_replace('h ', ':', $duration);
        $duration = str_replace('m', '', $duration);

        // Split hours and minutes
        $parts = explode(':', $duration);

        // Calculate total minutes
        $totalMinutes = (intval($parts[0]) * 60) + intval($parts[1] ?? 0);

        return $totalMinutes;
    }
    public function calculateStopover(string $landing_time, string $takeoff_time): string
    {
        $landing_time = str_replace('T', ' ', $landing_time);
        $takeoff_time = str_replace('T', ' ', $takeoff_time);

        $datetime1 = Carbon::parse($landing_time);
        $datetime2 = Carbon::parse($takeoff_time);

        $interval = $datetime1->diff($datetime2);

        $stopover = '';

        if ($interval->h > 0) {
            $stopover = $interval->h . ' hours ';
        }

        if ($interval->i > 0) {
            $stopover .= $interval->i . ' minutes';
        }

        return trim($stopover);
    }

    public function getDiscountAirline(string $airlineCode): int
    {
        $today = Carbon::today()->format('Y-m-d');

        $discount = DiscountFlight::where('airline_code', $airlineCode)
            ->where('date_start', '<=', $today)
            ->where('date_end', '>=', $today)
            ->orderByDesc('date_end')
            ->first();

        return $discount ? (int) $discount->discount : 0;
    }
    public function getDiscountAirlineCustomer(string $airlineCode, string $userCode): int
    {
        return (int) DiscountFlightCustomer::where('airline_code', $airlineCode)
            ->where('customer_code', $userCode)
            ->whereDate('date_start', '<=', now())
            ->whereDate('date_end', '>=', now())
            ->latest('date_end')
            ->value('discount') ?? 0;
    }


    public function calculateFlightPrice(
        string $airlineCode,
        float $basePrice,
        float $totalPrice,
        ?string $userCode = null
    ) {

        $farePrice = $totalPrice - $basePrice;

        $airlineDiscount = $this->getDiscountAirline($airlineCode);
        $customerDiscount = $userCode
            ? $this->getDiscountAirlineCustomer($airlineCode, $userCode)
            : 100;


        $finalDiscount = min($airlineDiscount, $customerDiscount);

        $discountAmount = ($finalDiscount / 100) * $basePrice;


        $markup = $this->getMarkup('FLIGHT');

        $basePriceMarkup = $this->priceMarkup($basePrice, $markup);

        $flightPrice = $basePriceMarkup - $discountAmount + $farePrice;

        return $flightPrice;
    }


    public function getFeaturedFlights()
    {
        return FeaturedFlight::whereDate('date_from', '>', now())
            ->where('active', 1)
            ->latest()
            ->limit(6)
            ->get();
    }



    public function getMarkup(string $module)
    {
        $customerMode = 'B2C';
        // if (Auth::check()) {
        //     $accessLevel = Auth::user()->access_level;

        //     if (in_array($accessLevel, ['AGENT', 'ADMIN'])) {
        //         $customerMode = 'B2B';
        //     }
        // }

        return Markup::where('module', $module)
            ->where('customer_type', $customerMode)
            ->first();
    }

    public function priceMarkup(float $amount, $markup): float
    {
        if ($amount <= 0) {
            return 0;
        }

        if (!$markup) {
            return $amount;
        }


        $markupType = is_array($markup)
            ? ($markup['markup_type'] ?? null)
            : ($markup->markup_type ?? null);

        $markupAmount = is_array($markup)
            ? ($markup['markup_amount'] ?? 0)
            : ($markup->markup_amount ?? 0);

        $newAmount = $amount;

        if ($markupType === 'FLAT') {
            $newAmount = $amount + (float) $markupAmount;
        }

        if ($markupType === 'PERCENTAGE') {
            $newAmount = $amount + ((float) $markupAmount / 100) * $amount;
        }

        return round($newAmount, 2);
    }
    public function formatTimeAirport(string $date): array
    {
        // Define month mapping
        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec',
        ];

        // Convert ISO date to Carbon instance
        $carbonDate = Carbon::parse($date);

        // Format date
        $dateFormat = $carbonDate->format('d');                     // Day
        $monthFormat = $months[$carbonDate->format('m')] ?? '';     // Month abbreviation
        $yearFormat = $carbonDate->format('Y');                     // Year

        $formattedDate = $dateFormat . ' ' . $monthFormat . ' ' . $yearFormat;
        $formattedTime = $carbonDate->format('H:i:s');

        return [
            'date' => $formattedDate,
            'time' => $formattedTime,
        ];
    }
}
