<?php

namespace libphonenumber;

interface MetadataSourceInterface
{
    /**
     * Gets phone metadata for a region.
     * @param string $regionCode the region code.
     * @return PhoneMetadata the phone metadata for that region, or null if there is none.
     */
    public function getMetadataForRegion($regionCode);

    /**
     * Gets phone metadata for a non-geographical region.
     * @param int $countryCallingCode the country calling code.
     * @return PhoneMetadata the phone metadata for that region, or null if there is none.
     */
    public function getMetadataForNonGeographicalRegion($countryCallingCode);
}
