<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mime;

use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\LogicException;

/**
 * Guesses the MIME type using the PECL extension FileInfo.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FileinfoMimeTypeGuesser implements MimeTypeGuesserInterface
{
    private ?string $magicFile;

    /**
     * @param string|null $magicFile A magic file to use with the finfo instance
     *
     * @see http://www.php.net/manual/en/function.finfo-open.php
     */
    public function __construct(?string $magicFile = null)
    {
        $this->magicFile = $magicFile;
    }

    public function isGuesserSupported(): bool
    {
        return \function_exists('finfo_open');
    }

    public function guessMimeType(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf('The "%s" file does not exist or is not readable.', $path));
        }

        if (!$this->isGuesserSupported()) {
            throw new LogicException(sprintf('The "%s" guesser is not supported.', __CLASS__));
        }

        if (false === $finfo = new \finfo(\FILEINFO_MIME_TYPE, $this->magicFile)) {
            return null;
        }
        $mimeType = $finfo->file($path);

        if ($mimeType && 0 === (\strlen($mimeType) % 2)) {
            $mimeStart = substr($mimeType, 0, \strlen($mimeType) >> 1);
            $mimeType = $mimeStart.$mimeStart === $mimeType ? $mimeStart : $mimeType;
        }

        return $mimeType;
    }
}
