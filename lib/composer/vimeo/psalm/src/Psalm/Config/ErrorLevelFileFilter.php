<?php
namespace Psalm\Config;

use function in_array;
use SimpleXMLElement;

class ErrorLevelFileFilter extends FileFilter
{
    /**
     * @var string
     */
    private $error_level = '';

    /**
     * @return static
     */
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        string $base_dir,
        bool $inclusive
    ): ErrorLevelFileFilter {
        $filter = parent::loadFromXMLElement($e, $base_dir, $inclusive);

        if (isset($e['type'])) {
            $filter->error_level = (string) $e['type'];

            if (!in_array($filter->error_level, \Psalm\Config::$ERROR_LEVELS, true)) {
                throw new \Psalm\Exception\ConfigException('Unexpected error level ' . $filter->error_level);
            }
        } else {
            throw new \Psalm\Exception\ConfigException('<type> element expects a level');
        }

        return $filter;
    }

    public function getErrorLevel(): string
    {
        return $this->error_level;
    }
}
