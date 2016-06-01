<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * Service definition for QPXExpress (v1).
 *
 * <p>
 * Lets you find the least expensive flights between an origin and a
 * destination.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="http://developers.google.com/qpx-express" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_QPXExpress extends Google_Service
{


  public $trips;
  

  /**
   * Constructs the internal representation of the QPXExpress service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'qpxExpress/v1/trips/';
    $this->version = 'v1';
    $this->serviceName = 'qpxExpress';

    $this->trips = new Google_Service_QPXExpress_Trips_Resource(
        $this,
        $this->serviceName,
        'trips',
        array(
          'methods' => array(
            'search' => array(
              'path' => 'search',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
  }
}


/**
 * The "trips" collection of methods.
 * Typical usage is:
 *  <code>
 *   $qpxExpressService = new Google_Service_QPXExpress(...);
 *   $trips = $qpxExpressService->trips;
 *  </code>
 */
class Google_Service_QPXExpress_Trips_Resource extends Google_Service_Resource
{

  /**
   * Returns a list of flights. (trips.search)
   *
   * @param Google_TripsSearchRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_QPXExpress_TripsSearchResponse
   */
  public function search(Google_Service_QPXExpress_TripsSearchRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('search', array($params), "Google_Service_QPXExpress_TripsSearchResponse");
  }
}




class Google_Service_QPXExpress_AircraftData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $kind;
  public $name;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_QPXExpress_AirportData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $city;
  public $code;
  public $kind;
  public $name;


  public function setCity($city)
  {
    $this->city = $city;
  }
  public function getCity()
  {
    return $this->city;
  }
  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_QPXExpress_BagDescriptor extends Google_Collection
{
  protected $collection_key = 'description';
  protected $internal_gapi_mappings = array(
  );
  public $commercialName;
  public $count;
  public $description;
  public $kind;
  public $subcode;


  public function setCommercialName($commercialName)
  {
    $this->commercialName = $commercialName;
  }
  public function getCommercialName()
  {
    return $this->commercialName;
  }
  public function setCount($count)
  {
    $this->count = $count;
  }
  public function getCount()
  {
    return $this->count;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSubcode($subcode)
  {
    $this->subcode = $subcode;
  }
  public function getSubcode()
  {
    return $this->subcode;
  }
}

class Google_Service_QPXExpress_CarrierData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $kind;
  public $name;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_QPXExpress_CityData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $country;
  public $kind;
  public $name;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setCountry($country)
  {
    $this->country = $country;
  }
  public function getCountry()
  {
    return $this->country;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_QPXExpress_Data extends Google_Collection
{
  protected $collection_key = 'tax';
  protected $internal_gapi_mappings = array(
  );
  protected $aircraftType = 'Google_Service_QPXExpress_AircraftData';
  protected $aircraftDataType = 'array';
  protected $airportType = 'Google_Service_QPXExpress_AirportData';
  protected $airportDataType = 'array';
  protected $carrierType = 'Google_Service_QPXExpress_CarrierData';
  protected $carrierDataType = 'array';
  protected $cityType = 'Google_Service_QPXExpress_CityData';
  protected $cityDataType = 'array';
  public $kind;
  protected $taxType = 'Google_Service_QPXExpress_TaxData';
  protected $taxDataType = 'array';


  public function setAircraft($aircraft)
  {
    $this->aircraft = $aircraft;
  }
  public function getAircraft()
  {
    return $this->aircraft;
  }
  public function setAirport($airport)
  {
    $this->airport = $airport;
  }
  public function getAirport()
  {
    return $this->airport;
  }
  public function setCarrier($carrier)
  {
    $this->carrier = $carrier;
  }
  public function getCarrier()
  {
    return $this->carrier;
  }
  public function setCity($city)
  {
    $this->city = $city;
  }
  public function getCity()
  {
    return $this->city;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setTax($tax)
  {
    $this->tax = $tax;
  }
  public function getTax()
  {
    return $this->tax;
  }
}

class Google_Service_QPXExpress_FareInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $basisCode;
  public $carrier;
  public $destination;
  public $id;
  public $kind;
  public $origin;
  public $private;


  public function setBasisCode($basisCode)
  {
    $this->basisCode = $basisCode;
  }
  public function getBasisCode()
  {
    return $this->basisCode;
  }
  public function setCarrier($carrier)
  {
    $this->carrier = $carrier;
  }
  public function getCarrier()
  {
    return $this->carrier;
  }
  public function setDestination($destination)
  {
    $this->destination = $destination;
  }
  public function getDestination()
  {
    return $this->destination;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setOrigin($origin)
  {
    $this->origin = $origin;
  }
  public function getOrigin()
  {
    return $this->origin;
  }
  public function setPrivate($private)
  {
    $this->private = $private;
  }
  public function getPrivate()
  {
    return $this->private;
  }
}

class Google_Service_QPXExpress_FlightInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $carrier;
  public $number;


  public function setCarrier($carrier)
  {
    $this->carrier = $carrier;
  }
  public function getCarrier()
  {
    return $this->carrier;
  }
  public function setNumber($number)
  {
    $this->number = $number;
  }
  public function getNumber()
  {
    return $this->number;
  }
}

class Google_Service_QPXExpress_FreeBaggageAllowance extends Google_Collection
{
  protected $collection_key = 'bagDescriptor';
  protected $internal_gapi_mappings = array(
  );
  protected $bagDescriptorType = 'Google_Service_QPXExpress_BagDescriptor';
  protected $bagDescriptorDataType = 'array';
  public $kilos;
  public $kilosPerPiece;
  public $kind;
  public $pieces;
  public $pounds;


  public function setBagDescriptor($bagDescriptor)
  {
    $this->bagDescriptor = $bagDescriptor;
  }
  public function getBagDescriptor()
  {
    return $this->bagDescriptor;
  }
  public function setKilos($kilos)
  {
    $this->kilos = $kilos;
  }
  public function getKilos()
  {
    return $this->kilos;
  }
  public function setKilosPerPiece($kilosPerPiece)
  {
    $this->kilosPerPiece = $kilosPerPiece;
  }
  public function getKilosPerPiece()
  {
    return $this->kilosPerPiece;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPieces($pieces)
  {
    $this->pieces = $pieces;
  }
  public function getPieces()
  {
    return $this->pieces;
  }
  public function setPounds($pounds)
  {
    $this->pounds = $pounds;
  }
  public function getPounds()
  {
    return $this->pounds;
  }
}

class Google_Service_QPXExpress_LegInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $aircraft;
  public $arrivalTime;
  public $changePlane;
  public $connectionDuration;
  public $departureTime;
  public $destination;
  public $destinationTerminal;
  public $duration;
  public $id;
  public $kind;
  public $meal;
  public $mileage;
  public $onTimePerformance;
  public $operatingDisclosure;
  public $origin;
  public $originTerminal;
  public $secure;


  public function setAircraft($aircraft)
  {
    $this->aircraft = $aircraft;
  }
  public function getAircraft()
  {
    return $this->aircraft;
  }
  public function setArrivalTime($arrivalTime)
  {
    $this->arrivalTime = $arrivalTime;
  }
  public function getArrivalTime()
  {
    return $this->arrivalTime;
  }
  public function setChangePlane($changePlane)
  {
    $this->changePlane = $changePlane;
  }
  public function getChangePlane()
  {
    return $this->changePlane;
  }
  public function setConnectionDuration($connectionDuration)
  {
    $this->connectionDuration = $connectionDuration;
  }
  public function getConnectionDuration()
  {
    return $this->connectionDuration;
  }
  public function setDepartureTime($departureTime)
  {
    $this->departureTime = $departureTime;
  }
  public function getDepartureTime()
  {
    return $this->departureTime;
  }
  public function setDestination($destination)
  {
    $this->destination = $destination;
  }
  public function getDestination()
  {
    return $this->destination;
  }
  public function setDestinationTerminal($destinationTerminal)
  {
    $this->destinationTerminal = $destinationTerminal;
  }
  public function getDestinationTerminal()
  {
    return $this->destinationTerminal;
  }
  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  public function getDuration()
  {
    return $this->duration;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMeal($meal)
  {
    $this->meal = $meal;
  }
  public function getMeal()
  {
    return $this->meal;
  }
  public function setMileage($mileage)
  {
    $this->mileage = $mileage;
  }
  public function getMileage()
  {
    return $this->mileage;
  }
  public function setOnTimePerformance($onTimePerformance)
  {
    $this->onTimePerformance = $onTimePerformance;
  }
  public function getOnTimePerformance()
  {
    return $this->onTimePerformance;
  }
  public function setOperatingDisclosure($operatingDisclosure)
  {
    $this->operatingDisclosure = $operatingDisclosure;
  }
  public function getOperatingDisclosure()
  {
    return $this->operatingDisclosure;
  }
  public function setOrigin($origin)
  {
    $this->origin = $origin;
  }
  public function getOrigin()
  {
    return $this->origin;
  }
  public function setOriginTerminal($originTerminal)
  {
    $this->originTerminal = $originTerminal;
  }
  public function getOriginTerminal()
  {
    return $this->originTerminal;
  }
  public function setSecure($secure)
  {
    $this->secure = $secure;
  }
  public function getSecure()
  {
    return $this->secure;
  }
}

class Google_Service_QPXExpress_PassengerCounts extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $adultCount;
  public $childCount;
  public $infantInLapCount;
  public $infantInSeatCount;
  public $kind;
  public $seniorCount;


  public function setAdultCount($adultCount)
  {
    $this->adultCount = $adultCount;
  }
  public function getAdultCount()
  {
    return $this->adultCount;
  }
  public function setChildCount($childCount)
  {
    $this->childCount = $childCount;
  }
  public function getChildCount()
  {
    return $this->childCount;
  }
  public function setInfantInLapCount($infantInLapCount)
  {
    $this->infantInLapCount = $infantInLapCount;
  }
  public function getInfantInLapCount()
  {
    return $this->infantInLapCount;
  }
  public function setInfantInSeatCount($infantInSeatCount)
  {
    $this->infantInSeatCount = $infantInSeatCount;
  }
  public function getInfantInSeatCount()
  {
    return $this->infantInSeatCount;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSeniorCount($seniorCount)
  {
    $this->seniorCount = $seniorCount;
  }
  public function getSeniorCount()
  {
    return $this->seniorCount;
  }
}

class Google_Service_QPXExpress_PricingInfo extends Google_Collection
{
  protected $collection_key = 'tax';
  protected $internal_gapi_mappings = array(
  );
  public $baseFareTotal;
  protected $fareType = 'Google_Service_QPXExpress_FareInfo';
  protected $fareDataType = 'array';
  public $fareCalculation;
  public $kind;
  public $latestTicketingTime;
  protected $passengersType = 'Google_Service_QPXExpress_PassengerCounts';
  protected $passengersDataType = '';
  public $ptc;
  public $refundable;
  public $saleFareTotal;
  public $saleTaxTotal;
  public $saleTotal;
  protected $segmentPricingType = 'Google_Service_QPXExpress_SegmentPricing';
  protected $segmentPricingDataType = 'array';
  protected $taxType = 'Google_Service_QPXExpress_TaxInfo';
  protected $taxDataType = 'array';


  public function setBaseFareTotal($baseFareTotal)
  {
    $this->baseFareTotal = $baseFareTotal;
  }
  public function getBaseFareTotal()
  {
    return $this->baseFareTotal;
  }
  public function setFare($fare)
  {
    $this->fare = $fare;
  }
  public function getFare()
  {
    return $this->fare;
  }
  public function setFareCalculation($fareCalculation)
  {
    $this->fareCalculation = $fareCalculation;
  }
  public function getFareCalculation()
  {
    return $this->fareCalculation;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLatestTicketingTime($latestTicketingTime)
  {
    $this->latestTicketingTime = $latestTicketingTime;
  }
  public function getLatestTicketingTime()
  {
    return $this->latestTicketingTime;
  }
  public function setPassengers(Google_Service_QPXExpress_PassengerCounts $passengers)
  {
    $this->passengers = $passengers;
  }
  public function getPassengers()
  {
    return $this->passengers;
  }
  public function setPtc($ptc)
  {
    $this->ptc = $ptc;
  }
  public function getPtc()
  {
    return $this->ptc;
  }
  public function setRefundable($refundable)
  {
    $this->refundable = $refundable;
  }
  public function getRefundable()
  {
    return $this->refundable;
  }
  public function setSaleFareTotal($saleFareTotal)
  {
    $this->saleFareTotal = $saleFareTotal;
  }
  public function getSaleFareTotal()
  {
    return $this->saleFareTotal;
  }
  public function setSaleTaxTotal($saleTaxTotal)
  {
    $this->saleTaxTotal = $saleTaxTotal;
  }
  public function getSaleTaxTotal()
  {
    return $this->saleTaxTotal;
  }
  public function setSaleTotal($saleTotal)
  {
    $this->saleTotal = $saleTotal;
  }
  public function getSaleTotal()
  {
    return $this->saleTotal;
  }
  public function setSegmentPricing($segmentPricing)
  {
    $this->segmentPricing = $segmentPricing;
  }
  public function getSegmentPricing()
  {
    return $this->segmentPricing;
  }
  public function setTax($tax)
  {
    $this->tax = $tax;
  }
  public function getTax()
  {
    return $this->tax;
  }
}

class Google_Service_QPXExpress_SegmentInfo extends Google_Collection
{
  protected $collection_key = 'leg';
  protected $internal_gapi_mappings = array(
  );
  public $bookingCode;
  public $bookingCodeCount;
  public $cabin;
  public $connectionDuration;
  public $duration;
  protected $flightType = 'Google_Service_QPXExpress_FlightInfo';
  protected $flightDataType = '';
  public $id;
  public $kind;
  protected $legType = 'Google_Service_QPXExpress_LegInfo';
  protected $legDataType = 'array';
  public $marriedSegmentGroup;
  public $subjectToGovernmentApproval;


  public function setBookingCode($bookingCode)
  {
    $this->bookingCode = $bookingCode;
  }
  public function getBookingCode()
  {
    return $this->bookingCode;
  }
  public function setBookingCodeCount($bookingCodeCount)
  {
    $this->bookingCodeCount = $bookingCodeCount;
  }
  public function getBookingCodeCount()
  {
    return $this->bookingCodeCount;
  }
  public function setCabin($cabin)
  {
    $this->cabin = $cabin;
  }
  public function getCabin()
  {
    return $this->cabin;
  }
  public function setConnectionDuration($connectionDuration)
  {
    $this->connectionDuration = $connectionDuration;
  }
  public function getConnectionDuration()
  {
    return $this->connectionDuration;
  }
  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  public function getDuration()
  {
    return $this->duration;
  }
  public function setFlight(Google_Service_QPXExpress_FlightInfo $flight)
  {
    $this->flight = $flight;
  }
  public function getFlight()
  {
    return $this->flight;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLeg($leg)
  {
    $this->leg = $leg;
  }
  public function getLeg()
  {
    return $this->leg;
  }
  public function setMarriedSegmentGroup($marriedSegmentGroup)
  {
    $this->marriedSegmentGroup = $marriedSegmentGroup;
  }
  public function getMarriedSegmentGroup()
  {
    return $this->marriedSegmentGroup;
  }
  public function setSubjectToGovernmentApproval($subjectToGovernmentApproval)
  {
    $this->subjectToGovernmentApproval = $subjectToGovernmentApproval;
  }
  public function getSubjectToGovernmentApproval()
  {
    return $this->subjectToGovernmentApproval;
  }
}

class Google_Service_QPXExpress_SegmentPricing extends Google_Collection
{
  protected $collection_key = 'freeBaggageOption';
  protected $internal_gapi_mappings = array(
  );
  public $fareId;
  protected $freeBaggageOptionType = 'Google_Service_QPXExpress_FreeBaggageAllowance';
  protected $freeBaggageOptionDataType = 'array';
  public $kind;
  public $segmentId;


  public function setFareId($fareId)
  {
    $this->fareId = $fareId;
  }
  public function getFareId()
  {
    return $this->fareId;
  }
  public function setFreeBaggageOption($freeBaggageOption)
  {
    $this->freeBaggageOption = $freeBaggageOption;
  }
  public function getFreeBaggageOption()
  {
    return $this->freeBaggageOption;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSegmentId($segmentId)
  {
    $this->segmentId = $segmentId;
  }
  public function getSegmentId()
  {
    return $this->segmentId;
  }
}

class Google_Service_QPXExpress_SliceInfo extends Google_Collection
{
  protected $collection_key = 'segment';
  protected $internal_gapi_mappings = array(
  );
  public $duration;
  public $kind;
  protected $segmentType = 'Google_Service_QPXExpress_SegmentInfo';
  protected $segmentDataType = 'array';


  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  public function getDuration()
  {
    return $this->duration;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSegment($segment)
  {
    $this->segment = $segment;
  }
  public function getSegment()
  {
    return $this->segment;
  }
}

class Google_Service_QPXExpress_SliceInput extends Google_Collection
{
  protected $collection_key = 'prohibitedCarrier';
  protected $internal_gapi_mappings = array(
  );
  public $alliance;
  public $date;
  public $destination;
  public $kind;
  public $maxConnectionDuration;
  public $maxStops;
  public $origin;
  public $permittedCarrier;
  protected $permittedDepartureTimeType = 'Google_Service_QPXExpress_TimeOfDayRange';
  protected $permittedDepartureTimeDataType = '';
  public $preferredCabin;
  public $prohibitedCarrier;


  public function setAlliance($alliance)
  {
    $this->alliance = $alliance;
  }
  public function getAlliance()
  {
    return $this->alliance;
  }
  public function setDate($date)
  {
    $this->date = $date;
  }
  public function getDate()
  {
    return $this->date;
  }
  public function setDestination($destination)
  {
    $this->destination = $destination;
  }
  public function getDestination()
  {
    return $this->destination;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMaxConnectionDuration($maxConnectionDuration)
  {
    $this->maxConnectionDuration = $maxConnectionDuration;
  }
  public function getMaxConnectionDuration()
  {
    return $this->maxConnectionDuration;
  }
  public function setMaxStops($maxStops)
  {
    $this->maxStops = $maxStops;
  }
  public function getMaxStops()
  {
    return $this->maxStops;
  }
  public function setOrigin($origin)
  {
    $this->origin = $origin;
  }
  public function getOrigin()
  {
    return $this->origin;
  }
  public function setPermittedCarrier($permittedCarrier)
  {
    $this->permittedCarrier = $permittedCarrier;
  }
  public function getPermittedCarrier()
  {
    return $this->permittedCarrier;
  }
  public function setPermittedDepartureTime(Google_Service_QPXExpress_TimeOfDayRange $permittedDepartureTime)
  {
    $this->permittedDepartureTime = $permittedDepartureTime;
  }
  public function getPermittedDepartureTime()
  {
    return $this->permittedDepartureTime;
  }
  public function setPreferredCabin($preferredCabin)
  {
    $this->preferredCabin = $preferredCabin;
  }
  public function getPreferredCabin()
  {
    return $this->preferredCabin;
  }
  public function setProhibitedCarrier($prohibitedCarrier)
  {
    $this->prohibitedCarrier = $prohibitedCarrier;
  }
  public function getProhibitedCarrier()
  {
    return $this->prohibitedCarrier;
  }
}

class Google_Service_QPXExpress_TaxData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $name;


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_QPXExpress_TaxInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $chargeType;
  public $code;
  public $country;
  public $id;
  public $kind;
  public $salePrice;


  public function setChargeType($chargeType)
  {
    $this->chargeType = $chargeType;
  }
  public function getChargeType()
  {
    return $this->chargeType;
  }
  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setCountry($country)
  {
    $this->country = $country;
  }
  public function getCountry()
  {
    return $this->country;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSalePrice($salePrice)
  {
    $this->salePrice = $salePrice;
  }
  public function getSalePrice()
  {
    return $this->salePrice;
  }
}

class Google_Service_QPXExpress_TimeOfDayRange extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $earliestTime;
  public $kind;
  public $latestTime;


  public function setEarliestTime($earliestTime)
  {
    $this->earliestTime = $earliestTime;
  }
  public function getEarliestTime()
  {
    return $this->earliestTime;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLatestTime($latestTime)
  {
    $this->latestTime = $latestTime;
  }
  public function getLatestTime()
  {
    return $this->latestTime;
  }
}

class Google_Service_QPXExpress_TripOption extends Google_Collection
{
  protected $collection_key = 'slice';
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  protected $pricingType = 'Google_Service_QPXExpress_PricingInfo';
  protected $pricingDataType = 'array';
  public $saleTotal;
  protected $sliceType = 'Google_Service_QPXExpress_SliceInfo';
  protected $sliceDataType = 'array';


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPricing($pricing)
  {
    $this->pricing = $pricing;
  }
  public function getPricing()
  {
    return $this->pricing;
  }
  public function setSaleTotal($saleTotal)
  {
    $this->saleTotal = $saleTotal;
  }
  public function getSaleTotal()
  {
    return $this->saleTotal;
  }
  public function setSlice($slice)
  {
    $this->slice = $slice;
  }
  public function getSlice()
  {
    return $this->slice;
  }
}

class Google_Service_QPXExpress_TripOptionsRequest extends Google_Collection
{
  protected $collection_key = 'slice';
  protected $internal_gapi_mappings = array(
  );
  public $maxPrice;
  protected $passengersType = 'Google_Service_QPXExpress_PassengerCounts';
  protected $passengersDataType = '';
  public $refundable;
  public $saleCountry;
  protected $sliceType = 'Google_Service_QPXExpress_SliceInput';
  protected $sliceDataType = 'array';
  public $solutions;


  public function setMaxPrice($maxPrice)
  {
    $this->maxPrice = $maxPrice;
  }
  public function getMaxPrice()
  {
    return $this->maxPrice;
  }
  public function setPassengers(Google_Service_QPXExpress_PassengerCounts $passengers)
  {
    $this->passengers = $passengers;
  }
  public function getPassengers()
  {
    return $this->passengers;
  }
  public function setRefundable($refundable)
  {
    $this->refundable = $refundable;
  }
  public function getRefundable()
  {
    return $this->refundable;
  }
  public function setSaleCountry($saleCountry)
  {
    $this->saleCountry = $saleCountry;
  }
  public function getSaleCountry()
  {
    return $this->saleCountry;
  }
  public function setSlice($slice)
  {
    $this->slice = $slice;
  }
  public function getSlice()
  {
    return $this->slice;
  }
  public function setSolutions($solutions)
  {
    $this->solutions = $solutions;
  }
  public function getSolutions()
  {
    return $this->solutions;
  }
}

class Google_Service_QPXExpress_TripOptionsResponse extends Google_Collection
{
  protected $collection_key = 'tripOption';
  protected $internal_gapi_mappings = array(
  );
  protected $dataType = 'Google_Service_QPXExpress_Data';
  protected $dataDataType = '';
  public $kind;
  public $requestId;
  protected $tripOptionType = 'Google_Service_QPXExpress_TripOption';
  protected $tripOptionDataType = 'array';


  public function setData(Google_Service_QPXExpress_Data $data)
  {
    $this->data = $data;
  }
  public function getData()
  {
    return $this->data;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRequestId($requestId)
  {
    $this->requestId = $requestId;
  }
  public function getRequestId()
  {
    return $this->requestId;
  }
  public function setTripOption($tripOption)
  {
    $this->tripOption = $tripOption;
  }
  public function getTripOption()
  {
    return $this->tripOption;
  }
}

class Google_Service_QPXExpress_TripsSearchRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $requestType = 'Google_Service_QPXExpress_TripOptionsRequest';
  protected $requestDataType = '';


  public function setRequest(Google_Service_QPXExpress_TripOptionsRequest $request)
  {
    $this->request = $request;
  }
  public function getRequest()
  {
    return $this->request;
  }
}

class Google_Service_QPXExpress_TripsSearchResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $tripsType = 'Google_Service_QPXExpress_TripOptionsResponse';
  protected $tripsDataType = '';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setTrips(Google_Service_QPXExpress_TripOptionsResponse $trips)
  {
    $this->trips = $trips;
  }
  public function getTrips()
  {
    return $this->trips;
  }
}
