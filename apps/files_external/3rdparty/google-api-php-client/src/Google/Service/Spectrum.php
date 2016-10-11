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
 * Service definition for Spectrum (v1explorer).
 *
 * <p>
 * API for spectrum-management functions.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="http://developers.google.com/spectrum" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Spectrum extends Google_Service
{


  public $paws;
  

  /**
   * Constructs the internal representation of the Spectrum service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'spectrum/v1explorer/paws/';
    $this->version = 'v1explorer';
    $this->serviceName = 'spectrum';

    $this->paws = new Google_Service_Spectrum_Paws_Resource(
        $this,
        $this->serviceName,
        'paws',
        array(
          'methods' => array(
            'getSpectrum' => array(
              'path' => 'getSpectrum',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'getSpectrumBatch' => array(
              'path' => 'getSpectrumBatch',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'init' => array(
              'path' => 'init',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'notifySpectrumUse' => array(
              'path' => 'notifySpectrumUse',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'register' => array(
              'path' => 'register',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'verifyDevice' => array(
              'path' => 'verifyDevice',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
  }
}


/**
 * The "paws" collection of methods.
 * Typical usage is:
 *  <code>
 *   $spectrumService = new Google_Service_Spectrum(...);
 *   $paws = $spectrumService->paws;
 *  </code>
 */
class Google_Service_Spectrum_Paws_Resource extends Google_Service_Resource
{

  /**
   * Requests information about the available spectrum for a device at a location.
   * Requests from a fixed-mode device must include owner information so the
   * device can be registered with the database. (paws.getSpectrum)
   *
   * @param Google_PawsGetSpectrumRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Spectrum_PawsGetSpectrumResponse
   */
  public function getSpectrum(Google_Service_Spectrum_PawsGetSpectrumRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('getSpectrum', array($params), "Google_Service_Spectrum_PawsGetSpectrumResponse");
  }

  /**
   * The Google Spectrum Database does not support batch requests, so this method
   * always yields an UNIMPLEMENTED error. (paws.getSpectrumBatch)
   *
   * @param Google_PawsGetSpectrumBatchRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Spectrum_PawsGetSpectrumBatchResponse
   */
  public function getSpectrumBatch(Google_Service_Spectrum_PawsGetSpectrumBatchRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('getSpectrumBatch', array($params), "Google_Service_Spectrum_PawsGetSpectrumBatchResponse");
  }

  /**
   * Initializes the connection between a white space device and the database.
   * (paws.init)
   *
   * @param Google_PawsInitRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Spectrum_PawsInitResponse
   */
  public function init(Google_Service_Spectrum_PawsInitRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('init', array($params), "Google_Service_Spectrum_PawsInitResponse");
  }

  /**
   * Notifies the database that the device has selected certain frequency ranges
   * for transmission. Only to be invoked when required by the regulator. The
   * Google Spectrum Database does not operate in domains that require
   * notification, so this always yields an UNIMPLEMENTED error.
   * (paws.notifySpectrumUse)
   *
   * @param Google_PawsNotifySpectrumUseRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Spectrum_PawsNotifySpectrumUseResponse
   */
  public function notifySpectrumUse(Google_Service_Spectrum_PawsNotifySpectrumUseRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('notifySpectrumUse', array($params), "Google_Service_Spectrum_PawsNotifySpectrumUseResponse");
  }

  /**
   * The Google Spectrum Database implements registration in the getSpectrum
   * method. As such this always returns an UNIMPLEMENTED error. (paws.register)
   *
   * @param Google_PawsRegisterRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Spectrum_PawsRegisterResponse
   */
  public function register(Google_Service_Spectrum_PawsRegisterRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('register', array($params), "Google_Service_Spectrum_PawsRegisterResponse");
  }

  /**
   * Validates a device for white space use in accordance with regulatory rules.
   * The Google Spectrum Database does not support master/slave configurations, so
   * this always yields an UNIMPLEMENTED error. (paws.verifyDevice)
   *
   * @param Google_PawsVerifyDeviceRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Spectrum_PawsVerifyDeviceResponse
   */
  public function verifyDevice(Google_Service_Spectrum_PawsVerifyDeviceRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('verifyDevice', array($params), "Google_Service_Spectrum_PawsVerifyDeviceResponse");
  }
}




class Google_Service_Spectrum_AntennaCharacteristics extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $heightType;
  public $heightUncertainty;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setHeightType($heightType)
  {
    $this->heightType = $heightType;
  }
  public function getHeightType()
  {
    return $this->heightType;
  }
  public function setHeightUncertainty($heightUncertainty)
  {
    $this->heightUncertainty = $heightUncertainty;
  }
  public function getHeightUncertainty()
  {
    return $this->heightUncertainty;
  }
}

