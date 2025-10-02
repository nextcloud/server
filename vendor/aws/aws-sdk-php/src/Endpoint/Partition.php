<?php
namespace Aws\Endpoint;

use ArrayAccess;
use Aws\HasDataTrait;
use Aws\Sts\RegionalEndpoints\ConfigurationProvider;
use Aws\S3\RegionalEndpoint\ConfigurationProvider as S3ConfigurationProvider;
use InvalidArgumentException as Iae;

/**
 * Default implementation of an AWS partition.
 */
final class Partition implements ArrayAccess, PartitionInterface
{
    use HasDataTrait;

    private $stsLegacyGlobalRegions = [
        'ap-northeast-1',
        'ap-south-1',
        'ap-southeast-1',
        'ap-southeast-2',
        'aws-global',
        'ca-central-1',
        'eu-central-1',
        'eu-north-1',
        'eu-west-1',
        'eu-west-2',
        'eu-west-3',
        'sa-east-1',
        'us-east-1',
        'us-east-2',
        'us-west-1',
        'us-west-2',
    ];

    /**
     * The partition constructor accepts the following options:
     *
     * - `partition`: (string, required) The partition name as specified in an
     *   ARN (e.g., `aws`)
     * - `partitionName`: (string) The human readable name of the partition
     *   (e.g., "AWS Standard")
     * - `dnsSuffix`: (string, required) The DNS suffix of the partition. This
     *   value is used to determine how endpoints in the partition are resolved.
     * - `regionRegex`: (string) A PCRE regular expression that specifies the
     *   pattern that region names in the endpoint adhere to.
     * - `regions`: (array, required) A map of the regions in the partition.
     *   Each key is the region as present in a hostname (e.g., `us-east-1`),
     *   and each value is a structure containing region information.
     * - `defaults`: (array) A map of default key value pairs to apply to each
     *   endpoint of the partition. Any value in an `endpoint` definition will
     *   supersede any values specified in `defaults`.
     * - `services`: (array, required) A map of service endpoint prefix name
     *   (the value found in a hostname) to information about the service.
     *
     * @param array $definition
     *
     * @throws Iae if any required options are missing
     */
    public function __construct(array $definition)
    {
        foreach (['partition', 'regions', 'services', 'dnsSuffix'] as $key) {
            if (!isset($definition[$key])) {
                throw new Iae("Partition missing required $key field");
            }
        }

        $this->data = $definition;
    }

    public function getName()
    {
        return $this->data['partition'];
    }

    /**
     * @internal
     * @return mixed
     */
    public function getDnsSuffix()
    {
        return $this->data['dnsSuffix'];
    }

    public function isRegionMatch($region, $service)
    {
        if (isset($this->data['regions'][$region])
            || isset($this->data['services'][$service]['endpoints'][$region])
        ) {
            return true;
        }

        if (isset($this->data['regionRegex'])) {
            return (bool) preg_match(
                "@{$this->data['regionRegex']}@",
                $region
            );
        }

        return false;
    }

    public function getAvailableEndpoints(
        $service,
        $allowNonRegionalEndpoints = false
    ) {
        if ($this->isServicePartitionGlobal($service)) {
            return [$this->getPartitionEndpoint($service)];
        }

        if (isset($this->data['services'][$service]['endpoints'])) {
            $serviceRegions = array_keys(
                $this->data['services'][$service]['endpoints']
            );

            return $allowNonRegionalEndpoints
                ? $serviceRegions
                : array_intersect($serviceRegions, array_keys(
                    $this->data['regions']
                ));
        }

        return [];
    }

    public function __invoke(array $args = [])
    {
        $service = isset($args['service']) ? $args['service'] : '';
        $region = isset($args['region']) ? $args['region'] : '';
        $scheme = isset($args['scheme']) ? $args['scheme'] : 'https';
        $options = isset($args['options']) ? $args['options'] : [];
        $data = $this->getEndpointData($service, $region, $options);
        $variant = $this->getVariant($options, $data);
        if (isset($variant['hostname'])) {
            $template = $variant['hostname'];
        } else {
            $template = isset($data['hostname']) ? $data['hostname'] : '';
        }
        $dnsSuffix = isset($variant['dnsSuffix'])
            ? $variant['dnsSuffix']
            : $this->data['dnsSuffix'];
        return [
            'endpoint' => "{$scheme}://" . $this->formatEndpoint(
                    $template,
                    $service,
                    $region,
                    $dnsSuffix
                ),
            'signatureVersion' => $this->getSignatureVersion($data),
            'signingRegion' => isset($data['credentialScope']['region'])
                ? $data['credentialScope']['region']
                : $region,
            'signingName' => isset($data['credentialScope']['service'])
                ? $data['credentialScope']['service']
                : $service,
        ];
    }

