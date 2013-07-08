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

return array (
    'apiVersion' => '2010-05-08',
    'endpointPrefix' => 'iam',
    'serviceFullName' => 'AWS Identity and Access Management',
    'serviceAbbreviation' => 'IAM',
    'serviceType' => 'query',
    'globalEndpoint' => 'iam.amazonaws.com',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'Iam',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'iam.us-gov.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AddRoleToInstanceProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adds the specified role to the specified instance profile. For more information about roles, go to Working with Roles. For more information about instance profiles, go to About Instance Profiles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AddRoleToInstanceProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'InstanceProfileName' => array(
                    'required' => true,
                    'description' => 'Name of the instance profile to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role to add.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'AddUserToGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adds the specified user to the specified group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AddUserToGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user to add.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'ChangePassword' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Changes the password of the IAM user calling ChangePassword. The root account password is not affected by this action. For information about modifying passwords, see Managing Passwords.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ChangePassword',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'OldPassword' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'NewPassword' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because the type of user for the transaction was incorrect.',
                    'class' => 'InvalidUserTypeException',
                ),
            ),
        ),
        'CreateAccessKey' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateAccessKeyResponse',
            'responseType' => 'model',
            'summary' => 'Creates a new AWS Secret Access Key and corresponding AWS Access Key ID for the specified user. The default status for new keys is Active.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateAccessKey',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'The user name that the new key will belong to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'CreateAccountAlias' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This action creates an alias for your AWS account. For information about using an AWS account alias, see Using an Alias for Your AWS Account ID in Using AWS Identity and Access Management.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateAccountAlias',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'AccountAlias' => array(
                    'required' => true,
                    'description' => 'Name of the account alias to create.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 63,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
            ),
        ),
        'CreateGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateGroupResponse',
            'responseType' => 'model',
            'summary' => 'Creates a new group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'Path' => array(
                    'description' => 'The path to the group. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group to create. Do not include the path in this value.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'CreateInstanceProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateInstanceProfileResponse',
            'responseType' => 'model',
            'summary' => 'Creates a new instance profile. For information about instance profiles, go to About Instance Profiles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateInstanceProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'InstanceProfileName' => array(
                    'required' => true,
                    'description' => 'Name of the instance profile to create.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'Path' => array(
                    'description' => 'The path to the instance profile. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'CreateLoginProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateLoginProfileResponse',
            'responseType' => 'model',
            'summary' => 'Creates a password for the specified user, giving the user the ability to access AWS services through the AWS Management Console. For more information about managing passwords, see Managing Passwords in Using IAM.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateLoginProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user to create a password for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'Password' => array(
                    'required' => true,
                    'description' => 'The new password for the user name.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because the provided password did not meet the requirements imposed by the account password policy.',
                    'class' => 'PasswordPolicyViolationException',
                ),
            ),
        ),
        'CreateRole' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateRoleResponse',
            'responseType' => 'model',
            'summary' => 'Creates a new role for your AWS account. For more information about roles, go to Working with Roles. For information about limitations on role names and the number of roles you can create, go to Limitations on IAM Entities in Using AWS Identity and Access Management.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateRole',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'Path' => array(
                    'description' => 'The path to the role. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role to create.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'AssumeRolePolicyDocument' => array(
                    'required' => true,
                    'description' => 'The policy that grants an entity permission to assume the role.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 131072,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because the policy document was malformed. The error message describes the specific error.',
                    'class' => 'MalformedPolicyDocumentException',
                ),
            ),
        ),
        'CreateUser' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateUserResponse',
            'responseType' => 'model',
            'summary' => 'Creates a new user for your AWS account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateUser',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'Path' => array(
                    'description' => 'The path for the user name. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user to create.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'CreateVirtualMFADevice' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateVirtualMFADeviceResponse',
            'responseType' => 'model',
            'summary' => 'Creates a new virtual MFA device for the AWS account. After creating the virtual MFA, use EnableMFADevice to attach the MFA device to an IAM user. For more information about creating and working with virtual MFA devices, go to Using a Virtual MFA Device in Using AWS Identity and Access Management.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateVirtualMFADevice',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'Path' => array(
                    'description' => 'The path for the virtual MFA device. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'VirtualMFADeviceName' => array(
                    'required' => true,
                    'description' => 'The name of the virtual MFA device. Use with path to uniquely identify a virtual MFA device.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
            ),
        ),
        'DeactivateMFADevice' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deactivates the specified MFA device and removes it from association with the user name for which it was originally enabled.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeactivateMFADevice',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user whose MFA device you want to deactivate.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'SerialNumber' => array(
                    'required' => true,
                    'description' => 'The serial number that uniquely identifies the MFA device. For virtual MFA devices, the serial number is the device ARN.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 9,
                    'maxLength' => 256,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that is temporarily unmodifiable, such as a user name that was deleted and then recreated. The error indicates that the request is likely to succeed if you try again after waiting several minutes. The error message describes the entity.',
                    'class' => 'EntityTemporarilyUnmodifiableException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteAccessKey' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the access key associated with the specified user.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteAccessKey',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'Name of the user whose key you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'AccessKeyId' => array(
                    'required' => true,
                    'description' => 'The Access Key ID for the Access Key ID and Secret Access Key you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 16,
                    'maxLength' => 32,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteAccountAlias' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified AWS account alias. For information about using an AWS account alias, see Using an Alias for Your AWS Account ID in Using AWS Identity and Access Management.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteAccountAlias',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'AccountAlias' => array(
                    'required' => true,
                    'description' => 'Name of the account alias to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 3,
                    'maxLength' => 63,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteAccountPasswordPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the password policy for the AWS account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteAccountPasswordPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified group. The group must not contain any users or have any attached policies.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to delete a resource that has attached subordinate entities. The error message describes these entities.',
                    'class' => 'DeleteConflictException',
                ),
            ),
        ),
        'DeleteGroupPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified policy that is associated with the specified group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteGroupPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group the policy is associated with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteInstanceProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified instance profile. The instance profile must not have an associated role.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteInstanceProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'InstanceProfileName' => array(
                    'required' => true,
                    'description' => 'Name of the instance profile to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to delete a resource that has attached subordinate entities. The error message describes these entities.',
                    'class' => 'DeleteConflictException',
                ),
            ),
        ),
        'DeleteLoginProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the password for the specified user, which terminates the user\'s ability to access AWS services through the AWS Management Console.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteLoginProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user whose password you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that is temporarily unmodifiable, such as a user name that was deleted and then recreated. The error indicates that the request is likely to succeed if you try again after waiting several minutes. The error message describes the entity.',
                    'class' => 'EntityTemporarilyUnmodifiableException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteRole' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified role. The role must not have any policies attached. For more information about roles, go to Working with Roles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteRole',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to delete a resource that has attached subordinate entities. The error message describes these entities.',
                    'class' => 'DeleteConflictException',
                ),
            ),
        ),
        'DeleteRolePolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified policy associated with the specified role.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteRolePolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role the associated with the policy.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteServerCertificate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified server certificate.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteServerCertificate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'ServerCertificateName' => array(
                    'required' => true,
                    'description' => 'The name of the server certificate you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to delete a resource that has attached subordinate entities. The error message describes these entities.',
                    'class' => 'DeleteConflictException',
                ),
            ),
        ),
        'DeleteSigningCertificate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified signing certificate associated with the specified user.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteSigningCertificate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'Name of the user the signing certificate belongs to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'CertificateId' => array(
                    'required' => true,
                    'description' => 'ID of the signing certificate to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 24,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteUser' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified user. The user must not belong to any groups, have any keys or signing certificates, or have any attached policies.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteUser',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to delete a resource that has attached subordinate entities. The error message describes these entities.',
                    'class' => 'DeleteConflictException',
                ),
            ),
        ),
        'DeleteUserPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified policy associated with the specified user.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteUserPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user the policy is associated with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'DeleteVirtualMFADevice' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a virtual MFA device.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteVirtualMFADevice',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'SerialNumber' => array(
                    'required' => true,
                    'description' => 'The serial number that uniquely identifies the MFA device. For virtual MFA devices, the serial number is the same as the ARN.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 9,
                    'maxLength' => 256,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to delete a resource that has attached subordinate entities. The error message describes these entities.',
                    'class' => 'DeleteConflictException',
                ),
            ),
        ),
        'EnableMFADevice' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Enables the specified MFA device and associates it with the specified user name. When enabled, the MFA device is required for every subsequent login by the user name associated with the device.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'EnableMFADevice',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user for whom you want to enable the MFA device.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'SerialNumber' => array(
                    'required' => true,
                    'description' => 'The serial number that uniquely identifies the MFA device. For virtual MFA devices, the serial number is the device ARN.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 9,
                    'maxLength' => 256,
                ),
                'AuthenticationCode1' => array(
                    'required' => true,
                    'description' => 'An authentication code emitted by the device.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 6,
                    'maxLength' => 6,
                ),
                'AuthenticationCode2' => array(
                    'required' => true,
                    'description' => 'A subsequent authentication code emitted by the device.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 6,
                    'maxLength' => 6,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that is temporarily unmodifiable, such as a user name that was deleted and then recreated. The error indicates that the request is likely to succeed if you try again after waiting several minutes. The error message describes the entity.',
                    'class' => 'EntityTemporarilyUnmodifiableException',
                ),
                array(
                    'reason' => 'The request was rejected because the authentication code was not recognized. The error message describes the specific error.',
                    'class' => 'InvalidAuthenticationCodeException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetAccountPasswordPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetAccountPasswordPolicyResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves the password policy for the AWS account. For more information about using a password policy, go to Managing an IAM Password Policy.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetAccountPasswordPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetAccountSummary' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetAccountSummaryResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves account level information about account entity usage and IAM quotas.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetAccountSummary',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
            ),
        ),
        'GetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetGroupResponse',
            'responseType' => 'model',
            'summary' => 'Returns a list of users that are in the specified group. You can paginate the results using the MaxItems and Marker parameters.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of user names you want in the response. If there are additional user names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetGroupPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetGroupPolicyResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves the specified policy document for the specified group. The returned policy is URL-encoded according to RFC 3986. For more information about RFC 3986, go to http://www.faqs.org/rfcs/rfc3986.html.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetGroupPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group the policy is associated with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document to get.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetInstanceProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetInstanceProfileResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves information about the specified instance profile, including the instance profile\'s path, GUID, ARN, and role. For more information about instance profiles, go to About Instance Profiles. For more information about ARNs, go to ARNs.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetInstanceProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'InstanceProfileName' => array(
                    'required' => true,
                    'description' => 'Name of the instance profile to get information about.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetLoginProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetLoginProfileResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves the user name and password create date for the specified user.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetLoginProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user whose login profile you want to retrieve.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetRole' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetRoleResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves information about the specified role, including the role\'s path, GUID, ARN, and the policy granting permission to EC2 to assume the role. For more information about ARNs, go to ARNs. For more information about roles, go to Working with Roles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetRole',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role to get information about.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetRolePolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetRolePolicyResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves the specified policy document for the specified role. For more information about roles, go to Working with Roles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetRolePolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role associated with the policy.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document to get.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetServerCertificate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetServerCertificateResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves information about the specified server certificate.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetServerCertificate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'ServerCertificateName' => array(
                    'required' => true,
                    'description' => 'The name of the server certificate you want to retrieve information about.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetUser' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetUserResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves information about the specified user, including the user\'s path, GUID, and ARN.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetUser',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'Name of the user to get information about.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'GetUserPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetUserPolicyResponse',
            'responseType' => 'model',
            'summary' => 'Retrieves the specified policy document for the specified user. The returned policy is URL-encoded according to RFC 3986. For more information about RFC 3986, go to http://www.faqs.org/rfcs/rfc3986.html.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetUserPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user who the policy is associated with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document to get.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListAccessKeys' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListAccessKeysResponse',
            'responseType' => 'model',
            'summary' => 'Returns information about the Access Key IDs associated with the specified user. If there are none, the action returns an empty list.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListAccessKeys',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'Name of the user.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'Marker' => array(
                    'description' => 'Use this parameter only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this parameter only when paginating results to indicate the maximum number of keys you want in the response. If there are additional keys beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListAccountAliases' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListAccountAliasesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the account aliases associated with the account. For information about using an AWS account alias, see Using an Alias for Your AWS Account ID in Using AWS Identity and Access Management.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListAccountAliases',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of account aliases you want in the response. If there are additional account aliases beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
        ),
        'ListGroupPolicies' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListGroupPoliciesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the names of the policies associated with the specified group. If there are none, the action returns an empty list.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListGroupPolicies',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'The name of the group to list policies for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of policy names you want in the response. If there are additional policy names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListGroupsResponse',
            'responseType' => 'model',
            'summary' => 'Lists the groups that have the specified path prefix.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'PathPrefix' => array(
                    'description' => 'The path prefix for filtering the results. For example: /division_abc/subdivision_xyz/, which would get all groups whose path starts with /division_abc/subdivision_xyz/.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of groups you want in the response. If there are additional groups beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
        ),
        'ListGroupsForUser' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListGroupsForUserResponse',
            'responseType' => 'model',
            'summary' => 'Lists the groups the specified user belongs to.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListGroupsForUser',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'The name of the user to list groups for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of groups you want in the response. If there are additional groups beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListInstanceProfiles' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListInstanceProfilesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the instance profiles that have the specified path prefix. If there are none, the action returns an empty list. For more information about instance profiles, go to About Instance Profiles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListInstanceProfiles',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'PathPrefix' => array(
                    'description' => 'The path prefix for filtering the results. For example: /application_abc/component_xyz/, which would get all instance profiles whose path starts with /application_abc/component_xyz/.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'Marker' => array(
                    'description' => 'Use this parameter only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this parameter only when paginating results to indicate the maximum number of user names you want in the response. If there are additional user names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
        ),
        'ListInstanceProfilesForRole' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListInstanceProfilesForRoleResponse',
            'responseType' => 'model',
            'summary' => 'Lists the instance profiles that have the specified associated role. If there are none, the action returns an empty list. For more information about instance profiles, go to About Instance Profiles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListInstanceProfilesForRole',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'The name of the role to list instance profiles for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'Marker' => array(
                    'description' => 'Use this parameter only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this parameter only when paginating results to indicate the maximum number of user names you want in the response. If there are additional user names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListMFADevices' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListMFADevicesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the MFA devices. If the request includes the user name, then this action lists all the MFA devices associated with the specified user name. If you do not specify a user name, IAM determines the user name implicitly based on the AWS Access Key ID signing the request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListMFADevices',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'Name of the user whose MFA devices you want to list.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of MFA devices you want in the response. If there are additional MFA devices beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListRolePolicies' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListRolePoliciesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the names of the policies associated with the specified role. If there are none, the action returns an empty list.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListRolePolicies',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'The name of the role to list policies for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'Marker' => array(
                    'description' => 'Use this parameter only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this parameter only when paginating results to indicate the maximum number of user names you want in the response. If there are additional user names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListRoles' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListRolesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the roles that have the specified path prefix. If there are none, the action returns an empty list. For more information about roles, go to Working with Roles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListRoles',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'PathPrefix' => array(
                    'description' => 'The path prefix for filtering the results. For example: /application_abc/component_xyz/, which would get all roles whose path starts with /application_abc/component_xyz/.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'Marker' => array(
                    'description' => 'Use this parameter only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this parameter only when paginating results to indicate the maximum number of user names you want in the response. If there are additional user names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
        ),
        'ListServerCertificates' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListServerCertificatesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the server certificates that have the specified path prefix. If none exist, the action returns an empty list.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListServerCertificates',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'PathPrefix' => array(
                    'description' => 'The path prefix for filtering the results. For example: /company/servercerts would get all server certificates for which the path starts with /company/servercerts.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of server certificates you want in the response. If there are additional server certificates beyond the maximum you specify, the IsTruncated response element will be set to true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
        ),
        'ListSigningCertificates' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListSigningCertificatesResponse',
            'responseType' => 'model',
            'summary' => 'Returns information about the signing certificates associated with the specified user. If there are none, the action returns an empty list.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListSigningCertificates',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'The name of the user.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of certificate IDs you want in the response. If there are additional certificate IDs beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListUserPolicies' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListUserPoliciesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the names of the policies associated with the specified user. If there are none, the action returns an empty list.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListUserPolicies',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'The name of the user to list policies for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this only when paginating results to indicate the maximum number of policy names you want in the response. If there are additional policy names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ListUsers' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListUsersResponse',
            'responseType' => 'model',
            'summary' => 'Lists the users that have the specified path prefix. If there are none, the action returns an empty list.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListUsers',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'PathPrefix' => array(
                    'description' => 'The path prefix for filtering the results. For example: /division_abc/subdivision_xyz/, which would get all user names whose path starts with /division_abc/subdivision_xyz/.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'Marker' => array(
                    'description' => 'Use this parameter only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this parameter only when paginating results to indicate the maximum number of user names you want in the response. If there are additional user names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
        ),
        'ListVirtualMFADevices' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListVirtualMFADevicesResponse',
            'responseType' => 'model',
            'summary' => 'Lists the virtual MFA devices under the AWS account by assignment status. If you do not specify an assignment status, the action returns a list of all virtual MFA devices. Assignment status can be Assigned, Unassigned, or Any.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListVirtualMFADevices',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'AssignmentStatus' => array(
                    'description' => 'The status (unassigned or assigned) of the devices to list. If you do not specify an AssignmentStatus, the action defaults to Any which lists both assigned and unassigned virtual MFA devices.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Assigned',
                        'Unassigned',
                        'Any',
                    ),
                ),
                'Marker' => array(
                    'description' => 'Use this parameter only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 320,
                ),
                'MaxItems' => array(
                    'description' => 'Use this parameter only when paginating results to indicate the maximum number of user names you want in the response. If there are additional user names beyond the maximum you specify, the IsTruncated response element is true.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
            ),
        ),
        'PutGroupPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adds (or updates) a policy document associated with the specified group. For information about policies, refer to Overview of Policies in Using AWS Identity and Access Management.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutGroupPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group to associate the policy with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyDocument' => array(
                    'required' => true,
                    'description' => 'The policy document.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 131072,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because the policy document was malformed. The error message describes the specific error.',
                    'class' => 'MalformedPolicyDocumentException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'PutRolePolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adds (or updates) a policy document associated with the specified role. For information about policies, go to Overview of Policies in Using AWS Identity and Access Management.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutRolePolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role to associate the policy with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyDocument' => array(
                    'required' => true,
                    'description' => 'The policy document.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 131072,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because the policy document was malformed. The error message describes the specific error.',
                    'class' => 'MalformedPolicyDocumentException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'PutUserPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adds (or updates) a policy document associated with the specified user. For information about policies, refer to Overview of Policies in Using AWS Identity and Access Management.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutUserPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user to associate the policy with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'Name of the policy document.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'PolicyDocument' => array(
                    'required' => true,
                    'description' => 'The policy document.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 131072,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because the policy document was malformed. The error message describes the specific error.',
                    'class' => 'MalformedPolicyDocumentException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'RemoveRoleFromInstanceProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Removes the specified role from the specified instance profile.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RemoveRoleFromInstanceProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'InstanceProfileName' => array(
                    'required' => true,
                    'description' => 'Name of the instance profile to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role to remove.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'RemoveUserFromGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Removes the specified user from the specified group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RemoveUserFromGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user to remove.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'ResyncMFADevice' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Synchronizes the specified MFA device with AWS servers.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResyncMFADevice',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user whose MFA device you want to resynchronize.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'SerialNumber' => array(
                    'required' => true,
                    'description' => 'Serial number that uniquely identifies the MFA device.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 9,
                    'maxLength' => 256,
                ),
                'AuthenticationCode1' => array(
                    'required' => true,
                    'description' => 'An authentication code emitted by the device.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 6,
                    'maxLength' => 6,
                ),
                'AuthenticationCode2' => array(
                    'required' => true,
                    'description' => 'A subsequent authentication code emitted by the device.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 6,
                    'maxLength' => 6,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because the authentication code was not recognized. The error message describes the specific error.',
                    'class' => 'InvalidAuthenticationCodeException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'UpdateAccessKey' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Changes the status of the specified access key from Active to Inactive, or vice versa. This action can be used to disable a user\'s key as part of a key rotation work flow.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateAccessKey',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'Name of the user whose key you want to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'AccessKeyId' => array(
                    'required' => true,
                    'description' => 'The Access Key ID of the Secret Access Key you want to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 16,
                    'maxLength' => 32,
                ),
                'Status' => array(
                    'required' => true,
                    'description' => 'The status you want to assign to the Secret Access Key. Active means the key can be used for API calls to AWS, while Inactive means the key cannot be used.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Active',
                        'Inactive',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'UpdateAccountPasswordPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Updates the password policy settings for the account. For more information about using a password policy, go to Managing an IAM Password Policy.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateAccountPasswordPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'MinimumPasswordLength' => array(
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 6,
                    'maximum' => 128,
                ),
                'RequireSymbols' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'RequireNumbers' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'RequireUppercaseCharacters' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'RequireLowercaseCharacters' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'AllowUsersToChangePassword' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because the policy document was malformed. The error message describes the specific error.',
                    'class' => 'MalformedPolicyDocumentException',
                ),
            ),
        ),
        'UpdateAssumeRolePolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Updates the policy that grants an entity permission to assume a role. Currently, only an Amazon EC2 instance can assume a role. For more information about roles, go to Working with Roles.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateAssumeRolePolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'RoleName' => array(
                    'required' => true,
                    'description' => 'Name of the role to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'PolicyDocument' => array(
                    'required' => true,
                    'description' => 'The policy that grants an entity permission to assume the role.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 131072,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because the policy document was malformed. The error message describes the specific error.',
                    'class' => 'MalformedPolicyDocumentException',
                ),
            ),
        ),
        'UpdateGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Updates the name and/or the path of the specified group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the group to update. If you\'re changing the name of the group, this is the original name.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'NewPath' => array(
                    'description' => 'New path for the group. Only include this if changing the group\'s path.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'NewGroupName' => array(
                    'description' => 'New name for the group. Only include this if changing the group\'s name.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
            ),
        ),
        'UpdateLoginProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Changes the password for the specified user.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateLoginProfile',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user whose password you want to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'Password' => array(
                    'description' => 'The new password for the user name.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that is temporarily unmodifiable, such as a user name that was deleted and then recreated. The error indicates that the request is likely to succeed if you try again after waiting several minutes. The error message describes the entity.',
                    'class' => 'EntityTemporarilyUnmodifiableException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because the provided password did not meet the requirements imposed by the account password policy.',
                    'class' => 'PasswordPolicyViolationException',
                ),
            ),
        ),
        'UpdateServerCertificate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Updates the name and/or the path of the specified server certificate.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateServerCertificate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'ServerCertificateName' => array(
                    'required' => true,
                    'description' => 'The name of the server certificate that you want to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'NewPath' => array(
                    'description' => 'The new path for the server certificate. Include this only if you are updating the server certificate\'s path.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'NewServerCertificateName' => array(
                    'description' => 'The new name for the server certificate. Include this only if you are updating the server certificate\'s name.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
            ),
        ),
        'UpdateSigningCertificate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Changes the status of the specified signing certificate from active to disabled, or vice versa. This action can be used to disable a user\'s signing certificate as part of a certificate rotation work flow.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateSigningCertificate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'Name of the user the signing certificate belongs to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'CertificateId' => array(
                    'required' => true,
                    'description' => 'The ID of the signing certificate you want to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 24,
                    'maxLength' => 128,
                ),
                'Status' => array(
                    'required' => true,
                    'description' => 'The status you want to assign to the certificate. Active means the certificate can be used for API calls to AWS, while Inactive means the certificate cannot be used.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Active',
                        'Inactive',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
        'UpdateUser' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Updates the name and/or the path of the specified user.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateUser',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'required' => true,
                    'description' => 'Name of the user to update. If you\'re changing the name of the user, this is the original user name.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'NewPath' => array(
                    'description' => 'New path for the user. Include this parameter only if you\'re changing the user\'s path.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'NewUserName' => array(
                    'description' => 'New name for the user. Include this parameter only if you\'re changing the user\'s name.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that is temporarily unmodifiable, such as a user name that was deleted and then recreated. The error indicates that the request is likely to succeed if you try again after waiting several minutes. The error message describes the entity.',
                    'class' => 'EntityTemporarilyUnmodifiableException',
                ),
            ),
        ),
        'UploadServerCertificate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UploadServerCertificateResponse',
            'responseType' => 'model',
            'summary' => 'Uploads a server certificate entity for the AWS account. The server certificate entity includes a public key certificate, a private key, and an optional certificate chain, which should all be PEM-encoded.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UploadServerCertificate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'Path' => array(
                    'description' => 'The path for the server certificate. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 512,
                ),
                'ServerCertificateName' => array(
                    'required' => true,
                    'description' => 'The name for the server certificate. Do not include the path in this value.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'CertificateBody' => array(
                    'required' => true,
                    'description' => 'The contents of the public key certificate in PEM-encoded format.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 16384,
                ),
                'PrivateKey' => array(
                    'required' => true,
                    'description' => 'The contents of the private key in PEM-encoded format.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 16384,
                ),
                'CertificateChain' => array(
                    'description' => 'The contents of the certificate chain. This is typically a concatenation of the PEM-encoded public key certificates of the chain.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 2097152,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because the certificate was malformed or expired. The error message describes the specific error.',
                    'class' => 'MalformedCertificateException',
                ),
                array(
                    'reason' => 'The request was rejected because the public key certificate and the private key do not match.',
                    'class' => 'KeyPairMismatchException',
                ),
            ),
        ),
        'UploadSigningCertificate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UploadSigningCertificateResponse',
            'responseType' => 'model',
            'summary' => 'Uploads an X.509 signing certificate and associates it with the specified user. Some AWS services use X.509 signing certificates to validate requests that are signed with a corresponding private key. When you upload the certificate, its default status is Active.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UploadSigningCertificate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-08',
                ),
                'UserName' => array(
                    'description' => 'Name of the user the signing certificate is for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'CertificateBody' => array(
                    'required' => true,
                    'description' => 'The contents of the signing certificate.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 16384,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request was rejected because it attempted to create resources beyond the current AWS account limits. The error message describes the limit exceeded.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The request was rejected because it attempted to create a resource that already exists.',
                    'class' => 'EntityAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request was rejected because the certificate was malformed or expired. The error message describes the specific error.',
                    'class' => 'MalformedCertificateException',
                ),
                array(
                    'reason' => 'The request was rejected because the certificate is invalid.',
                    'class' => 'InvalidCertificateException',
                ),
                array(
                    'reason' => 'The request was rejected because the same certificate is associated to another user under the account.',
                    'class' => 'DuplicateCertificateException',
                ),
                array(
                    'reason' => 'The request was rejected because it referenced an entity that does not exist. The error message describes the entity.',
                    'class' => 'NoSuchEntityException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'CreateAccessKeyResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AccessKey' => array(
                    'description' => 'Information about the access key.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'UserName' => array(
                            'description' => 'Name of the user the key is associated with.',
                            'type' => 'string',
                        ),
                        'AccessKeyId' => array(
                            'description' => 'The ID for this access key.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of the access key. Active means the key is valid for API calls, while Inactive means it is not.',
                            'type' => 'string',
                        ),
                        'SecretAccessKey' => array(
                            'description' => 'The secret key used to sign requests.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the access key was created.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'CreateGroupResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Group' => array(
                    'description' => 'Information about the group.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the group. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'GroupName' => array(
                            'description' => 'The name that identifies the group.',
                            'type' => 'string',
                        ),
                        'GroupId' => array(
                            'description' => 'The stable and unique string identifying the group. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the group. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the group was created.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'CreateInstanceProfileResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceProfile' => array(
                    'description' => 'Information about the instance profile.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the instance profile. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'InstanceProfileName' => array(
                            'description' => 'The name identifying the instance profile.',
                            'type' => 'string',
                        ),
                        'InstanceProfileId' => array(
                            'description' => 'The stable and unique string identifying the instance profile. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the instance profile. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the instance profile was created.',
                            'type' => 'string',
                        ),
                        'Roles' => array(
                            'description' => 'The role associated with the instance profile.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Role',
                                'description' => 'The Role data type contains information about a role.',
                                'type' => 'object',
                                'sentAs' => 'member',
                                'properties' => array(
                                    'Path' => array(
                                        'description' => 'Path to the role. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'RoleName' => array(
                                        'description' => 'The name identifying the role.',
                                        'type' => 'string',
                                    ),
                                    'RoleId' => array(
                                        'description' => 'The stable and unique string identifying the role. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'Arn' => array(
                                        'description' => 'The Amazon Resource Name (ARN) specifying the role. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'CreateDate' => array(
                                        'description' => 'The date when the role was created.',
                                        'type' => 'string',
                                    ),
                                    'AssumeRolePolicyDocument' => array(
                                        'description' => 'The policy that grants an entity permission to assume the role.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateLoginProfileResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LoginProfile' => array(
                    'description' => 'The user name and password create date.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'UserName' => array(
                            'description' => 'The name of the user, which can be used for signing into the AWS Management Console.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the password for the user was created.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'CreateRoleResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Role' => array(
                    'description' => 'Information about the role.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the role. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'RoleName' => array(
                            'description' => 'The name identifying the role.',
                            'type' => 'string',
                        ),
                        'RoleId' => array(
                            'description' => 'The stable and unique string identifying the role. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the role. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the role was created.',
                            'type' => 'string',
                        ),
                        'AssumeRolePolicyDocument' => array(
                            'description' => 'The policy that grants an entity permission to assume the role.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'CreateUserResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'User' => array(
                    'description' => 'Information about the user.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the user. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'UserName' => array(
                            'description' => 'The name identifying the user.',
                            'type' => 'string',
                        ),
                        'UserId' => array(
                            'description' => 'The stable and unique string identifying the user. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the user. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the user was created.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'CreateVirtualMFADeviceResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VirtualMFADevice' => array(
                    'description' => 'A newly created virtual MFA device.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'SerialNumber' => array(
                            'description' => 'The serial number associated with VirtualMFADevice.',
                            'type' => 'string',
                        ),
                        'Base32StringSeed' => array(
                            'description' => 'The Base32 seed defined as specified in RFC3548. The Base32StringSeed is Base64-encoded.',
                            'type' => 'string',
                        ),
                        'QRCodePNG' => array(
                            'description' => 'A QR code PNG image that encodes otpauth://totp/$virtualMFADeviceName@$AccountName? secret=$Base32String where $virtualMFADeviceName is one of the create call arguments, AccountName is the user name if set (accountId otherwise), and Base32String is the seed in Base32 format. The Base32String is Base64-encoded.',
                            'type' => 'string',
                        ),
                        'User' => array(
                            'description' => 'The User data type contains information about a user.',
                            'type' => 'object',
                            'properties' => array(
                                'Path' => array(
                                    'description' => 'Path to the user. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                    'type' => 'string',
                                ),
                                'UserName' => array(
                                    'description' => 'The name identifying the user.',
                                    'type' => 'string',
                                ),
                                'UserId' => array(
                                    'description' => 'The stable and unique string identifying the user. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                    'type' => 'string',
                                ),
                                'Arn' => array(
                                    'description' => 'The Amazon Resource Name (ARN) specifying the user. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                    'type' => 'string',
                                ),
                                'CreateDate' => array(
                                    'description' => 'The date when the user was created.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'EnableDate' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'GetAccountPasswordPolicyResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PasswordPolicy' => array(
                    'description' => 'The PasswordPolicy data type contains information about the account password policy.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'MinimumPasswordLength' => array(
                            'description' => 'Minimum length to require for IAM user passwords.',
                            'type' => 'numeric',
                        ),
                        'RequireSymbols' => array(
                            'description' => 'Specifies whether to require symbols for IAM user passwords.',
                            'type' => 'boolean',
                        ),
                        'RequireNumbers' => array(
                            'description' => 'Specifies whether to require numbers for IAM user passwords.',
                            'type' => 'boolean',
                        ),
                        'RequireUppercaseCharacters' => array(
                            'description' => 'Specifies whether to require uppercase characters for IAM user passwords.',
                            'type' => 'boolean',
                        ),
                        'RequireLowercaseCharacters' => array(
                            'description' => 'Specifies whether to require lowercase characters for IAM user passwords.',
                            'type' => 'boolean',
                        ),
                        'AllowUsersToChangePassword' => array(
                            'description' => 'Specifies whether to allow IAM users to change their own password.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
            ),
        ),
        'GetAccountSummaryResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SummaryMap' => array(
                    'description' => 'A set of key value pairs containing account-level information.',
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlMap' => array(
                            'Users',
                            'UsersQuota',
                            'Groups',
                            'GroupsQuota',
                            'ServerCertificates',
                            'ServerCertificatesQuota',
                            'UserPolicySizeQuota',
                            'GroupPolicySizeQuota',
                            'GroupsPerUserQuota',
                            'SigningCertificatesPerUserQuota',
                            'AccessKeysPerUserQuota',
                            'MFADevices',
                            'MFADevicesInUse',
                            'AccountMFAEnabled',
                        ),
                    ),
                    'filters' => array(
                        array(
                            'method' => 'Aws\\Common\\Command\\XmlResponseLocationVisitor::xmlMap',
                            'args' => array(
                                '@value',
                                'entry',
                                'key',
                                'value',
                            ),
                        ),
                    ),
                    'items' => array(
                        'name' => 'entry',
                        'type' => 'object',
                        'sentAs' => 'entry',
                        'additionalProperties' => true,
                        'properties' => array(
                            'key' => array(
                                'type' => 'string',
                            ),
                            'value' => array(
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                    'additionalProperties' => false,
                ),
            ),
        ),
        'GetGroupResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Group' => array(
                    'description' => 'Information about the group.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the group. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'GroupName' => array(
                            'description' => 'The name that identifies the group.',
                            'type' => 'string',
                        ),
                        'GroupId' => array(
                            'description' => 'The stable and unique string identifying the group. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the group. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the group was created.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Users' => array(
                    'description' => 'A list of users in the group.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'User',
                        'description' => 'The User data type contains information about a user.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Path' => array(
                                'description' => 'Path to the user. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'UserName' => array(
                                'description' => 'The name identifying the user.',
                                'type' => 'string',
                            ),
                            'UserId' => array(
                                'description' => 'The stable and unique string identifying the user. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'description' => 'The Amazon Resource Name (ARN) specifying the user. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'CreateDate' => array(
                                'description' => 'The date when the user was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more user names to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more user names in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, then this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'GetGroupPolicyResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GroupName' => array(
                    'description' => 'The group the policy is associated with.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'PolicyName' => array(
                    'description' => 'The name of the policy.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'PolicyDocument' => array(
                    'description' => 'The policy document.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'GetInstanceProfileResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceProfile' => array(
                    'description' => 'Information about the instance profile.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the instance profile. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'InstanceProfileName' => array(
                            'description' => 'The name identifying the instance profile.',
                            'type' => 'string',
                        ),
                        'InstanceProfileId' => array(
                            'description' => 'The stable and unique string identifying the instance profile. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the instance profile. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the instance profile was created.',
                            'type' => 'string',
                        ),
                        'Roles' => array(
                            'description' => 'The role associated with the instance profile.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Role',
                                'description' => 'The Role data type contains information about a role.',
                                'type' => 'object',
                                'sentAs' => 'member',
                                'properties' => array(
                                    'Path' => array(
                                        'description' => 'Path to the role. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'RoleName' => array(
                                        'description' => 'The name identifying the role.',
                                        'type' => 'string',
                                    ),
                                    'RoleId' => array(
                                        'description' => 'The stable and unique string identifying the role. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'Arn' => array(
                                        'description' => 'The Amazon Resource Name (ARN) specifying the role. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'CreateDate' => array(
                                        'description' => 'The date when the role was created.',
                                        'type' => 'string',
                                    ),
                                    'AssumeRolePolicyDocument' => array(
                                        'description' => 'The policy that grants an entity permission to assume the role.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'GetLoginProfileResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LoginProfile' => array(
                    'description' => 'User name and password create date for the user.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'UserName' => array(
                            'description' => 'The name of the user, which can be used for signing into the AWS Management Console.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the password for the user was created.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'GetRoleResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Role' => array(
                    'description' => 'Information about the role.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the role. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'RoleName' => array(
                            'description' => 'The name identifying the role.',
                            'type' => 'string',
                        ),
                        'RoleId' => array(
                            'description' => 'The stable and unique string identifying the role. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the role. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the role was created.',
                            'type' => 'string',
                        ),
                        'AssumeRolePolicyDocument' => array(
                            'description' => 'The policy that grants an entity permission to assume the role.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'GetRolePolicyResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RoleName' => array(
                    'description' => 'The role the policy is associated with.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'PolicyName' => array(
                    'description' => 'The name of the policy.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'PolicyDocument' => array(
                    'description' => 'The policy document.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'GetServerCertificateResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ServerCertificate' => array(
                    'description' => 'Information about the server certificate.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ServerCertificateMetadata' => array(
                            'description' => 'The meta information of the server certificate, such as its name, path, ID, and ARN.',
                            'type' => 'object',
                            'properties' => array(
                                'Path' => array(
                                    'description' => 'Path to the server certificate. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                    'type' => 'string',
                                ),
                                'ServerCertificateName' => array(
                                    'description' => 'The name that identifies the server certificate.',
                                    'type' => 'string',
                                ),
                                'ServerCertificateId' => array(
                                    'description' => 'The stable and unique string identifying the server certificate. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                    'type' => 'string',
                                ),
                                'Arn' => array(
                                    'description' => 'The Amazon Resource Name (ARN) specifying the server certificate. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                    'type' => 'string',
                                ),
                                'UploadDate' => array(
                                    'description' => 'The date when the server certificate was uploaded.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'CertificateBody' => array(
                            'description' => 'The contents of the public key certificate.',
                            'type' => 'string',
                        ),
                        'CertificateChain' => array(
                            'description' => 'The contents of the public key certificate chain.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'GetUserResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'User' => array(
                    'description' => 'Information about the user.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the user. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'UserName' => array(
                            'description' => 'The name identifying the user.',
                            'type' => 'string',
                        ),
                        'UserId' => array(
                            'description' => 'The stable and unique string identifying the user. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the user. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'CreateDate' => array(
                            'description' => 'The date when the user was created.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'GetUserPolicyResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'UserName' => array(
                    'description' => 'The user the policy is associated with.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'PolicyName' => array(
                    'description' => 'The name of the policy.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'PolicyDocument' => array(
                    'description' => 'The policy document.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListAccessKeysResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AccessKeyMetadata' => array(
                    'description' => 'A list of access key metadata.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'AccessKeyMetadata',
                        'description' => 'The AccessKey data type contains information about an AWS access key, without its secret key.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'UserName' => array(
                                'description' => 'Name of the user the key is associated with.',
                                'type' => 'string',
                            ),
                            'AccessKeyId' => array(
                                'description' => 'The ID for this access key.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The status of the access key. Active means the key is valid for API calls, while Inactive means it is not.',
                                'type' => 'string',
                            ),
                            'CreateDate' => array(
                                'description' => 'The date when the access key was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more keys to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more keys in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListAccountAliasesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AccountAliases' => array(
                    'description' => 'A list of aliases associated with the account.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'accountAliasType',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more account aliases to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more account aliases in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'Use this only when paginating results, and only in a subsequent request after you\'ve received a response where the results are truncated. Set it to the value of the Marker element in the response you just received.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListGroupPoliciesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PolicyNames' => array(
                    'description' => 'A list of policy names.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'policyNameType',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more policy names to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more policy names in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListGroupsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Groups' => array(
                    'description' => 'A list of groups.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Group',
                        'description' => 'The Group data type contains information about a group.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Path' => array(
                                'description' => 'Path to the group. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'GroupName' => array(
                                'description' => 'The name that identifies the group.',
                                'type' => 'string',
                            ),
                            'GroupId' => array(
                                'description' => 'The stable and unique string identifying the group. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'description' => 'The Amazon Resource Name (ARN) specifying the group. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'CreateDate' => array(
                                'description' => 'The date when the group was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more groups to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more groups in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListGroupsForUserResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Groups' => array(
                    'description' => 'A list of groups.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Group',
                        'description' => 'The Group data type contains information about a group.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Path' => array(
                                'description' => 'Path to the group. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'GroupName' => array(
                                'description' => 'The name that identifies the group.',
                                'type' => 'string',
                            ),
                            'GroupId' => array(
                                'description' => 'The stable and unique string identifying the group. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'description' => 'The Amazon Resource Name (ARN) specifying the group. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'CreateDate' => array(
                                'description' => 'The date when the group was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more groups to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more groups in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListInstanceProfilesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceProfiles' => array(
                    'description' => 'A list of instance profiles.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'InstanceProfile',
                        'description' => 'The InstanceProfile data type contains information about an instance profile.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Path' => array(
                                'description' => 'Path to the instance profile. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'InstanceProfileName' => array(
                                'description' => 'The name identifying the instance profile.',
                                'type' => 'string',
                            ),
                            'InstanceProfileId' => array(
                                'description' => 'The stable and unique string identifying the instance profile. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'description' => 'The Amazon Resource Name (ARN) specifying the instance profile. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'CreateDate' => array(
                                'description' => 'The date when the instance profile was created.',
                                'type' => 'string',
                            ),
                            'Roles' => array(
                                'description' => 'The role associated with the instance profile.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Role',
                                    'description' => 'The Role data type contains information about a role.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Path' => array(
                                            'description' => 'Path to the role. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                            'type' => 'string',
                                        ),
                                        'RoleName' => array(
                                            'description' => 'The name identifying the role.',
                                            'type' => 'string',
                                        ),
                                        'RoleId' => array(
                                            'description' => 'The stable and unique string identifying the role. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                            'type' => 'string',
                                        ),
                                        'Arn' => array(
                                            'description' => 'The Amazon Resource Name (ARN) specifying the role. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                            'type' => 'string',
                                        ),
                                        'CreateDate' => array(
                                            'description' => 'The date when the role was created.',
                                            'type' => 'string',
                                        ),
                                        'AssumeRolePolicyDocument' => array(
                                            'description' => 'The policy that grants an entity permission to assume the role.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more instance profiles to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more instance profiles in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListInstanceProfilesForRoleResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceProfiles' => array(
                    'description' => 'A list of instance profiles.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'InstanceProfile',
                        'description' => 'The InstanceProfile data type contains information about an instance profile.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Path' => array(
                                'description' => 'Path to the instance profile. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'InstanceProfileName' => array(
                                'description' => 'The name identifying the instance profile.',
                                'type' => 'string',
                            ),
                            'InstanceProfileId' => array(
                                'description' => 'The stable and unique string identifying the instance profile. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'description' => 'The Amazon Resource Name (ARN) specifying the instance profile. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'CreateDate' => array(
                                'description' => 'The date when the instance profile was created.',
                                'type' => 'string',
                            ),
                            'Roles' => array(
                                'description' => 'The role associated with the instance profile.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Role',
                                    'description' => 'The Role data type contains information about a role.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Path' => array(
                                            'description' => 'Path to the role. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                            'type' => 'string',
                                        ),
                                        'RoleName' => array(
                                            'description' => 'The name identifying the role.',
                                            'type' => 'string',
                                        ),
                                        'RoleId' => array(
                                            'description' => 'The stable and unique string identifying the role. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                            'type' => 'string',
                                        ),
                                        'Arn' => array(
                                            'description' => 'The Amazon Resource Name (ARN) specifying the role. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                            'type' => 'string',
                                        ),
                                        'CreateDate' => array(
                                            'description' => 'The date when the role was created.',
                                            'type' => 'string',
                                        ),
                                        'AssumeRolePolicyDocument' => array(
                                            'description' => 'The policy that grants an entity permission to assume the role.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more instance profiles to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more instance profiles in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListMFADevicesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'MFADevices' => array(
                    'description' => 'A list of MFA devices.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'MFADevice',
                        'description' => 'The MFADevice data type contains information about an MFA device.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'UserName' => array(
                                'description' => 'The user with whom the MFA device is associated.',
                                'type' => 'string',
                            ),
                            'SerialNumber' => array(
                                'description' => 'The serial number that uniquely identifies the MFA device. For virtual MFA devices, the serial number is the device ARN.',
                                'type' => 'string',
                            ),
                            'EnableDate' => array(
                                'description' => 'The date when the MFA device was enabled for the user.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more MFA devices to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more MFA devices in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListRolePoliciesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PolicyNames' => array(
                    'description' => 'A list of policy names.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'policyNameType',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more policy names to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more policy names in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListRolesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Roles' => array(
                    'description' => 'A list of roles.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Role',
                        'description' => 'The Role data type contains information about a role.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Path' => array(
                                'description' => 'Path to the role. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'RoleName' => array(
                                'description' => 'The name identifying the role.',
                                'type' => 'string',
                            ),
                            'RoleId' => array(
                                'description' => 'The stable and unique string identifying the role. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'description' => 'The Amazon Resource Name (ARN) specifying the role. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'CreateDate' => array(
                                'description' => 'The date when the role was created.',
                                'type' => 'string',
                            ),
                            'AssumeRolePolicyDocument' => array(
                                'description' => 'The policy that grants an entity permission to assume the role.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more roles to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more roles in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListServerCertificatesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ServerCertificateMetadataList' => array(
                    'description' => 'A list of server certificates.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ServerCertificateMetadata',
                        'description' => 'ServerCertificateMetadata contains information about a server certificate without its certificate body, certificate chain, and private key.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Path' => array(
                                'description' => 'Path to the server certificate. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'ServerCertificateName' => array(
                                'description' => 'The name that identifies the server certificate.',
                                'type' => 'string',
                            ),
                            'ServerCertificateId' => array(
                                'description' => 'The stable and unique string identifying the server certificate. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'description' => 'The Amazon Resource Name (ARN) specifying the server certificate. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'UploadDate' => array(
                                'description' => 'The date when the server certificate was uploaded.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more server certificates to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more server certificates in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListSigningCertificatesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Certificates' => array(
                    'description' => 'A list of the user\'s signing certificate information.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'SigningCertificate',
                        'description' => 'The SigningCertificate data type contains information about an X.509 signing certificate.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'UserName' => array(
                                'description' => 'Name of the user the signing certificate is associated with.',
                                'type' => 'string',
                            ),
                            'CertificateId' => array(
                                'description' => 'The ID for the signing certificate.',
                                'type' => 'string',
                            ),
                            'CertificateBody' => array(
                                'description' => 'The contents of the signing certificate.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The status of the signing certificate. Active means the key is valid for API calls, while Inactive means it is not.',
                                'type' => 'string',
                            ),
                            'UploadDate' => array(
                                'description' => 'The date when the signing certificate was uploaded.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more certificate IDs to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more certificates in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListUserPoliciesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PolicyNames' => array(
                    'description' => 'A list of policy names.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'policyNameType',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more policy names to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more policy names in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListUsersResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Users' => array(
                    'description' => 'A list of users.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'User',
                        'description' => 'The User data type contains information about a user.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Path' => array(
                                'description' => 'Path to the user. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'UserName' => array(
                                'description' => 'The name identifying the user.',
                                'type' => 'string',
                            ),
                            'UserId' => array(
                                'description' => 'The stable and unique string identifying the user. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'Arn' => array(
                                'description' => 'The Amazon Resource Name (ARN) specifying the user. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                'type' => 'string',
                            ),
                            'CreateDate' => array(
                                'description' => 'The date when the user was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more user names to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more users in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListVirtualMFADevicesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VirtualMFADevices' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'VirtualMFADevice',
                        'description' => 'The VirtualMFADevice data type contains information about a virtual MFA device.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'SerialNumber' => array(
                                'description' => 'The serial number associated with VirtualMFADevice.',
                                'type' => 'string',
                            ),
                            'Base32StringSeed' => array(
                                'description' => 'The Base32 seed defined as specified in RFC3548. The Base32StringSeed is Base64-encoded.',
                                'type' => 'string',
                            ),
                            'QRCodePNG' => array(
                                'description' => 'A QR code PNG image that encodes otpauth://totp/$virtualMFADeviceName@$AccountName? secret=$Base32String where $virtualMFADeviceName is one of the create call arguments, AccountName is the user name if set (accountId otherwise), and Base32String is the seed in Base32 format. The Base32String is Base64-encoded.',
                                'type' => 'string',
                            ),
                            'User' => array(
                                'description' => 'The User data type contains information about a user.',
                                'type' => 'object',
                                'properties' => array(
                                    'Path' => array(
                                        'description' => 'Path to the user. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'UserName' => array(
                                        'description' => 'The name identifying the user.',
                                        'type' => 'string',
                                    ),
                                    'UserId' => array(
                                        'description' => 'The stable and unique string identifying the user. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'Arn' => array(
                                        'description' => 'The Amazon Resource Name (ARN) specifying the user. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                                        'type' => 'string',
                                    ),
                                    'CreateDate' => array(
                                        'description' => 'The date when the user was created.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'EnableDate' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more items to list. If your results were truncated, you can make a subsequent pagination request using the Marker request parameter to retrieve more items the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value to use for the Marker parameter in a subsequent pagination request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'UploadServerCertificateResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ServerCertificateMetadata' => array(
                    'description' => 'The meta information of the uploaded server certificate without its certificate body, certificate chain, and private key.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Path' => array(
                            'description' => 'Path to the server certificate. For more information about paths, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'ServerCertificateName' => array(
                            'description' => 'The name that identifies the server certificate.',
                            'type' => 'string',
                        ),
                        'ServerCertificateId' => array(
                            'description' => 'The stable and unique string identifying the server certificate. For more information about IDs, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'Arn' => array(
                            'description' => 'The Amazon Resource Name (ARN) specifying the server certificate. For more information about ARNs and how to use them in policies, see Identifiers for IAM Entities in Using AWS Identity and Access Management.',
                            'type' => 'string',
                        ),
                        'UploadDate' => array(
                            'description' => 'The date when the server certificate was uploaded.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'UploadSigningCertificateResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Certificate' => array(
                    'description' => 'Information about the certificate.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'UserName' => array(
                            'description' => 'Name of the user the signing certificate is associated with.',
                            'type' => 'string',
                        ),
                        'CertificateId' => array(
                            'description' => 'The ID for the signing certificate.',
                            'type' => 'string',
                        ),
                        'CertificateBody' => array(
                            'description' => 'The contents of the signing certificate.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of the signing certificate. Active means the key is valid for API calls, while Inactive means it is not.',
                            'type' => 'string',
                        ),
                        'UploadDate' => array(
                            'description' => 'The date when the signing certificate was uploaded.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'GetGroup' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'Users',
            ),
            'ListAccessKeys' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'AccessKeyMetadata',
            ),
            'ListAccountAliases' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'AccountAliases',
            ),
            'ListGroupPolicies' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'PolicyNames',
            ),
            'ListGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'Groups',
            ),
            'ListGroupsForUser' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'Groups',
            ),
            'ListInstanceProfiles' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'InstanceProfiles',
            ),
            'ListInstanceProfilesForRole' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'InstanceProfiles',
            ),
            'ListMFADevices' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'MFADevices',
            ),
            'ListRolePolicies' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'PolicyNames',
            ),
            'ListRoles' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'Roles',
            ),
            'ListServerCertificates' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'ServerCertificateMetadataList',
            ),
            'ListSigningCertificates' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'Certificates',
            ),
            'ListUserPolicies' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'PolicyNames',
            ),
            'ListUsers' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'Users',
            ),
            'ListVirtualMFADevices' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'VirtualMFADevices',
            ),
        ),
    ),
);
