<?php

namespace OpenStack\Test\Common\Service\Fixtures\Models;

use OpenStack\Common\Resource\OperatorResource;

class Foo extends OperatorResource
{
    public function testGetService()
    {
        return $this->getService();
    }
}
