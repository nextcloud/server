<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2;

/**
 * Represents common constants.
 */
abstract class Enum
{
    const REBOOT_SOFT         = 'SOFT';
    const REBOOT_HARD         = 'HARD';
    const CONSOLE_NOVNC       = 'novnc';
    const CONSOLE_XVPNC       = 'xvpvnc';
    const CONSOLE_RDP_HTML5   = 'rdp-html5';
    const CONSOLE_SPICE_HTML5 = 'spice-html5';
    const CONSOLE_SERIAL      = 'serial';
}
