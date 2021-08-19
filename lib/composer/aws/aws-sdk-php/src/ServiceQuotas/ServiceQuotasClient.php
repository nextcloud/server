<?php
namespace Aws\ServiceQuotas;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Service Quotas** service.
 * @method \Aws\Result associateServiceQuotaTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateServiceQuotaTemplateAsync(array $args = [])
 * @method \Aws\Result deleteServiceQuotaIncreaseRequestFromTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteServiceQuotaIncreaseRequestFromTemplateAsync(array $args = [])
 * @method \Aws\Result disassociateServiceQuotaTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateServiceQuotaTemplateAsync(array $args = [])
 * @method \Aws\Result getAWSDefaultServiceQuota(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAWSDefaultServiceQuotaAsync(array $args = [])
 * @method \Aws\Result getAssociationForServiceQuotaTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAssociationForServiceQuotaTemplateAsync(array $args = [])
 * @method \Aws\Result getRequestedServiceQuotaChange(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getRequestedServiceQuotaChangeAsync(array $args = [])
 * @method \Aws\Result getServiceQuota(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getServiceQuotaAsync(array $args = [])
 * @method \Aws\Result getServiceQuotaIncreaseRequestFromTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getServiceQuotaIncreaseRequestFromTemplateAsync(array $args = [])
 * @method \Aws\Result listAWSDefaultServiceQuotas(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAWSDefaultServiceQuotasAsync(array $args = [])
 * @method \Aws\Result listRequestedServiceQuotaChangeHistory(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRequestedServiceQuotaChangeHistoryAsync(array $args = [])
 * @method \Aws\Result listRequestedServiceQuotaChangeHistoryByQuota(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRequestedServiceQuotaChangeHistoryByQuotaAsync(array $args = [])
 * @method \Aws\Result listServiceQuotaIncreaseRequestsInTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listServiceQuotaIncreaseRequestsInTemplateAsync(array $args = [])
 * @method \Aws\Result listServiceQuotas(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listServiceQuotasAsync(array $args = [])
 * @method \Aws\Result listServices(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listServicesAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result putServiceQuotaIncreaseRequestIntoTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putServiceQuotaIncreaseRequestIntoTemplateAsync(array $args = [])
 * @method \Aws\Result requestServiceQuotaIncrease(array $args = [])
 * @method \GuzzleHttp\Promise\Promise requestServiceQuotaIncreaseAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 */
class ServiceQuotasClient extends AwsClient {}
