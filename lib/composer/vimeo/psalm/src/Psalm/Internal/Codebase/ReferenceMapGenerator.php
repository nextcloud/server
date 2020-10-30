<?php
namespace Psalm\Internal\Codebase;

class ReferenceMapGenerator
{
    /**
     * @return array<string, string>
     */
    public static function getReferenceMap(
        \Psalm\Internal\Provider\ClassLikeStorageProvider $classlike_storage_provider,
        array $expected_references
    ) : array {
        $reference_dictionary = [];

        foreach ($classlike_storage_provider->getAll() as $storage) {
            if (!$storage->location) {
                continue;
            }

            $fq_classlike_name = $storage->name;

            if (isset($expected_references[$fq_classlike_name])) {
                $reference_dictionary[$fq_classlike_name]
                    = $storage->location->file_name
                        . ':' . $storage->location->getLineNumber()
                        . ':' . $storage->location->getColumn();
            }

            foreach ($storage->methods as $method_name => $method_storage) {
                if (!$method_storage->location) {
                    continue;
                }

                if (isset($expected_references[$fq_classlike_name . '::' . $method_name . '()'])) {
                    $reference_dictionary[$fq_classlike_name . '::' . $method_name . '()']
                        = $method_storage->location->file_name
                            . ':' . $method_storage->location->getLineNumber()
                            . ':' . $method_storage->location->getColumn();
                }
            }

            foreach ($storage->properties as $property_name => $property_storage) {
                if (!$property_storage->location) {
                    continue;
                }

                if (isset($expected_references[$fq_classlike_name . '::$' . $property_name])) {
                    $reference_dictionary[$fq_classlike_name . '::$' . $property_name]
                        = $property_storage->location->file_name
                            . ':' . $property_storage->location->getLineNumber()
                            . ':' . $property_storage->location->getColumn();
                }
            }
        }

        return $reference_dictionary;
    }
}
