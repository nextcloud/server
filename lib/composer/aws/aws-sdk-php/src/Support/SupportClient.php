<?php
namespace Aws\Support;

use Aws\AwsClient;

/**
 * AWS Support client.
 *
 * @method \Aws\Result addAttachmentsToSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addAttachmentsToSetAsync(array $args = [])
 * @method \Aws\Result addCommunicationToCase(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addCommunicationToCaseAsync(array $args = [])
 * @method \Aws\Result createCase(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createCaseAsync(array $args = [])
 * @method \Aws\Result describeAttachment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAttachmentAsync(array $args = [])
 * @method \Aws\Result describeCases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeCasesAsync(array $args = [])
 * @method \Aws\Result describeCommunications(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeCommunicationsAsync(array $args = [])
 * @method \Aws\Result describeServices(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeServicesAsync(array $args = [])
 * @method \Aws\Result describeSeverityLevels(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeSeverityLevelsAsync(array $args = [])
 * @method \Aws\Result describeTrustedAdvisorCheckRefreshStatuses(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTrustedAdvisorCheckRefreshStatusesAsync(array $args = [])
 * @method \Aws\Result describeTrustedAdvisorCheckResult(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTrustedAdvisorCheckResultAsync(array $args = [])
 * @method \Aws\Result describeTrustedAdvisorCheckSummaries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTrustedAdvisorCheckSummariesAsync(array $args = [])
 * @method \Aws\Result describeTrustedAdvisorChecks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTrustedAdvisorChecksAsync(array $args = [])
 * @method \Aws\Result refreshTrustedAdvisorCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise refreshTrustedAdvisorCheckAsync(array $args = [])
 * @method \Aws\Result resolveCase(array $args = [])
 * @method \GuzzleHttp\Promise\Promise resolveCaseAsync(array $args = [])
 */
class SupportClient extends AwsClient {}
