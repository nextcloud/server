<?php
/**
 *
 *
 * @author joshuag
 * @created: 04/08/2015 09:03
 * @project libphonenumber-for-php
 */

namespace libphonenumber;

class MultiFileMetadataSourceImpl implements MetadataSourceInterface
{
    protected static $metaDataFilePrefix = PhoneNumberUtil::META_DATA_FILE_PREFIX;

    /**
     * A mapping from a region code to the PhoneMetadata for that region.
     * @var PhoneMetadata[]
     */
    protected $regionToMetadataMap = array();

    /**
     * A mapping from a country calling code for a non-geographical entity to the PhoneMetadata for
     * that country calling code. Examples of the country calling codes include 800 (International
     * Toll Free Service) and 808 (International Shared Cost Service).
     * @var PhoneMetadata[]
     */
    protected $countryCodeToNonGeographicalMetadataMap = array();

    /**
     * The prefix of the metadata files from which region data is loaded.
     * @var String
     */
    protected $currentFilePrefix;


    /**
     * The metadata loader used to inject alternative metadata sources.
     * @var MetadataLoaderInterface
     */
    protected $metadataLoader;

    /**
     * @param MetadataLoaderInterface $metadataLoader
     * @param string|null $currentFilePrefix
     */
    public function __construct(MetadataLoaderInterface $metadataLoader, $currentFilePrefix = null)
    {
        if ($currentFilePrefix === null) {
            $currentFilePrefix = static::$metaDataFilePrefix;
        }

        $this->currentFilePrefix = $currentFilePrefix;
        $this->metadataLoader = $metadataLoader;
    }

    /**
     * @inheritdoc
     */
    public function getMetadataForRegion($regionCode)
    {
        if (!array_key_exists($regionCode, $this->regionToMetadataMap)) {
            // The regionCode here will be valid and won't be '001', so we don't need to worry about
            // what to pass in for the country calling code.
            $this->loadMetadataFromFile($this->currentFilePrefix, $regionCode, 0, $this->metadataLoader);
        }

        return $this->regionToMetadataMap[$regionCode];
    }

    /**
     * @inheritdoc
     */
    public function getMetadataForNonGeographicalRegion($countryCallingCode)
    {
        if (!array_key_exists($countryCallingCode, $this->countryCodeToNonGeographicalMetadataMap)) {
            $this->loadMetadataFromFile($this->currentFilePrefix, PhoneNumberUtil::REGION_CODE_FOR_NON_GEO_ENTITY, $countryCallingCode, $this->metadataLoader);
        }

        return $this->countryCodeToNonGeographicalMetadataMap[$countryCallingCode];
    }

    /**
     * @param string $filePrefix
     * @param string $regionCode
     * @param int $countryCallingCode
     * @param MetadataLoaderInterface $metadataLoader
     * @throws \RuntimeException
     */
    public function loadMetadataFromFile($filePrefix, $regionCode, $countryCallingCode, MetadataLoaderInterface $metadataLoader)
    {
        $isNonGeoRegion = PhoneNumberUtil::REGION_CODE_FOR_NON_GEO_ENTITY === $regionCode;
        $fileName = $filePrefix . '_' . ($isNonGeoRegion ? $countryCallingCode : $regionCode) . '.php';
        if (!is_readable($fileName)) {
            throw new \RuntimeException('missing metadata: ' . $fileName);
        }

        $data = $metadataLoader->loadMetadata($fileName);
        $metadata = new PhoneMetadata();
        $metadata->fromArray($data);
        if ($isNonGeoRegion) {
            $this->countryCodeToNonGeographicalMetadataMap[$countryCallingCode] = $metadata;
        } else {
            $this->regionToMetadataMap[$regionCode] = $metadata;
        }
    }
}
