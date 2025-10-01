<?php

namespace OpenStack\Common\Transport;

interface Serializable
{
    /**
     * @return string
     */
    public function serialize(): \stdClass;
}
