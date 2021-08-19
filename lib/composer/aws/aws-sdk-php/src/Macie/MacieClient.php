<?php
namespace Aws\Macie;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Macie** service.
 * @method \Aws\Result associateMemberAccount(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateMemberAccountAsync(array $args = [])
 * @method \Aws\Result associateS3Resources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateS3ResourcesAsync(array $args = [])
 * @method \Aws\Result disassociateMemberAccount(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateMemberAccountAsync(array $args = [])
 * @method \Aws\Result disassociateS3Resources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateS3ResourcesAsync(array $args = [])
 * @method \Aws\Result listMemberAccounts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listMemberAccountsAsync(array $args = [])
 * @method \Aws\Result listS3Resources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listS3ResourcesAsync(array $args = [])
 * @method \Aws\Result updateS3Resources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateS3ResourcesAsync(array $args = [])
 */
class MacieClient extends AwsClient {}
