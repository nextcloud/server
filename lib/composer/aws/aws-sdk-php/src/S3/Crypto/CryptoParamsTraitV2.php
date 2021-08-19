<?php
namespace Aws\S3\Crypto;

use Aws\Crypto\MaterialsProviderInterfaceV2;

trait CryptoParamsTraitV2
{
    use CryptoParamsTrait;

    protected function getMaterialsProvider(array $args)
    {
        if ($args['@MaterialsProvider'] instanceof MaterialsProviderInterfaceV2) {
            return $args['@MaterialsProvider'];
        }

        throw new \InvalidArgumentException('An instance of MaterialsProviderInterfaceV2'
            . ' must be passed in the "MaterialsProvider" field.');
    }
}