class Google_Service_Spectrum_DatabaseSpec extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $uri;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  public function getUri()
  {
    return $this->uri;
  }
}

class Google_Service_Spectrum_DbUpdateSpec extends Google_Collection
{
  protected $collection_key = 'databases';
  protected $internal_gapi_mappings = array(
  );
  protected $databasesType = 'Google_Service_Spectrum_DatabaseSpec';
  protected $databasesDataType = 'array';


  public function setDatabases($databases)
  {
    $this->databases = $databases;
  }
  public function getDatabases()
  {
    return $this->databases;
  }
}

class Google_Service_Spectrum_DeviceCapabilities extends Google_Collection
{
  protected $collection_key = 'frequencyRanges';
  protected $internal_gapi_mappings = array(
  );
  protected $frequencyRangesType = 'Google_Service_Spectrum_FrequencyRange';
  protected $frequencyRangesDataType = 'array';


  public function setFrequencyRanges($frequencyRanges)
  {
    $this->frequencyRanges = $frequencyRanges;
  }
  public function getFrequencyRanges()
  {
    return $this->frequencyRanges;
  }
}

class Google_Service_Spectrum_DeviceDescriptor extends Google_Collection
{
  protected $collection_key = 'rulesetIds';
  protected $internal_gapi_mappings = array(
  );
  public $etsiEnDeviceCategory;
  public $etsiEnDeviceEmissionsClass;
  public $etsiEnDeviceType;
  public $etsiEnTechnologyId;
  public $fccId;
  public $fccTvbdDeviceType;
  public $manufacturerId;
  public $modelId;
  public $rulesetIds;
  public $serialNumber;


  public function setEtsiEnDeviceCategory($etsiEnDeviceCategory)
  {
    $this->etsiEnDeviceCategory = $etsiEnDeviceCategory;
  }
  public function getEtsiEnDeviceCategory()
  {
    return $this->etsiEnDeviceCategory;
  }
  public function setEtsiEnDeviceEmissionsClass($etsiEnDeviceEmissionsClass)
  {
    $this->etsiEnDeviceEmissionsClass = $etsiEnDeviceEmissionsClass;
  }
  public function getEtsiEnDeviceEmissionsClass()
  {
    return $this->etsiEnDeviceEmissionsClass;
  }
  public function setEtsiEnDeviceType($etsiEnDeviceType)
  {
    $this->etsiEnDeviceType = $etsiEnDeviceType;
  }
  public function getEtsiEnDeviceType()
  {
    return $this->etsiEnDeviceType;
  }
  public function setEtsiEnTechnologyId($etsiEnTechnologyId)
  {
    $this->etsiEnTechnologyId = $etsiEnTechnologyId;
  }
  public function getEtsiEnTechnologyId()
  {
    return $this->etsiEnTechnologyId;
  }
  public function setFccId($fccId)
  {
    $this->fccId = $fccId;
  }
  public function getFccId()
  {
    return $this->fccId;
  }
  public function setFccTvbdDeviceType($fccTvbdDeviceType)
  {
    $this->fccTvbdDeviceType = $fccTvbdDeviceType;
  }
  public function getFccTvbdDeviceType()
  {
    return $this->fccTvbdDeviceType;
  }
  public function setManufacturerId($manufacturerId)
  {
    $this->manufacturerId = $manufacturerId;
  }
  public function getManufacturerId()
  {
    return $this->manufacturerId;
  }
  public function setModelId($modelId)
  {
    $this->modelId = $modelId;
  }
  public function getModelId()
  {
    return $this->modelId;
  }
  public function setRulesetIds($rulesetIds)
  {
    $this->rulesetIds = $rulesetIds;
  }
  public function getRulesetIds()
  {
    return $this->rulesetIds;
  }
  public function setSerialNumber($serialNumber)
  {
    $this->serialNumber = $serialNumber;
  }
  public function getSerialNumber()
  {
    return $this->serialNumber;
  }
}

