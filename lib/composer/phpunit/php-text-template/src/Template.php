<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-text-template.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Template;

use function array_keys;
use function array_merge;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function sprintf;
use function str_replace;

final class Template
{
    private string $template = '';
    private string $openDelimiter;
    private string $closeDelimiter;

    /**
     * @psalm-var array<string,string>
     */
    private array $values = [];

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $file = '', string $openDelimiter = '{', string $closeDelimiter = '}')
    {
        $this->setFile($file);

        $this->openDelimiter  = $openDelimiter;
        $this->closeDelimiter = $closeDelimiter;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setFile(string $file): void
    {
        if (is_file($file)) {
            $this->template = file_get_contents($file);

            return;
        }

        $distFile = $file . '.dist';

        if (is_file($distFile)) {
            $this->template = file_get_contents($distFile);

            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Failed to load template "%s"',
                $file
            )
        );
    }

    /**
     * @psalm-param array<string,string> $values
     */
    public function setVar(array $values, bool $merge = true): void
    {
        if (!$merge || empty($this->values)) {
            $this->values = $values;

            return;
        }

        $this->values = array_merge($this->values, $values);
    }

    public function render(): string
    {
        $keys = [];

        foreach (array_keys($this->values) as $key) {
            $keys[] = $this->openDelimiter . $key . $this->closeDelimiter;
        }

        return str_replace($keys, $this->values, $this->template);
    }

    /**
     * @codeCoverageIgnore
     */
    public function renderTo(string $target): void
    {
        if (!@file_put_contents($target, $this->render())) {
            throw new RuntimeException(
                sprintf(
                    'Writing rendered result to "%s" failed',
                    $target
                )
            );
        }
    }
}
