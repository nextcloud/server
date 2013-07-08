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
    'apiVersion' => '2010-12-01',
    'endpointPrefix' => 'email',
    'serviceFullName' => 'Amazon Simple Email Service',
    'serviceAbbreviation' => 'Amazon SES',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'Ses',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'email.us-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'DeleteIdentity' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified identity (email address or domain) from the list of verified identities.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteIdentity',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Identity' => array(
                    'required' => true,
                    'description' => 'The identity to be removed from the list of identities for the AWS Account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteVerifiedEmailAddress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified email address from the list of verified addresses.',
            'deprecated' => true,
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteVerifiedEmailAddress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EmailAddress' => array(
                    'required' => true,
                    'description' => 'An email address to be removed from the list of verified addresses.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'GetIdentityDkimAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetIdentityDkimAttributesResponse',
            'responseType' => 'model',
            'summary' => 'Returns the current status of Easy DKIM signing for an entity. For domain name identities, this action also returns the DKIM tokens that are required for Easy DKIM signing, and whether Amazon SES has successfully verified that these tokens have been published.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetIdentityDkimAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Identities' => array(
                    'required' => true,
                    'description' => 'A list of one or more verified identities - email addresses, domains, or both.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Identities.member',
                    'items' => array(
                        'name' => 'Identity',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'GetIdentityNotificationAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetIdentityNotificationAttributesResponse',
            'responseType' => 'model',
            'summary' => 'Given a list of verified identities (email addresses and/or domains), returns a structure describing identity notification attributes.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetIdentityNotificationAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Identities' => array(
                    'required' => true,
                    'description' => 'A list of one or more identities.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Identities.member',
                    'items' => array(
                        'name' => 'Identity',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'GetIdentityVerificationAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetIdentityVerificationAttributesResponse',
            'responseType' => 'model',
            'summary' => 'Given a list of identities (email addresses and/or domains), returns the verification status and (for domain identities) the verification token for each identity.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetIdentityVerificationAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Identities' => array(
                    'required' => true,
                    'description' => 'A list of identities.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Identities.member',
                    'items' => array(
                        'name' => 'Identity',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'GetSendQuota' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetSendQuotaResponse',
            'responseType' => 'model',
            'summary' => 'Returns the user\'s current sending limits.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetSendQuota',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
            ),
        ),
        'GetSendStatistics' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetSendStatisticsResponse',
            'responseType' => 'model',
            'summary' => 'Returns the user\'s sending statistics. The result is a list of data points, representing the last two weeks of sending activity.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetSendStatistics',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
            ),
        ),
        'ListIdentities' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListIdentitiesResponse',
            'responseType' => 'model',
            'summary' => 'Returns a list containing all of the identities (email addresses and domains) for a specific AWS Account, regardless of verification status.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListIdentities',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'IdentityType' => array(
                    'description' => 'The type of the identities to list. Possible values are "EmailAddress" and "Domain". If this parameter is omitted, then all identities will be listed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'EmailAddress',
                        'Domain',
                    ),
                ),
                'NextToken' => array(
                    'description' => 'The token to use for pagination.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of identities per page. Possible values are 1-100 inclusive.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ListVerifiedEmailAddresses' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListVerifiedEmailAddressesResponse',
            'responseType' => 'model',
            'summary' => 'Returns a list containing all of the email addresses that have been verified.',
            'deprecated' => true,
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListVerifiedEmailAddresses',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
            ),
        ),
        'SendEmail' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SendEmailResponse',
            'responseType' => 'model',
            'summary' => 'Composes an email message based on input data, and then immediately queues the message for sending.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SendEmail',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Source' => array(
                    'required' => true,
                    'description' => 'The identity\'s email address.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Destination' => array(
                    'required' => true,
                    'description' => 'The destination for this email, composed of To:, CC:, and BCC: fields.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'ToAddresses' => array(
                            'description' => 'The To: field(s) of the message.',
                            'type' => 'array',
                            'sentAs' => 'ToAddresses.member',
                            'items' => array(
                                'name' => 'Address',
                                'type' => 'string',
                            ),
                        ),
                        'CcAddresses' => array(
                            'description' => 'The CC: field(s) of the message.',
                            'type' => 'array',
                            'sentAs' => 'CcAddresses.member',
                            'items' => array(
                                'name' => 'Address',
                                'type' => 'string',
                            ),
                        ),
                        'BccAddresses' => array(
                            'description' => 'The BCC: field(s) of the message.',
                            'type' => 'array',
                            'sentAs' => 'BccAddresses.member',
                            'items' => array(
                                'name' => 'Address',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Message' => array(
                    'required' => true,
                    'description' => 'The message to be sent.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Subject' => array(
                            'required' => true,
                            'description' => 'The subject of the message: A short summary of the content, which will appear in the recipient\'s inbox.',
                            'type' => 'object',
                            'properties' => array(
                                'Data' => array(
                                    'required' => true,
                                    'description' => 'The textual data of the content.',
                                    'type' => 'string',
                                ),
                                'Charset' => array(
                                    'description' => 'The character set of the content.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Body' => array(
                            'required' => true,
                            'description' => 'The message body.',
                            'type' => 'object',
                            'properties' => array(
                                'Text' => array(
                                    'description' => 'The content of the message, in text format. Use this for text-based email clients, or clients on high-latency networks (such as mobile devices).',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Data' => array(
                                            'required' => true,
                                            'description' => 'The textual data of the content.',
                                            'type' => 'string',
                                        ),
                                        'Charset' => array(
                                            'description' => 'The character set of the content.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                                'Html' => array(
                                    'description' => 'The content of the message, in HTML format. Use this for email clients that can process HTML. You can include clickable links, formatted text, and much more in an HTML message.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Data' => array(
                                            'required' => true,
                                            'description' => 'The textual data of the content.',
                                            'type' => 'string',
                                        ),
                                        'Charset' => array(
                                            'description' => 'The character set of the content.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'ReplyToAddresses' => array(
                    'description' => 'The reply-to email address(es) for the message. If the recipient replies to the message, each reply-to address will receive the reply.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ReplyToAddresses.member',
                    'items' => array(
                        'name' => 'Address',
                        'type' => 'string',
                    ),
                ),
                'ReturnPath' => array(
                    'description' => 'The email address to which bounce notifications are to be forwarded. If the message cannot be delivered to the recipient, then an error message will be returned from the recipient\'s ISP; this message will then be forwarded to the email address specified by the ReturnPath parameter.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that the action failed, and the message could not be sent. Check the error stack for more information about what caused the error.',
                    'class' => 'MessageRejectedException',
                ),
            ),
        ),
        'SendRawEmail' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SendRawEmailResponse',
            'responseType' => 'model',
            'summary' => 'Sends an email message, with header and content specified by the client. The SendRawEmail action is useful for sending multipart MIME emails. The raw text of the message must comply with Internet email standards; otherwise, the message cannot be sent.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SendRawEmail',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Source' => array(
                    'description' => 'The identity\'s email address.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Destinations' => array(
                    'description' => 'A list of destinations for the message.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Destinations.member',
                    'items' => array(
                        'name' => 'Address',
                        'type' => 'string',
                    ),
                ),
                'RawMessage' => array(
                    'required' => true,
                    'description' => 'The raw text of the message. The client is responsible for ensuring the following:',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Data' => array(
                            'required' => true,
                            'description' => 'The raw data of the message. The client must ensure that the message format complies with Internet email standards regarding email header fields, MIME types, MIME encoding, and base64 encoding (if necessary).',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that the action failed, and the message could not be sent. Check the error stack for more information about what caused the error.',
                    'class' => 'MessageRejectedException',
                ),
            ),
        ),
        'SetIdentityDkimEnabled' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Enables or disables Easy DKIM signing of email sent from an identity:',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetIdentityDkimEnabled',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Identity' => array(
                    'required' => true,
                    'description' => 'The identity for which DKIM signing should be enabled or disabled.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DkimEnabled' => array(
                    'required' => true,
                    'description' => 'Sets whether DKIM signing is enabled for an identity. Set to true to enable DKIM signing for this identity; false to disable it.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'SetIdentityFeedbackForwardingEnabled' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Given an identity (email address or domain), enables or disables whether Amazon SES forwards feedback notifications as email. Feedback forwarding may only be disabled when both complaint and bounce topics are set.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetIdentityFeedbackForwardingEnabled',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Identity' => array(
                    'required' => true,
                    'description' => 'The identity for which to set feedback notification forwarding. Examples: user@example.com, example.com.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ForwardingEnabled' => array(
                    'required' => true,
                    'description' => 'Sets whether Amazon SES will forward feedback notifications as email. true specifies that Amazon SES will forward feedback notifications as email, in addition to any Amazon SNS topic publishing otherwise specified. false specifies that Amazon SES will publish feedback notifications only through Amazon SNS. This value can only be set to false when topics are specified for both Bounce and Complaint topic types.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'SetIdentityNotificationTopic' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Given an identity (email address or domain), sets the Amazon SNS topic to which Amazon SES will publish bounce and complaint notifications for emails sent with that identity as the Source. Publishing to topics may only be disabled when feedback forwarding is enabled.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetIdentityNotificationTopic',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Identity' => array(
                    'required' => true,
                    'description' => 'The identity for which the topic will be set. Examples: user@example.com, example.com.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NotificationType' => array(
                    'required' => true,
                    'description' => 'The type of feedback notifications that will be published to the specified topic.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Bounce',
                        'Complaint',
                    ),
                ),
                'SnsTopic' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the Amazon Simple Notification Service (Amazon SNS) topic. If the parameter is ommited from the request or a null value is passed, the topic is cleared and publishing is disabled.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'VerifyDomainDkim' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'VerifyDomainDkimResponse',
            'responseType' => 'model',
            'summary' => 'Returns a set of DKIM tokens for a domain. DKIM tokens are character strings that represent your domain\'s identity. Using these tokens, you will need to create DNS CNAME records that point to DKIM public keys hosted by Amazon SES. Amazon Web Services will eventually detect that you have updated your DNS records; this detection process may take up to 72 hours. Upon successful detection, Amazon SES will be able to DKIM-sign email originating from that domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'VerifyDomainDkim',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain to be verified for Easy DKIM signing.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'VerifyDomainIdentity' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'VerifyDomainIdentityResponse',
            'responseType' => 'model',
            'summary' => 'Verifies a domain.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'VerifyDomainIdentity',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'Domain' => array(
                    'required' => true,
                    'description' => 'The domain to be verified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'VerifyEmailAddress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Verifies an email address. This action causes a confirmation email message to be sent to the specified address.',
            'deprecated' => true,
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'VerifyEmailAddress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EmailAddress' => array(
                    'required' => true,
                    'description' => 'The email address to be verified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'VerifyEmailIdentity' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Verifies an email address. This action causes a confirmation email message to be sent to the specified address.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'VerifyEmailIdentity',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EmailAddress' => array(
                    'required' => true,
                    'description' => 'The email address to be verified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'GetIdentityDkimAttributesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DkimAttributes' => array(
                    'description' => 'The DKIM attributes for an email address or a domain.',
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlMap' => array(
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
                                'description' => 'Represents the DKIM attributes of a verified email address or a domain.',
                                'type' => 'object',
                                'properties' => array(
                                    'DkimEnabled' => array(
                                        'description' => 'True if DKIM signing is enabled for email sent from the identity; false otherwise.',
                                        'type' => 'boolean',
                                    ),
                                    'DkimVerificationStatus' => array(
                                        'description' => 'Describes whether Amazon SES has successfully verified the DKIM DNS records (tokens) published in the domain name\'s DNS. (This only applies to domain identities, not email address identities.)',
                                        'type' => 'string',
                                    ),
                                    'DkimTokens' => array(
                                        'description' => 'A set of DNS records (tokens) that must be published in the domain name\'s DNS for DKIM verification to complete, and which must remain published in order for DKIM signing to succeed. The tokens are CNAME DNS records that point to DKIM public keys hosted by Amazon SES. (This only applies to domain entities, not email address identities.)',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'VerificationToken',
                                            'type' => 'string',
                                            'sentAs' => 'member',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'additionalProperties' => false,
                ),
            ),
        ),
        'GetIdentityNotificationAttributesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NotificationAttributes' => array(
                    'description' => 'A map of Identity to IdentityNotificationAttributes.',
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlMap' => array(
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
                                'description' => 'Represents the notification attributes of an identity, including whether a bounce or complaint topic are set, and whether feedback forwarding is enabled.',
                                'type' => 'object',
                                'properties' => array(
                                    'BounceTopic' => array(
                                        'description' => 'The Amazon Resource Name (ARN) of the Amazon Simple Notification Service (SNS) topic where Amazon SES will publish bounce notifications.',
                                        'type' => 'string',
                                    ),
                                    'ComplaintTopic' => array(
                                        'description' => 'The Amazon Resource Name (ARN) of the Amazon Simple Notification Service (SNS) topic where Amazon SES will publish complaint notifications.',
                                        'type' => 'string',
                                    ),
                                    'ForwardingEnabled' => array(
                                        'description' => 'Describes whether Amazon SES will forward feedback as email. true indicates that Amazon SES will forward feedback as email, while false indicates that feedback will be published only to the specified Bounce and Complaint topics.',
                                        'type' => 'boolean',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'additionalProperties' => false,
                ),
            ),
        ),
        'GetIdentityVerificationAttributesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VerificationAttributes' => array(
                    'description' => 'A map of Identities to IdentityVerificationAttributes objects.',
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlMap' => array(
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
                                'description' => 'Represents the verification attributes of a single identity.',
                                'type' => 'object',
                                'properties' => array(
                                    'VerificationStatus' => array(
                                        'description' => 'The verification status of the identity: "Pending", "Success", "Failed", or "TemporaryFailure".',
                                        'type' => 'string',
                                    ),
                                    'VerificationToken' => array(
                                        'description' => 'The verification token for a domain identity. Null for email address identities.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'additionalProperties' => false,
                ),
            ),
        ),
        'GetSendQuotaResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Max24HourSend' => array(
                    'description' => 'The maximum number of emails the user is allowed to send in a 24-hour interval.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'MaxSendRate' => array(
                    'description' => 'The maximum number of emails the user is allowed to send per second.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'SentLast24Hours' => array(
                    'description' => 'The number of emails sent during the previous 24 hours.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
            ),
        ),
        'GetSendStatisticsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SendDataPoints' => array(
                    'description' => 'A list of data points, each of which represents 15 minutes of activity.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'SendDataPoint',
                        'description' => 'Represents sending statistics data. Each SendDataPoint contains statistics for a 15-minute period of sending activity.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Timestamp' => array(
                                'description' => 'Time of the data point.',
                                'type' => 'string',
                            ),
                            'DeliveryAttempts' => array(
                                'description' => 'Number of emails that have been enqueued for sending.',
                                'type' => 'numeric',
                            ),
                            'Bounces' => array(
                                'description' => 'Number of emails that have bounced.',
                                'type' => 'numeric',
                            ),
                            'Complaints' => array(
                                'description' => 'Number of unwanted emails that were rejected by recipients.',
                                'type' => 'numeric',
                            ),
                            'Rejects' => array(
                                'description' => 'Number of emails rejected by Amazon SES.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ListIdentitiesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Identities' => array(
                    'description' => 'A list of identities.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Identity',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'NextToken' => array(
                    'description' => 'The token used for pagination.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListVerifiedEmailAddressesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VerifiedEmailAddresses' => array(
                    'description' => 'A list of email addresses that have been verified.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Address',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'SendEmailResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'MessageId' => array(
                    'description' => 'The unique message identifier returned from the SendEmail action.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'SendRawEmailResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'MessageId' => array(
                    'description' => 'The unique message identifier returned from the SendRawEmail action.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'VerifyDomainDkimResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DkimTokens' => array(
                    'description' => 'A set of DNS records (tokens) that must be published in the domain name\'s DNS for DKIM verification to complete, and which must remain published in order for DKIM signing to succeed. The tokens are CNAME DNS records pointing to DKIM public keys hosted by Amazon SES.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'VerificationToken',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'VerifyDomainIdentityResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VerificationToken' => array(
                    'description' => 'A TXT record that must be placed in the DNS settings for the domain, in order to complete domain verification.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'ListIdentities' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxItems',
                'result_key' => 'Identities',
            ),
            'ListVerifiedEmailAddresses' => array(
                'result_key' => 'VerifiedEmailAddresses',
            ),
        ),
    ),
    'waiters' => array(
        '__default__' => array(
            'interval' => 3,
            'max_attempts' => 20,
        ),
        'IdentityExists' => array(
            'operation' => 'GetIdentityVerificationAttributes',
            'success.type' => 'output',
            'success.path' => 'VerificationAttributes/*/VerificationStatus',
            'success.value' => true,
        ),
    ),
);
