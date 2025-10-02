<?php
namespace Aws\Arn;

/**
 * Amazon Resource Names (ARNs) uniquely identify AWS resources. Classes
 * implementing ArnInterface parse and store an ARN object representation.
 *
 * Valid ARN formats include:
 *
 *   arn:partition:service:region:account-id:resource-id
 *   arn:partition:service:region:account-id:resource-type/resource-id
 *   arn:partition:service:region:account-id:resource-type:resource-id
 *
 * Some components may be omitted, depending on the service and resource type.
 *
 * @internal
 */
interface ArnInterface
{
    public static function parse($string);

    public function __toString();

    public function getPrefix();

    public function getPartition();

    public function getService();

    public function getRegion();

    public function getAccountId();

    public function getResource();

    public function toArray();
}