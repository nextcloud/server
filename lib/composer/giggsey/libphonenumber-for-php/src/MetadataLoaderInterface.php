<?php

namespace libphonenumber;

interface MetadataLoaderInterface
{
    /**
     * @param string $metadataFileName File name (including path) of metadata to load.
     * @return mixed
     */
    public function loadMetadata($metadataFileName);
}
