<?php
namespace Psalm\Storage;

/**
 * @psalm-type _MetadataEntry scalar|scalar[]|scalar[][]|scalar[][][]|scalar[][][][]|scalar[][][][][]
 */
trait CustomMetadataTrait
{
    /** @var array<string,_MetadataEntry> */
    public $custom_metadata = [];
}