class Google_Service_Spectrum_DeviceOwner extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $operatorType = 'Google_Service_Spectrum_Vcard';
  protected $operatorDataType = '';
  protected $ownerType = 'Google_Service_Spectrum_Vcard';
  protected $ownerDataType = '';


  public function setOperator(Google_Service_Spectrum_Vcard $operator)
  {
    $this->operator = $operator;
  }
  public function getOperator()
  {
    return $this->operator;
  }
  public function setOwner(Google_Service_Spectrum_Vcard $owner)
  {
    $this->owner = $owner;
  }
  public function getOwner()
  {
    return $this->owner;
  }
}

class Google_Service_Spectrum_DeviceValidity extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $deviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescDataType = '';
  public $isValid;
  public $reason;


  public function setDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $deviceDesc)
  {
    $this->deviceDesc = $deviceDesc;
  }
  public function getDeviceDesc()
  {
    return $this->deviceDesc;
  }
  public function setIsValid($isValid)
  {
    $this->isValid = $isValid;
  }
  public function getIsValid()
  {
    return $this->isValid;
  }
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  public function getReason()
  {
    return $this->reason;
  }
}

class Google_Service_Spectrum_EventTime extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $startTime;
  public $stopTime;


  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  public function getStartTime()
  {
    return $this->startTime;
  }
  public function setStopTime($stopTime)
  {
    $this->stopTime = $stopTime;
  }
  public function getStopTime()
  {
    return $this->stopTime;
  }
}

class Google_Service_Spectrum_FrequencyRange extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $maxPowerDBm;
  public $startHz;
  public $stopHz;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setMaxPowerDBm($maxPowerDBm)
  {
    $this->maxPowerDBm = $maxPowerDBm;
  }
  public function getMaxPowerDBm()
  {
    return $this->maxPowerDBm;
  }
  public function setStartHz($startHz)
  {
    $this->startHz = $startHz;
  }
  public function getStartHz()
  {
    return $this->startHz;
  }
  public function setStopHz($stopHz)
  {
    $this->stopHz = $stopHz;
  }
  public function getStopHz()
  {
    return $this->stopHz;
  }
}

class Google_Service_Spectrum_GeoLocation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $confidence;
  protected $pointType = 'Google_Service_Spectrum_GeoLocationEllipse';
  protected $pointDataType = '';
  protected $regionType = 'Google_Service_Spectrum_GeoLocationPolygon';
  protected $regionDataType = '';


  public function setConfidence($confidence)
  {
    $this->confidence = $confidence;
  }
  public function getConfidence()
  {
    return $this->confidence;
  }
  public function setPoint(Google_Service_Spectrum_GeoLocationEllipse $point)
  {
    $this->point = $point;
  }
  public function getPoint()
  {
    return $this->point;
  }
  public function setRegion(Google_Service_Spectrum_GeoLocationPolygon $region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
}

class Google_Service_Spectrum_GeoLocationEllipse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $centerType = 'Google_Service_Spectrum_GeoLocationPoint';
  protected $centerDataType = '';
  public $orientation;
  public $semiMajorAxis;
  public $semiMinorAxis;


  public function setCenter(Google_Service_Spectrum_GeoLocationPoint $center)
  {
    $this->center = $center;
  }
  public function getCenter()
  {
    return $this->center;
  }
  public function setOrientation($orientation)
  {
    $this->orientation = $orientation;
  }
  public function getOrientation()
  {
    return $this->orientation;
  }
  public function setSemiMajorAxis($semiMajorAxis)
  {
    $this->semiMajorAxis = $semiMajorAxis;
  }
  public function getSemiMajorAxis()
  {
    return $this->semiMajorAxis;
  }
  public function setSemiMinorAxis($semiMinorAxis)
  {
    $this->semiMinorAxis = $semiMinorAxis;
  }
  public function getSemiMinorAxis()
  {
    return $this->semiMinorAxis;
  }
}

