<?php
namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Config;
use function explode;
use function get_called_class;
use function array_pop;

abstract class CodeIssue
{
    public const ERROR_LEVEL = -1;
    public const SHORTCODE = 0;

    /**
     * @var CodeLocation
     * @readonly
     */
    public $code_location;

    /**
     * @var string
     * @readonly
     */
    public $message;

    /**
     * @var ?string
     */
    public $dupe_key;

    public function __construct(
        string $message,
        CodeLocation $code_location
    ) {
        $this->code_location = $code_location;
        $this->message = $message;
    }

    /**
     * @deprecated
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getLocation(): CodeLocation
    {
        return $this->code_location;
    }

    public function getShortLocationWithPrevious(): string
    {
        $previous_text = '';

        if ($this->code_location->previous_location) {
            $previous_location = $this->code_location->previous_location;
            $previous_text = ' from ' . $previous_location->file_name . ':' . $previous_location->getLineNumber();
        }

        return $this->code_location->file_name . ':' . $this->code_location->getLineNumber() . $previous_text;
    }

    public function getShortLocation(): string
    {
        return $this->code_location->file_name . ':' . $this->code_location->getLineNumber();
    }

    public function getFilePath(): string
    {
        return $this->code_location->file_path;
    }

    /**
     * @deprecated
     * @psalm-suppress PossiblyUnusedMethod for convenience
     */
    public function getFileName(): string
    {
        return $this->code_location->file_name;
    }

    /**
     * @deprecated
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    public function toIssueData(string $severity = Config::REPORT_ERROR): \Psalm\Internal\Analyzer\IssueData
    {
        $location = $this->code_location;
        $selection_bounds = $location->getSelectionBounds();
        $snippet_bounds = $location->getSnippetBounds();

        $fqcn_parts = explode('\\', get_called_class());
        $issue_type = array_pop($fqcn_parts);

        return new \Psalm\Internal\Analyzer\IssueData(
            $severity,
            $location->getLineNumber(),
            $location->getEndLineNumber(),
            $issue_type,
            $this->message,
            $location->file_name,
            $location->file_path,
            $location->getSnippet(),
            $location->getSelectedText(),
            $selection_bounds[0],
            $selection_bounds[1],
            $snippet_bounds[0],
            $snippet_bounds[1],
            $location->getColumn(),
            $location->getEndColumn(),
            (int) static::SHORTCODE,
            (int) static::ERROR_LEVEL,
            $this instanceof TaintedInput ? $this->getTaintTrace() : null,
            $this->dupe_key
        );
    }
}
