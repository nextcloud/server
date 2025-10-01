<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2;

/**
 * Represents common constants.
 */
abstract class Enum
{
    public const REBOOT_SOFT         = 'SOFT';
    public const REBOOT_HARD         = 'HARD';
    public const CONSOLE_NOVNC       = 'novnc';
    public const CONSOLE_XVPNC       = 'xvpvnc';
    public const CONSOLE_RDP_HTML5   = 'rdp-html5';
    public const CONSOLE_SPICE_HTML5 = 'spice-html5';
    public const CONSOLE_SERIAL      = 'serial';
}