class Google_Service_Spectrum_GeoLocationPoint extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $latitude;
  public $longitude;


  public function setLatitude($latitude)
  {
    $this->latitude = $latitude;
  }
  public function getLatitude()
  {
    return $this->latitude;
  }
  public function setLongitude($longitude)
  {
    $this->longitude = $longitude;
  }
  public function getLongitude()
  {
    return $this->longitude;
  }
}

class Google_Service_Spectrum_GeoLocationPolygon extends Google_Collection
{
  protected $collection_key = 'exterior';
  protected $internal_gapi_mappings = array(
  );
  protected $exteriorType = 'Google_Service_Spectrum_GeoLocationPoint';
  protected $exteriorDataType = 'array';


  public function setExterior($exterior)
  {
    $this->exterior = $exterior;
  }
  public function getExterior()
  {
    return $this->exterior;
  }
}

class Google_Service_Spectrum_GeoSpectrumSchedule extends Google_Collection
{
  protected $collection_key = 'spectrumSchedules';
  protected $internal_gapi_mappings = array(
  );
  protected $locationType = 'Google_Service_Spectrum_GeoLocation';
  protected $locationDataType = '';
  protected $spectrumSchedulesType = 'Google_Service_Spectrum_SpectrumSchedule';
  protected $spectrumSchedulesDataType = 'array';


  public function setLocation(Google_Service_Spectrum_GeoLocation $location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setSpectrumSchedules($spectrumSchedules)
  {
    $this->spectrumSchedules = $spectrumSchedules;
  }
  public function getSpectrumSchedules()
  {
    return $this->spectrumSchedules;
  }
}

class Google_Service_Spectrum_PawsGetSpectrumBatchRequest extends Google_Collection
{
  protected $collection_key = 'locations';
  protected $internal_gapi_mappings = array(
  );
  protected $antennaType = 'Google_Service_Spectrum_AntennaCharacteristics';
  protected $antennaDataType = '';
  protected $capabilitiesType = 'Google_Service_Spectrum_DeviceCapabilities';
  protected $capabilitiesDataType = '';
  protected $deviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescDataType = '';
  protected $locationsType = 'Google_Service_Spectrum_GeoLocation';
  protected $locationsDataType = 'array';
  protected $masterDeviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $masterDeviceDescDataType = '';
  protected $ownerType = 'Google_Service_Spectrum_DeviceOwner';
  protected $ownerDataType = '';
  public $requestType;
  public $type;
  public $version;