    private function getEndpointData($service, $region, $options)
    {
        $defaultRegion = $this->resolveRegion($service, $region, $options);
        $data = isset($this->data['services'][$service]['endpoints'][$defaultRegion])
            ? $this->data['services'][$service]['endpoints'][$defaultRegion]
            : [];
        $data += isset($this->data['services'][$service]['defaults'])
            ? $this->data['services'][$service]['defaults']
            : [];
        $data += isset($this->data['defaults'])
            ? $this->data['defaults']
            : [];

        return $data;
    }

    private function getSignatureVersion(array $data)
    {
        static $supportedBySdk = [
            's3v4',
            'v4',
            'anonymous',
        ];

        $possibilities = array_intersect(
            $supportedBySdk,
            isset($data['signatureVersions'])
                ? $data['signatureVersions']
                : ['v4']
        );

        return array_shift($possibilities);
    }

    private function resolveRegion($service, $region, $options)
    {
        if (isset($this->data['services'][$service]['endpoints'][$region])
            && $this->isFipsEndpointUsed($region)
        ) {
            return $region;
        }

        if ($this->isServicePartitionGlobal($service)
            || $this->isStsLegacyEndpointUsed($service, $region, $options)
            || $this->isS3LegacyEndpointUsed($service, $region, $options)
        ) {
            return $this->getPartitionEndpoint($service);
        }

        return $region;
    }

    private function isServicePartitionGlobal($service)
    {
        return isset($this->data['services'][$service]['isRegionalized'])
            && false === $this->data['services'][$service]['isRegionalized']
            && isset($this->data['services'][$service]['partitionEndpoint']);
    }

    /**
     * STS legacy endpoints used for valid regions unless option is explicitly
     * set to 'regional'
     *
     * @param string $service
     * @param string $region
     * @param array $options
     * @return bool
     */
    private function isStsLegacyEndpointUsed($service, $region, $options)
    {
        return $service === 'sts'
            && in_array($region, $this->stsLegacyGlobalRegions)
            && (empty($options['sts_regional_endpoints'])
                || ConfigurationProvider::unwrap(
                    $options['sts_regional_endpoints']
                )->getEndpointsType() !== 'regional'
            );
    }

    /**
     * S3 legacy us-east-1 endpoint used for valid regions unless option is explicitly
     * set to 'regional'
     *
     * @param string $service
     * @param string $region
     * @param array $options
     * @return bool
     */
    private function isS3LegacyEndpointUsed($service, $region, $options)
    {
        return $service === 's3'
            && $region === 'us-east-1'
            && (empty($options['s3_us_east_1_regional_endpoint'])
                || S3ConfigurationProvider::unwrap(
                    $options['s3_us_east_1_regional_endpoint']
                )->getEndpointsType() !== 'regional'
            );
    }

    private function getPartitionEndpoint($service)
    {
        return $this->data['services'][$service]['partitionEndpoint'];
    }

    private function formatEndpoint($template, $service, $region, $dnsSuffix)
    {
        return strtr($template, [
            '{service}' => $service,
            '{region}' => $region,
            '{dnsSuffix}' => $dnsSuffix,
        ]);
    }

    /**
     * @param $region
     * @return bool
     */
    private function isFipsEndpointUsed($region)
    {
        return strpos($region, "fips") !== false;
    }

    /**
     * @param array $options
     * @param array $data
     * @return array
     */
    private function getVariant(array $options, array $data)
    {
        $variantTags = [];
        if (isset($options['use_fips_endpoint'])) {
            $useFips = $options['use_fips_endpoint'];
            if (is_bool($useFips)) {
                $useFips && $variantTags[] = 'fips';
            } elseif ($useFips->isUseFipsEndpoint()) {
                $variantTags[] = 'fips';
            }
        }
        if (isset($options['use_dual_stack_endpoint'])) {
            $useDualStack = $options['use_dual_stack_endpoint'];
            if (is_bool($useDualStack)) {
                $useDualStack && $variantTags[] = 'dualstack';
            } elseif ($useDualStack->isUseDualStackEndpoint()) {
                $variantTags[] = 'dualstack';
            }
        }
        if (!empty($variantTags)) {
            if (isset($data['variants'])) {
                foreach ($data['variants'] as $variant) {
                    if (array_count_values($variant['tags']) == array_count_values($variantTags)) {
                        return $variant;
                    }
                }
            }
            if (isset($this->data['defaults']['variants'])) {
                foreach ($this->data['defaults']['variants'] as $variant) {
                    if (array_count_values($variant['tags']) == array_count_values($variantTags)) {
                        return $variant;
                    }
                }
            }
        }
    }
}
