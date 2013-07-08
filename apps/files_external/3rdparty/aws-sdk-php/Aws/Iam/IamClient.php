<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\Iam;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Enum\ClientOptions as Options;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with AWS Identity and Access Management
 *
 * @method Model addRoleToInstanceProfile(array $args = array()) {@command Iam AddRoleToInstanceProfile}
 * @method Model addUserToGroup(array $args = array()) {@command Iam AddUserToGroup}
 * @method Model changePassword(array $args = array()) {@command Iam ChangePassword}
 * @method Model createAccessKey(array $args = array()) {@command Iam CreateAccessKey}
 * @method Model createAccountAlias(array $args = array()) {@command Iam CreateAccountAlias}
 * @method Model createGroup(array $args = array()) {@command Iam CreateGroup}
 * @method Model createInstanceProfile(array $args = array()) {@command Iam CreateInstanceProfile}
 * @method Model createLoginProfile(array $args = array()) {@command Iam CreateLoginProfile}
 * @method Model createRole(array $args = array()) {@command Iam CreateRole}
 * @method Model createUser(array $args = array()) {@command Iam CreateUser}
 * @method Model createVirtualMFADevice(array $args = array()) {@command Iam CreateVirtualMFADevice}
 * @method Model deactivateMFADevice(array $args = array()) {@command Iam DeactivateMFADevice}
 * @method Model deleteAccessKey(array $args = array()) {@command Iam DeleteAccessKey}
 * @method Model deleteAccountAlias(array $args = array()) {@command Iam DeleteAccountAlias}
 * @method Model deleteAccountPasswordPolicy(array $args = array()) {@command Iam DeleteAccountPasswordPolicy}
 * @method Model deleteGroup(array $args = array()) {@command Iam DeleteGroup}
 * @method Model deleteGroupPolicy(array $args = array()) {@command Iam DeleteGroupPolicy}
 * @method Model deleteInstanceProfile(array $args = array()) {@command Iam DeleteInstanceProfile}
 * @method Model deleteLoginProfile(array $args = array()) {@command Iam DeleteLoginProfile}
 * @method Model deleteRole(array $args = array()) {@command Iam DeleteRole}
 * @method Model deleteRolePolicy(array $args = array()) {@command Iam DeleteRolePolicy}
 * @method Model deleteServerCertificate(array $args = array()) {@command Iam DeleteServerCertificate}
 * @method Model deleteSigningCertificate(array $args = array()) {@command Iam DeleteSigningCertificate}
 * @method Model deleteUser(array $args = array()) {@command Iam DeleteUser}
 * @method Model deleteUserPolicy(array $args = array()) {@command Iam DeleteUserPolicy}
 * @method Model deleteVirtualMFADevice(array $args = array()) {@command Iam DeleteVirtualMFADevice}
 * @method Model enableMFADevice(array $args = array()) {@command Iam EnableMFADevice}
 * @method Model getAccountPasswordPolicy(array $args = array()) {@command Iam GetAccountPasswordPolicy}
 * @method Model getAccountSummary(array $args = array()) {@command Iam GetAccountSummary}
 * @method Model getGroup(array $args = array()) {@command Iam GetGroup}
 * @method Model getGroupPolicy(array $args = array()) {@command Iam GetGroupPolicy}
 * @method Model getInstanceProfile(array $args = array()) {@command Iam GetInstanceProfile}
 * @method Model getLoginProfile(array $args = array()) {@command Iam GetLoginProfile}
 * @method Model getRole(array $args = array()) {@command Iam GetRole}
 * @method Model getRolePolicy(array $args = array()) {@command Iam GetRolePolicy}
 * @method Model getServerCertificate(array $args = array()) {@command Iam GetServerCertificate}
 * @method Model getUser(array $args = array()) {@command Iam GetUser}
 * @method Model getUserPolicy(array $args = array()) {@command Iam GetUserPolicy}
 * @method Model listAccessKeys(array $args = array()) {@command Iam ListAccessKeys}
 * @method Model listAccountAliases(array $args = array()) {@command Iam ListAccountAliases}
 * @method Model listGroupPolicies(array $args = array()) {@command Iam ListGroupPolicies}
 * @method Model listGroups(array $args = array()) {@command Iam ListGroups}
 * @method Model listGroupsForUser(array $args = array()) {@command Iam ListGroupsForUser}
 * @method Model listInstanceProfiles(array $args = array()) {@command Iam ListInstanceProfiles}
 * @method Model listInstanceProfilesForRole(array $args = array()) {@command Iam ListInstanceProfilesForRole}
 * @method Model listMFADevices(array $args = array()) {@command Iam ListMFADevices}
 * @method Model listRolePolicies(array $args = array()) {@command Iam ListRolePolicies}
 * @method Model listRoles(array $args = array()) {@command Iam ListRoles}
 * @method Model listServerCertificates(array $args = array()) {@command Iam ListServerCertificates}
 * @method Model listSigningCertificates(array $args = array()) {@command Iam ListSigningCertificates}
 * @method Model listUserPolicies(array $args = array()) {@command Iam ListUserPolicies}
 * @method Model listUsers(array $args = array()) {@command Iam ListUsers}
 * @method Model listVirtualMFADevices(array $args = array()) {@command Iam ListVirtualMFADevices}
 * @method Model putGroupPolicy(array $args = array()) {@command Iam PutGroupPolicy}
 * @method Model putRolePolicy(array $args = array()) {@command Iam PutRolePolicy}
 * @method Model putUserPolicy(array $args = array()) {@command Iam PutUserPolicy}
 * @method Model removeRoleFromInstanceProfile(array $args = array()) {@command Iam RemoveRoleFromInstanceProfile}
 * @method Model removeUserFromGroup(array $args = array()) {@command Iam RemoveUserFromGroup}
 * @method Model resyncMFADevice(array $args = array()) {@command Iam ResyncMFADevice}
 * @method Model updateAccessKey(array $args = array()) {@command Iam UpdateAccessKey}
 * @method Model updateAccountPasswordPolicy(array $args = array()) {@command Iam UpdateAccountPasswordPolicy}
 * @method Model updateAssumeRolePolicy(array $args = array()) {@command Iam UpdateAssumeRolePolicy}
 * @method Model updateGroup(array $args = array()) {@command Iam UpdateGroup}
 * @method Model updateLoginProfile(array $args = array()) {@command Iam UpdateLoginProfile}
 * @method Model updateServerCertificate(array $args = array()) {@command Iam UpdateServerCertificate}
 * @method Model updateSigningCertificate(array $args = array()) {@command Iam UpdateSigningCertificate}
 * @method Model updateUser(array $args = array()) {@command Iam UpdateUser}
 * @method Model uploadServerCertificate(array $args = array()) {@command Iam UploadServerCertificate}
 * @method Model uploadSigningCertificate(array $args = array()) {@command Iam UploadSigningCertificate}
 * @method ResourceIteratorInterface getGetGroupIterator(array $args = array()) The input array uses the parameters of the GetGroup operation
 * @method ResourceIteratorInterface getListAccessKeysIterator(array $args = array()) The input array uses the parameters of the ListAccessKeys operation
 * @method ResourceIteratorInterface getListAccountAliasesIterator(array $args = array()) The input array uses the parameters of the ListAccountAliases operation
 * @method ResourceIteratorInterface getListGroupPoliciesIterator(array $args = array()) The input array uses the parameters of the ListGroupPolicies operation
 * @method ResourceIteratorInterface getListGroupsIterator(array $args = array()) The input array uses the parameters of the ListGroups operation
 * @method ResourceIteratorInterface getListGroupsForUserIterator(array $args = array()) The input array uses the parameters of the ListGroupsForUser operation
 * @method ResourceIteratorInterface getListInstanceProfilesIterator(array $args = array()) The input array uses the parameters of the ListInstanceProfiles operation
 * @method ResourceIteratorInterface getListInstanceProfilesForRoleIterator(array $args = array()) The input array uses the parameters of the ListInstanceProfilesForRole operation
 * @method ResourceIteratorInterface getListMFADevicesIterator(array $args = array()) The input array uses the parameters of the ListMFADevices operation
 * @method ResourceIteratorInterface getListRolePoliciesIterator(array $args = array()) The input array uses the parameters of the ListRolePolicies operation
 * @method ResourceIteratorInterface getListRolesIterator(array $args = array()) The input array uses the parameters of the ListRoles operation
 * @method ResourceIteratorInterface getListServerCertificatesIterator(array $args = array()) The input array uses the parameters of the ListServerCertificates operation
 * @method ResourceIteratorInterface getListSigningCertificatesIterator(array $args = array()) The input array uses the parameters of the ListSigningCertificates operation
 * @method ResourceIteratorInterface getListUserPoliciesIterator(array $args = array()) The input array uses the parameters of the ListUserPolicies operation
 * @method ResourceIteratorInterface getListUsersIterator(array $args = array()) The input array uses the parameters of the ListUsers operation
 * @method ResourceIteratorInterface getListVirtualMFADevicesIterator(array $args = array()) The input array uses the parameters of the ListVirtualMFADevices operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-iam.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.Iam.IamClient.html API docs
 */
