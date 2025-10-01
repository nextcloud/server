<?php
namespace Aws\S3;

use Aws\Api\Service;
use Aws\Arn\ArnInterface;
use Aws\Arn\S3\OutpostsArnInterface;
use Aws\Endpoint\PartitionEndpointProvider;
use Aws\Exception\InvalidRegionException;

/**
 * @internal
 */
trait EndpointRegionHelperTrait
{
    /** @var array */
    private $config;

    /** @var PartitionEndpointProvider */
    private $partitionProvider;

    /** @var string */
    private $region;

    /** @var Service */
    private $service;

    private function getPartitionSuffix(
        ArnInterface $arn,
        PartitionEndpointProvider $provider
    ) {
        $partition = $provider->getPartition(
            $arn->getRegion(),
            $arn->getService()
        );
        return $partition->getDnsSuffix();
    }

    private function getSigningRegion(
        $region,
        $service,
        PartitionEndpointProvider $provider
    ) {
        $partition = $provider->getPartition($region, $service);
        $data = $partition->toArray();
        if (isset($data['services'][$service]['endpoints'][$region]['credentialScope']['region'])) {
            return $data['services'][$service]['endpoints'][$region]['credentialScope']['region'];
        }
        return $region;
    }

    private function isMatchingSigningRegion(
        $arnRegion,
        $clientRegion,
        $service,
        PartitionEndpointProvider $provider
    ) {
        $arnRegion = \Aws\strip_fips_pseudo_regions(strtolower($arnRegion));
        $clientRegion = strtolower($clientRegion);
        if ($arnRegion === $clientRegion) {
            return true;
        }
        if ($this->getSigningRegion($clientRegion, $service, $provider) === $arnRegion) {
            return true;
        }
        return false;
    }

    private function validateFipsConfigurations(ArnInterface $arn)
    {
        $useFipsEndpoint = !empty($this->config['use_fips_endpoint']);
        if ($arn instanceof OutpostsArnInterface) {
            if (empty($this->config['use_arn_region'])
                || !($this->config['use_arn_region']->isUseArnRegion())
            ) {
                $region = $this->region;
            } else {
                $region = $arn->getRegion();
            }
            if (\Aws\is_fips_pseudo_region($region)) {
                throw new InvalidRegionException(
                    'Fips is currently not supported with S3 Outposts access'
                    . ' points. Please provide a non-fips region or do not supply an'
                    . ' access point ARN.');
            }
        }
    }

    private function validateMatchingRegion(ArnInterface $arn)
    {
        if (!($this->isMatchingSigningRegion(
            $arn->getRegion(),
            $this->region,
            $this->service->getEndpointPrefix(),
            $this->partitionProvider)
        )) {
            if (empty($this->config['use_arn_region'])
                || !($this->config['use_arn_region']->isUseArnRegion())
            ) {
                throw new InvalidRegionException('The region'
                    . " specified in the ARN (" . $arn->getRegion()
                    . ") does not match the client region ("
                    . "{$this->region}).");
            }
        }
    }
}
