<?php

declare(strict_types=1);

namespace Sabre\Event;

/**
 * This class is an EventEmitter with support for wildcard event handlers.
 *
 * What this means is that you can emit events like this:
 *
 *   emit('change:firstName')
 *
 * and listen to this event like this:
 *
 *   on('change:*')
 *
 * A few notes:
 *
 * - Wildcards only work at the end of an event name.
 * - Currently you can only use 1 wildcard.
 * - Using ":" as a separator is optional, but it's highly recommended to use
 *   some kind of separator.
 *
 * The WildcardEmitter is a bit slower than the regular Emitter. If your code
 * must be very high performance, it might be better to try to use the other
 * emitter. For most usage the difference is negligible though.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class WildcardEmitter implements EmitterInterface
{
    use WildcardEmitterTrait;
}
