<?php

namespace libphonenumber;

class DefaultMetadataLoader implements MetadataLoaderInterface
{
    public function loadMetadata($metadataFileName)
    {
        return include $metadataFileName;
    }
}