class IamClient extends AbstractClient
{
    const LATEST_API_VERSION = '2010-05-08';

    /**
     * Factory method to create a new AWS Identity and Access Management client using an array of configuration options.
     *
     * The following array keys and values are available options:
     *
     * - Credential options (`key`, `secret`, and optional `token` OR `credentials` is required)
     *     - key: AWS Access Key ID
     *     - secret: AWS secret access key
     *     - credentials: You can optionally provide a custom `Aws\Common\Credentials\CredentialsInterface` object
     *     - token: Custom AWS security token to use with request authentication
     *     - token.ttd: UNIX timestamp for when the custom credentials expire
     *     - credentials.cache.key: Optional custom cache key to use with the credentials
     * - Region and Endpoint options (a `region` and optional `scheme` OR a `base_url` is required)
     *     - region: Region name (e.g. 'us-east-1', 'us-west-1', 'us-west-2', 'eu-west-1', etc...)
     *     - scheme: URI Scheme of the base URL (e.g. 'https', 'http').
     *     - base_url: Instead of using a `region` and `scheme`, you can specify a custom base URL for the client
     * - Generic client options
     *     - ssl.cert: Set to true to use the bundled CA cert or pass the full path to an SSL certificate bundle. This
     *           option should be used when you encounter curl error code 60.
     *     - curl.CURLOPT_VERBOSE: Set to true to output curl debug information during transfers
     *     - curl.*: Prefix any available cURL option with `curl.` to add cURL options to each request.
     *           See: http://www.php.net/manual/en/function.curl-setopt.php
     *     - service.description.cache.ttl: Optional TTL used for the service description cache
     * - Signature options
     *     - signature: You can optionally provide a custom signature implementation used to sign requests
     *     - signature.service: Set to explicitly override the service name used in signatures
     *     - signature.region:  Set to explicitly override the region name used in signatures
     * - Exponential backoff options
     *     - client.backoff.logger: `Guzzle\Common\Log\LogAdapterInterface` object used to log backoff retries. Use
     *           'debug' to emit PHP warnings when a retry is issued.
     *     - client.backoff.logger.template: Optional template to use for exponential backoff log messages. See
     *           `Guzzle\Http\Plugin\ExponentialBackoffLogger` for formatting information.
     *
     * @param array|Collection $config Client configuration data
     *
     * @return self
     */
    public static function factory($config = array())
    {
        return ClientBuilder::factory(__NAMESPACE__)
            ->setConfig($config)
            ->setConfigDefaults(array(
                Options::VERSION             => self::LATEST_API_VERSION,
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/iam-%s.php'
            ))
            ->build();
    }
}
