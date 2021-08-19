<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid\Provider\Dce;

use Ramsey\Uuid\Exception\DceSecurityException;
use Ramsey\Uuid\Provider\DceSecurityProviderInterface;
use Ramsey\Uuid\Type\Integer as IntegerObject;

use function escapeshellarg;
use function preg_split;
use function str_getcsv;
use function strpos;
use function strrpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;

use const PREG_SPLIT_NO_EMPTY;

/**
 * SystemDceSecurityProvider retrieves the user or group identifiers from the system
 */
class SystemDceSecurityProvider implements DceSecurityProviderInterface
{
    /**
     * @throws DceSecurityException if unable to get a user identifier
     *
     * @inheritDoc
     */
    public function getUid(): IntegerObject
    {
        static $uid = null;

        if ($uid instanceof IntegerObject) {
            return $uid;
        }

        if ($uid === null) {
            $uid = $this->getSystemUid();
        }

        if ($uid === '') {
            throw new DceSecurityException(
                'Unable to get a user identifier using the system DCE '
                . 'Security provider; please provide a custom identifier or '
                . 'use a different provider'
            );
        }

        $uid = new IntegerObject($uid);

        return $uid;
    }

    /**
     * @throws DceSecurityException if unable to get a group identifier
     *
     * @inheritDoc
     */
    public function getGid(): IntegerObject
    {
        static $gid = null;

        if ($gid instanceof IntegerObject) {
            return $gid;
        }

        if ($gid === null) {
            $gid = $this->getSystemGid();
        }

        if ($gid === '') {
            throw new DceSecurityException(
                'Unable to get a group identifier using the system DCE '
                . 'Security provider; please provide a custom identifier or '
                . 'use a different provider'
            );
        }

        $gid = new IntegerObject($gid);

        return $gid;
    }

    /**
     * Returns the UID from the system
     */
    private function getSystemUid(): string
    {
        if (!$this->hasShellExec()) {
            return '';
        }

        switch ($this->getOs()) {
            case 'WIN':
                return $this->getWindowsUid();
            case 'DAR':
            case 'FRE':
            case 'LIN':
            default:
                return trim((string) shell_exec('id -u'));
        }
    }

    /**
     * Returns the GID from the system
     */
    private function getSystemGid(): string
    {
        if (!$this->hasShellExec()) {
            return '';
        }

        switch ($this->getOs()) {
            case 'WIN':
                return $this->getWindowsGid();
            case 'DAR':
            case 'FRE':
            case 'LIN':
            default:
                return trim((string) shell_exec('id -g'));
        }
    }

    /**
     * Returns true if shell_exec() is available for use
     */
    private function hasShellExec(): bool
    {
        $disabledFunctions = strtolower((string) ini_get('disable_functions'));

        return strpos($disabledFunctions, 'shell_exec') === false;
    }

    /**
     * Returns the PHP_OS string
     */
    private function getOs(): string
    {
        return strtoupper(substr(constant('PHP_OS'), 0, 3));
    }

    /**
     * Returns the user identifier for a user on a Windows system
     *
     * Windows does not have the same concept as an effective POSIX UID for the
     * running script. Instead, each user is uniquely identified by an SID
     * (security identifier). The SID includes three 32-bit unsigned integers
     * that make up a unique domain identifier, followed by an RID (relative
     * identifier) that we will use as the UID. The primary caveat is that this
     * UID may not be unique to the system, since it is, instead, unique to the
     * domain.
     *
     * @link https://www.lifewire.com/what-is-an-sid-number-2626005 What Is an SID Number?
     * @link https://bit.ly/30vE7NM Well-known SID Structures
     * @link https://bit.ly/2FWcYKJ Well-known security identifiers in Windows operating systems
     * @link https://www.windows-commandline.com/get-sid-of-user/ Get SID of user
     */
    private function getWindowsUid(): string
    {
        $response = shell_exec('whoami /user /fo csv /nh');

        if ($response === null) {
            return '';
        }

        $sid = str_getcsv(trim((string) $response))[1] ?? '';

        if (($lastHyphen = strrpos($sid, '-')) === false) {
            return '';
        }

        return trim(substr($sid, $lastHyphen + 1));
    }

    /**
     * Returns a group identifier for a user on a Windows system
     *
     * Since Windows does not have the same concept as an effective POSIX GID
     * for the running script, we will get the local group memberships for the
     * user running the script. Then, we will get the SID (security identifier)
     * for the first group that appears in that list. Finally, we will return
     * the RID (relative identifier) for the group and use that as the GID.
     *
     * @link https://www.windows-commandline.com/list-of-user-groups-command-line/ List of user groups command line
     */
    private function getWindowsGid(): string
    {
        $response = shell_exec('net user %username% | findstr /b /i "Local Group Memberships"');

        if ($response === null) {
            return '';
        }

        /** @var string[] $userGroups */
        $userGroups = preg_split('/\s{2,}/', (string) $response, -1, PREG_SPLIT_NO_EMPTY);

        $firstGroup = trim($userGroups[1] ?? '', "* \t\n\r\0\x0B");

        if ($firstGroup === '') {
            return '';
        }

        $response = shell_exec('wmic group get name,sid | findstr /b /i ' . escapeshellarg($firstGroup));

        if ($response === null) {
            return '';
        }

        /** @var string[] $userGroup */
        $userGroup = preg_split('/\s{2,}/', (string) $response, -1, PREG_SPLIT_NO_EMPTY);

        $sid = $userGroup[1] ?? '';

        if (($lastHyphen = strrpos($sid, '-')) === false) {
            return '';
        }

        return trim((string) substr($sid, $lastHyphen + 1));
    }
}