  public function setAntenna(Google_Service_Spectrum_AntennaCharacteristics $antenna)
  {
    $this->antenna = $antenna;
  }
  public function getAntenna()
  {
    return $this->antenna;
  }
  public function setCapabilities(Google_Service_Spectrum_DeviceCapabilities $capabilities)
  {
    $this->capabilities = $capabilities;
  }
  public function getCapabilities()
  {
    return $this->capabilities;
  }
  public function setDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $deviceDesc)
  {
    $this->deviceDesc = $deviceDesc;
  }
  public function getDeviceDesc()
  {
    return $this->deviceDesc;
  }
  public function setLocations($locations)
  {
    $this->locations = $locations;
  }
  public function getLocations()
  {
    return $this->locations;
  }
  public function setMasterDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $masterDeviceDesc)
  {
    $this->masterDeviceDesc = $masterDeviceDesc;
  }
  public function getMasterDeviceDesc()
  {
    return $this->masterDeviceDesc;
  }
  public function setOwner(Google_Service_Spectrum_DeviceOwner $owner)
  {
    $this->owner = $owner;
  }
  public function getOwner()
  {
    return $this->owner;
  }
  public function setRequestType($requestType)
  {
    $this->requestType = $requestType;
  }
  public function getRequestType()
  {
    return $this->requestType;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsGetSpectrumBatchResponse extends Google_Collection
{
  protected $collection_key = 'geoSpectrumSchedules';
  protected $internal_gapi_mappings = array(
  );
  protected $databaseChangeType = 'Google_Service_Spectrum_DbUpdateSpec';
  protected $databaseChangeDataType = '';
  protected $deviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescDataType = '';
  protected $geoSpectrumSchedulesType = 'Google_Service_Spectrum_GeoSpectrumSchedule';
  protected $geoSpectrumSchedulesDataType = 'array';
  public $kind;
  public $maxContiguousBwHz;
  public $maxTotalBwHz;
  public $needsSpectrumReport;
  protected $rulesetInfoType = 'Google_Service_Spectrum_RulesetInfo';
  protected $rulesetInfoDataType = '';
  public $timestamp;
  public $type;
  public $version;


  public function setDatabaseChange(Google_Service_Spectrum_DbUpdateSpec $databaseChange)
  {
    $this->databaseChange = $databaseChange;
  }
  public function getDatabaseChange()
  {
    return $this->databaseChange;
  }
  public function setDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $deviceDesc)
  {
    $this->deviceDesc = $deviceDesc;
  }
  public function getDeviceDesc()
  {
    return $this->deviceDesc;
  }
  public function setGeoSpectrumSchedules($geoSpectrumSchedules)
  {
    $this->geoSpectrumSchedules = $geoSpectrumSchedules;
  }
  public function getGeoSpectrumSchedules()
  {
    return $this->geoSpectrumSchedules;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMaxContiguousBwHz($maxContiguousBwHz)
  {
    $this->maxContiguousBwHz = $maxContiguousBwHz;
  }
  public function getMaxContiguousBwHz()
  {
    return $this->maxContiguousBwHz;
  }
  public function setMaxTotalBwHz($maxTotalBwHz)
  {
    $this->maxTotalBwHz = $maxTotalBwHz;
  }
  public function getMaxTotalBwHz()
  {
    return $this->maxTotalBwHz;
  }
  public function setNeedsSpectrumReport($needsSpectrumReport)
  {
    $this->needsSpectrumReport = $needsSpectrumReport;
  }
  public function getNeedsSpectrumReport()
  {
    return $this->needsSpectrumReport;
  }
  public function setRulesetInfo(Google_Service_Spectrum_RulesetInfo $rulesetInfo)
  {
    $this->rulesetInfo = $rulesetInfo;
  }
  public function getRulesetInfo()
  {
    return $this->rulesetInfo;
  }
  public function setTimestamp($timestamp)
  {
    $this->timestamp = $timestamp;
  }
  public function getTimestamp()
  {
    return $this->timestamp;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsGetSpectrumRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $antennaType = 'Google_Service_Spectrum_AntennaCharacteristics';
  protected $antennaDataType = '';
  protected $capabilitiesType = 'Google_Service_Spectrum_DeviceCapabilities';
  protected $capabilitiesDataType = '';
  protected $deviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescDataType = '';
  protected $locationType = 'Google_Service_Spectrum_GeoLocation';
  protected $locationDataType = '';
  protected $masterDeviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $masterDeviceDescDataType = '';
  protected $ownerType = 'Google_Service_Spectrum_DeviceOwner';
  protected $ownerDataType = '';
  public $requestType;
  public $type;
  public $version;


  public function setAntenna(Google_Service_Spectrum_AntennaCharacteristics $antenna)
  {
    $this->antenna = $antenna;
  }
  public function getAntenna()
  {
    return $this->antenna;
  }
  public function setCapabilities(Google_Service_Spectrum_DeviceCapabilities $capabilities)
  {
    $this->capabilities = $capabilities;
  }
  public function getCapabilities()
  {
    return $this->capabilities;
  }
  public function setDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $deviceDesc)
  {
    $this->deviceDesc = $deviceDesc;
  }
  public function getDeviceDesc()
  {
    return $this->deviceDesc;
  }
  public function setLocation(Google_Service_Spectrum_GeoLocation $location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setMasterDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $masterDeviceDesc)
  {
    $this->masterDeviceDesc = $masterDeviceDesc;
  }
  public function getMasterDeviceDesc()
  {
    return $this->masterDeviceDesc;
  }
  public function setOwner(Google_Service_Spectrum_DeviceOwner $owner)
  {
    $this->owner = $owner;
  }
  public function getOwner()
  {
    return $this->owner;
  }
  public function setRequestType($requestType)
  {
    $this->requestType = $requestType;
  }
  public function getRequestType()
  {
    return $this->requestType;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsGetSpectrumResponse extends Google_Collection
{
  protected $collection_key = 'spectrumSchedules';
  protected $internal_gapi_mappings = array(
  );
  protected $databaseChangeType = 'Google_Service_Spectrum_DbUpdateSpec';
  protected $databaseChangeDataType = '';
  protected $deviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescDataType = '';
  public $kind;
  public $maxContiguousBwHz;
  public $maxTotalBwHz;
  public $needsSpectrumReport;
  protected $rulesetInfoType = 'Google_Service_Spectrum_RulesetInfo';
  protected $rulesetInfoDataType = '';
  protected $spectrumSchedulesType = 'Google_Service_Spectrum_SpectrumSchedule';
  protected $spectrumSchedulesDataType = 'array';
  public $timestamp;
  public $type;
  public $version;


  public function setDatabaseChange(Google_Service_Spectrum_DbUpdateSpec $databaseChange)
  {
    $this->databaseChange = $databaseChange;
  }
  public function getDatabaseChange()
  {
    return $this->databaseChange;
  }
  public function setDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $deviceDesc)
  {
    $this->deviceDesc = $deviceDesc;
  }
  public function getDeviceDesc()
  {
    return $this->deviceDesc;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMaxContiguousBwHz($maxContiguousBwHz)
  {
    $this->maxContiguousBwHz = $maxContiguousBwHz;
  }
  public function getMaxContiguousBwHz()
  {
    return $this->maxContiguousBwHz;
  }
  public function setMaxTotalBwHz($maxTotalBwHz)
  {
    $this->maxTotalBwHz = $maxTotalBwHz;
  }
  public function getMaxTotalBwHz()
  {
    return $this->maxTotalBwHz;
  }
  public function setNeedsSpectrumReport($needsSpectrumReport)
  {
    $this->needsSpectrumReport = $needsSpectrumReport;
  }
  public function getNeedsSpectrumReport()
  {
    return $this->needsSpectrumReport;
  }
  public function setRulesetInfo(Google_Service_Spectrum_RulesetInfo $rulesetInfo)
  {
    $this->rulesetInfo = $rulesetInfo;
  }
  public function getRulesetInfo()
  {
    return $this->rulesetInfo;
  }
  public function setSpectrumSchedules($spectrumSchedules)
  {
    $this->spectrumSchedules = $spectrumSchedules;
  }
  public function getSpectrumSchedules()
  {
    return $this->spectrumSchedules;
  }
  public function setTimestamp($timestamp)
  {
    $this->timestamp = $timestamp;
  }
  public function getTimestamp()
  {
    return $this->timestamp;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsInitRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $deviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescDataType = '';
  protected $locationType = 'Google_Service_Spectrum_GeoLocation';
  protected $locationDataType = '';
  public $type;
  public $version;


  public function setDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $deviceDesc)
  {
    $this->deviceDesc = $deviceDesc;
  }
  public function getDeviceDesc()
  {
    return $this->deviceDesc;
  }
  public function setLocation(Google_Service_Spectrum_GeoLocation $location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsInitResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $databaseChangeType = 'Google_Service_Spectrum_DbUpdateSpec';
  protected $databaseChangeDataType = '';
  public $kind;
  protected $rulesetInfoType = 'Google_Service_Spectrum_RulesetInfo';
  protected $rulesetInfoDataType = '';
  public $type;
  public $version;


  public function setDatabaseChange(Google_Service_Spectrum_DbUpdateSpec $databaseChange)
  {
    $this->databaseChange = $databaseChange;
  }
  public function getDatabaseChange()
  {
    return $this->databaseChange;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRulesetInfo(Google_Service_Spectrum_RulesetInfo $rulesetInfo)
  {
    $this->rulesetInfo = $rulesetInfo;
  }
  public function getRulesetInfo()
  {
    return $this->rulesetInfo;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsNotifySpectrumUseRequest extends Google_Collection
{
  protected $collection_key = 'spectra';
  protected $internal_gapi_mappings = array(
  );
  protected $deviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescDataType = '';
  protected $locationType = 'Google_Service_Spectrum_GeoLocation';
  protected $locationDataType = '';
  protected $spectraType = 'Google_Service_Spectrum_SpectrumMessage';
  protected $spectraDataType = 'array';
  public $type;
  public $version;


  public function setDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $deviceDesc)
  {
    $this->deviceDesc = $deviceDesc;
  }
  public function getDeviceDesc()
  {
    return $this->deviceDesc;
  }
  public function setLocation(Google_Service_Spectrum_GeoLocation $location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setSpectra($spectra)
  {
    $this->spectra = $spectra;
  }
  public function getSpectra()
  {
    return $this->spectra;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsNotifySpectrumUseResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $type;
  public $version;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsRegisterRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $antennaType = 'Google_Service_Spectrum_AntennaCharacteristics';
  protected $antennaDataType = '';
  protected $deviceDescType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescDataType = '';
  protected $deviceOwnerType = 'Google_Service_Spectrum_DeviceOwner';
  protected $deviceOwnerDataType = '';
  protected $locationType = 'Google_Service_Spectrum_GeoLocation';
  protected $locationDataType = '';
  public $type;
  public $version;


  public function setAntenna(Google_Service_Spectrum_AntennaCharacteristics $antenna)
  {
    $this->antenna = $antenna;
  }
  public function getAntenna()
  {
    return $this->antenna;
  }
  public function setDeviceDesc(Google_Service_Spectrum_DeviceDescriptor $deviceDesc)
  {
    $this->deviceDesc = $deviceDesc;
  }
  public function getDeviceDesc()
  {
    return $this->deviceDesc;
  }
  public function setDeviceOwner(Google_Service_Spectrum_DeviceOwner $deviceOwner)
  {
    $this->deviceOwner = $deviceOwner;
  }
  public function getDeviceOwner()
  {
    return $this->deviceOwner;
  }
  public function setLocation(Google_Service_Spectrum_GeoLocation $location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsRegisterResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $databaseChangeType = 'Google_Service_Spectrum_DbUpdateSpec';
  protected $databaseChangeDataType = '';
  public $kind;
  public $type;
  public $version;


  public function setDatabaseChange(Google_Service_Spectrum_DbUpdateSpec $databaseChange)
  {
    $this->databaseChange = $databaseChange;
  }
  public function getDatabaseChange()
  {
    return $this->databaseChange;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsVerifyDeviceRequest extends Google_Collection
{
  protected $collection_key = 'deviceDescs';
  protected $internal_gapi_mappings = array(
  );
  protected $deviceDescsType = 'Google_Service_Spectrum_DeviceDescriptor';
  protected $deviceDescsDataType = 'array';
  public $type;
  public $version;


  public function setDeviceDescs($deviceDescs)
  {
    $this->deviceDescs = $deviceDescs;
  }
  public function getDeviceDescs()
  {
    return $this->deviceDescs;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_PawsVerifyDeviceResponse extends Google_Collection
{
  protected $collection_key = 'deviceValidities';
  protected $internal_gapi_mappings = array(
  );
  protected $databaseChangeType = 'Google_Service_Spectrum_DbUpdateSpec';
  protected $databaseChangeDataType = '';
  protected $deviceValiditiesType = 'Google_Service_Spectrum_DeviceValidity';
  protected $deviceValiditiesDataType = 'array';
  public $kind;
  public $type;
  public $version;


  public function setDatabaseChange(Google_Service_Spectrum_DbUpdateSpec $databaseChange)
  {
    $this->databaseChange = $databaseChange;
  }
  public function getDatabaseChange()
  {
    return $this->databaseChange;
  }
  public function setDeviceValidities($deviceValidities)
  {
    $this->deviceValidities = $deviceValidities;
  }
  public function getDeviceValidities()
  {
    return $this->deviceValidities;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}

class Google_Service_Spectrum_RulesetInfo extends Google_Collection
{
  protected $collection_key = 'rulesetIds';
  protected $internal_gapi_mappings = array(
  );
  public $authority;
  public $maxLocationChange;
  public $maxPollingSecs;
  public $rulesetIds;


  public function setAuthority($authority)
  {
    $this->authority = $authority;
  }
  public function getAuthority()
  {
    return $this->authority;
  }
  public function setMaxLocationChange($maxLocationChange)
  {
    $this->maxLocationChange = $maxLocationChange;
  }
  public function getMaxLocationChange()
  {
    return $this->maxLocationChange;
  }
  public function setMaxPollingSecs($maxPollingSecs)
  {
    $this->maxPollingSecs = $maxPollingSecs;
  }
  public function getMaxPollingSecs()
  {
    return $this->maxPollingSecs;
  }
  public function setRulesetIds($rulesetIds)
  {
    $this->rulesetIds = $rulesetIds;
  }
  public function getRulesetIds()
  {
    return $this->rulesetIds;
  }
}

class Google_Service_Spectrum_SpectrumMessage extends Google_Collection
{
  protected $collection_key = 'frequencyRanges';
  protected $internal_gapi_mappings = array(
  );
  public $bandwidth;
  protected $frequencyRangesType = 'Google_Service_Spectrum_FrequencyRange';
  protected $frequencyRangesDataType = 'array';


  public function setBandwidth($bandwidth)
  {
    $this->bandwidth = $bandwidth;
  }
  public function getBandwidth()
  {
    return $this->bandwidth;
  }
  public function setFrequencyRanges($frequencyRanges)
  {
    $this->frequencyRanges = $frequencyRanges;
  }
  public function getFrequencyRanges()
  {
    return $this->frequencyRanges;
  }
}

class Google_Service_Spectrum_SpectrumSchedule extends Google_Collection
{
  protected $collection_key = 'spectra';
  protected $internal_gapi_mappings = array(
  );
  protected $eventTimeType = 'Google_Service_Spectrum_EventTime';
  protected $eventTimeDataType = '';
  protected $spectraType = 'Google_Service_Spectrum_SpectrumMessage';
  protected $spectraDataType = 'array';


  public function setEventTime(Google_Service_Spectrum_EventTime $eventTime)
  {
    $this->eventTime = $eventTime;
  }
  public function getEventTime()
  {
    return $this->eventTime;
  }
  public function setSpectra($spectra)
  {
    $this->spectra = $spectra;
  }
  public function getSpectra()
  {
    return $this->spectra;
  }
}

class Google_Service_Spectrum_Vcard extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $adrType = 'Google_Service_Spectrum_VcardAddress';
  protected $adrDataType = '';
  protected $emailType = 'Google_Service_Spectrum_VcardTypedText';
  protected $emailDataType = '';
  public $fn;
  protected $orgType = 'Google_Service_Spectrum_VcardTypedText';
  protected $orgDataType = '';
  protected $telType = 'Google_Service_Spectrum_VcardTelephone';
  protected $telDataType = '';


  public function setAdr(Google_Service_Spectrum_VcardAddress $adr)
  {
    $this->adr = $adr;
  }
  public function getAdr()
  {
    return $this->adr;
  }
  public function setEmail(Google_Service_Spectrum_VcardTypedText $email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setFn($fn)
  {
    $this->fn = $fn;
  }
  public function getFn()
  {
    return $this->fn;
  }
  public function setOrg(Google_Service_Spectrum_VcardTypedText $org)
  {
    $this->org = $org;
  }
  public function getOrg()
  {
    return $this->org;
  }
  public function setTel(Google_Service_Spectrum_VcardTelephone $tel)
  {
    $this->tel = $tel;
  }
  public function getTel()
  {
    return $this->tel;
  }
}

class Google_Service_Spectrum_VcardAddress extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $country;
  public $locality;
  public $pobox;
  public $region;
  public $street;


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
  public function setLocality($locality)
  {
    $this->locality = $locality;
  }
  public function getLocality()
  {
    return $this->locality;
  }
  public function setPobox($pobox)
  {
    $this->pobox = $pobox;
  }
  public function getPobox()
  {
    return $this->pobox;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setStreet($street)
  {
    $this->street = $street;
  }
  public function getStreet()
  {
    return $this->street;
  }
}

class Google_Service_Spectrum_VcardTelephone extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $uri;


  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  public function getUri()
  {
    return $this->uri;
  }
}

class Google_Service_Spectrum_VcardTypedText extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $text;


  public function setText($text)
  {
    $this->text = $text;
  }
  public function getText()
  {
    return $this->text;
  }
}
