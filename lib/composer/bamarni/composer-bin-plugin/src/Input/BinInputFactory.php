<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Input;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use function array_filter;
use function array_map;
use function implode;
use function preg_match;
use function preg_quote;
use function sprintf;

final class BinInputFactory
{
    /**
     * Extracts the input to execute in the bin namespace.
     *
     * For example: `bin namespace-name update --prefer-lowest` => `update --prefer-lowest`
     *
     * Note that no input definition is bound in the resulting input.
     */
    public static function createInput(
        string $namespace,
        InputInterface $previousInput
    ): InputInterface {
        $matchResult = preg_match(
            sprintf(
                '/^(?<preBinOptions>.+)?bin (?:(?<preBinOptions2>.+?) )?(?:%1$s|\'%1$s\') (?<binCommand>.+?)(?<extraInput> -- .*)?$/',
                preg_quote($namespace, '/')
            ),
            $previousInput->__toString(),
            $matches
        );

        if (1 !== $matchResult) {
            throw InvalidBinInput::forBinInput($previousInput);
        }

        $inputParts = array_filter(
            array_map(
                'trim',
                [
                    $matches['binCommand'],
                    $matches['preBinOptions2'] ?? '',
                    $matches['preBinOptions'] ?? '',
                    $matches['extraInput'] ?? '',
                ]
            )
        );

        // Move the options present _before_ bin namespaceName to after, but
        // before the end of option marker (--) if present.
        $reorderedInput = implode(' ', $inputParts);

        return new StringInput($reorderedInput);
    }

    public static function createNamespaceInput(InputInterface $previousInput): InputInterface
    {
        $matchResult = preg_match(
            '/^(.+?\s?)(--(?: .+)?)?$/',
            $previousInput->__toString(),
            $matches
        );

        if (1 !== $matchResult) {
            throw InvalidBinInput::forNamespaceInput($previousInput);
        }

        $inputParts = array_filter(
            array_map(
                'trim',
                [
                    $matches[1],
                    '--working-dir=.',
                    $matches[2] ?? '',
                ]
            )
        );

        $newInput = implode(' ', $inputParts);

        return new StringInput($newInput);
    }

    public static function createForwardedCommandInput(InputInterface $input): InputInterface
    {
        return new StringInput(
            sprintf(
                'bin all %s',
                $input->__toString()
            )
        );
    }

    private function __construct()
    {
    }
}
