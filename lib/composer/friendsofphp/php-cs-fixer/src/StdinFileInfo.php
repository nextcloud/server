<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer;

/**
 * @author Davi Koscianski Vidal <davividal@gmail.com>
 *
 * @internal
 */
final class StdinFileInfo extends \SplFileInfo
{
    public function __construct()
    {
    }

    public function __toString()
    {
        return $this->getRealPath();
    }

    public function getRealPath()
    {
        // So file_get_contents & friends will work.
        // Warning - this stream is not seekable, so `file_get_contents` will work only once! Consider using `FileReader`.
        return 'php://stdin';
    }

    public function getATime()
    {
        return 0;
    }

    public function getBasename($suffix = null)
    {
        return $this->getFilename();
    }

    public function getCTime()
    {
        return 0;
    }

    public function getExtension()
    {
        return '.php';
    }

    public function getFileInfo($className = null)
    {
        throw new \BadMethodCallException(sprintf('Method "%s" is not implemented.', __METHOD__));
    }

    public function getFilename()
    {
        /*
         * Useful so fixers depending on PHP-only files still work.
         *
         * The idea to use STDIN is to parse PHP-only files, so we can
         * assume that there will be always a PHP file out there.
         */

        return 'stdin.php';
    }

    public function getGroup()
    {
        return 0;
    }

    public function getInode()
    {
        return 0;
    }

    public function getLinkTarget()
    {
        return '';
    }

    public function getMTime()
    {
        return 0;
    }

    public function getOwner()
    {
        return 0;
    }

    public function getPath()
    {
        return '';
    }

    public function getPathInfo($className = null)
    {
        throw new \BadMethodCallException(sprintf('Method "%s" is not implemented.', __METHOD__));
    }

    public function getPathname()
    {
        return $this->getFilename();
    }

    public function getPerms()
    {
        return 0;
    }

    public function getSize()
    {
        return 0;
    }

    public function getType()
    {
        return 'file';
    }

    public function isDir()
    {
        return false;
    }

    public function isExecutable()
    {
        return false;
    }

    public function isFile()
    {
        return true;
    }

    public function isLink()
    {
        return false;
    }

    public function isReadable()
    {
        return true;
    }

    public function isWritable()
    {
        return false;
    }

    public function openFile($openMode = 'r', $useIncludePath = false, $context = null)
    {
        throw new \BadMethodCallException(sprintf('Method "%s" is not implemented.', __METHOD__));
    }

    public function setFileClass($className = null)
    {
    }

    public function setInfoClass($className = null)
    {
    }
}
