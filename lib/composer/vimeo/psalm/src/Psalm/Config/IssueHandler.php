<?php
namespace Psalm\Config;

use function array_filter;
use function array_map;
use function dirname;
use function in_array;
use function scandir;
use SimpleXMLElement;
use function strtolower;
use function substr;
use const SCANDIR_SORT_NONE;

class IssueHandler
{
    /**
     * @var string
     */
    private $error_level = \Psalm\Config::REPORT_ERROR;

    /**
     * @var array<ErrorLevelFileFilter>
     */
    private $custom_levels = [];

    public static function loadFromXMLElement(SimpleXMLElement $e, string $base_dir): IssueHandler
    {
        $handler = new self();

        if (isset($e['errorLevel'])) {
            $handler->error_level = (string) $e['errorLevel'];

            if (!in_array($handler->error_level, \Psalm\Config::$ERROR_LEVELS, true)) {
                throw new \Psalm\Exception\ConfigException('Unexpected error level ' . $handler->error_level);
            }
        }

        /** @var \SimpleXMLElement $error_level */
        foreach ($e->errorLevel as $error_level) {
            $handler->custom_levels[] = ErrorLevelFileFilter::loadFromXMLElement($error_level, $base_dir, true);
        }

        return $handler;
    }

    public function setErrorLevel(string $error_level): void
    {
        if (!in_array($error_level, \Psalm\Config::$ERROR_LEVELS, true)) {
            throw new \Psalm\Exception\ConfigException('Unexpected error level ' . $error_level);
        }

        $this->error_level = $error_level;
    }

    public function getReportingLevelForFile(string $file_path): string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allows($file_path)) {
                return $custom_level->getErrorLevel();
            }
        }

        return $this->error_level;
    }

    public function getReportingLevelForClass(string $fq_classlike_name): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsClass($fq_classlike_name)) {
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForMethod(string $method_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($method_id))) {
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForFunction(string $function_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($function_id))) {
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForArgument(string $function_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($function_id))) {
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForProperty(string $property_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsProperty($property_id)) {
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForVariable(string $var_name): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsVariable($var_name)) {
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function getAllIssueTypes(): array
    {
        return array_filter(
            array_map(
                /**
                 * @param string $file_name
                 *
                 * @return string
                 */
                function ($file_name) {
                    return substr($file_name, 0, -4);
                },
                scandir(dirname(__DIR__) . '/Issue', SCANDIR_SORT_NONE)
            ),
            function (string $issue_name): bool {
                return $issue_name !== ''
                    && $issue_name !== 'MethodIssue'
                    && $issue_name !== 'PropertyIssue'
                    && $issue_name !== 'FunctionIssue'
                    && $issue_name !== 'ArgumentIssue'
                    && $issue_name !== 'VariableIssue'
                    && $issue_name !== 'ClassIssue'
                    && $issue_name !== 'CodeIssue'
                    && $issue_name !== 'PsalmInternalError'
                    && $issue_name !== 'ParseError'
                    && $issue_name !== 'PluginIssue';
            }
        );
    }
}
