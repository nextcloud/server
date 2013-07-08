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
    'apiVersion' => '2013-04-15',
    'endpointPrefix' => 'support',
    'serviceFullName' => 'AWS Support',
    'serviceType' => 'json',
    'jsonVersion' => '1.1',
    'targetPrefix' => 'AWSSupport_20130415.',
    'signatureVersion' => 'v4',
    'namespace' => 'Support',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'support.us-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AddCommunicationToCase' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'AddCommunicationToCaseResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This action adds additional customer communication to an AWS Support case. You use the CaseId value to identify the case to which you want to add communication. You can list a set of email addresses to copy on the communication using the CcEmailAddresses value. The CommunicationBody value contains the text of the communication.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.AddCommunicationToCase',
                ),
                'caseId' => array(
                    'description' => 'String that indicates the AWS Support caseID requested or returned in the call. The caseID is an alphanumeric string formatted as shown in this example CaseId: case-12345678910-2013-c4c1d2bf33c5cf47',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'communicationBody' => array(
                    'required' => true,
                    'description' => 'Represents the body of an email communication added to the support case.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 8000,
                ),
                'ccEmailAddresses' => array(
                    'description' => 'Represents any email addresses contained in the CC line of an email added to the support case.',
                    'type' => 'array',
                    'location' => 'json',
                    'maxItems' => 10,
                    'items' => array(
                        'name' => 'CcEmailAddress',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
                array(
                    'reason' => 'Returned when the CaseId requested could not be located.',
                    'class' => 'CaseIdNotFoundException',
                ),
            ),
        ),
        'CreateCase' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateCaseResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a new case in the AWS Support Center. This action is modeled on the behavior of the AWS Support Center Open a new case page. Its parameters require you to specify the following information:',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.CreateCase',
                ),
                'subject' => array(
                    'required' => true,
                    'description' => 'Title of the AWS Support case.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'serviceCode' => array(
                    'required' => true,
                    'description' => 'Code for the AWS service returned by the call to DescribeServices.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'severityCode' => array(
                    'description' => 'Code for the severity level returned by the call to DescribeSeverityLevels.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'categoryCode' => array(
                    'required' => true,
                    'description' => 'Specifies the category of problem for the AWS Support case.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'communicationBody' => array(
                    'required' => true,
                    'description' => 'Parameter that represents the communication body text when you create an AWS Support case by calling CreateCase.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 8000,
                ),
                'ccEmailAddresses' => array(
                    'description' => 'List of email addresses that AWS Support copies on case correspondence.',
                    'type' => 'array',
                    'location' => 'json',
                    'maxItems' => 10,
                    'items' => array(
                        'name' => 'CcEmailAddress',
                        'type' => 'string',
                    ),
                ),
                'language' => array(
                    'description' => 'Specifies the ISO 639-1 code for the language in which AWS provides support. AWS Support currently supports English and Japanese, for which the codes are en and ja, respectively. Language parameters must be passed explicitly for operations that take them.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'issueType' => array(
                    'description' => 'Field passed as a parameter in a CreateCase call.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
                array(
                    'reason' => 'Returned when you have exceeded the case creation limit for an account.',
                    'class' => 'CaseCreationLimitExceededException',
                ),
            ),
        ),
        'DescribeCases' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeCasesResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This action returns a list of cases that you specify by passing one or more CaseIds. In addition, you can filter the cases by date by setting values for the AfterTime and BeforeTime request parameters.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.DescribeCases',
                ),
                'caseIdList' => array(
                    'description' => 'A list of Strings comprising ID numbers for support cases you want returned. The maximum number of cases is 100.',
                    'type' => 'array',
                    'location' => 'json',
                    'maxItems' => 100,
                    'items' => array(
                        'name' => 'CaseId',
                        'type' => 'string',
                    ),
                ),
                'displayId' => array(
                    'description' => 'String that corresponds to the ID value displayed for a case in the AWS Support Center user interface.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'afterTime' => array(
                    'description' => 'Start date for a filtered date search on support case communications.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'beforeTime' => array(
                    'description' => 'End date for a filtered date search on support case communications.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'includeResolvedCases' => array(
                    'description' => 'Boolean that indicates whether or not resolved support cases should be listed in the DescribeCases search.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'nextToken' => array(
                    'description' => 'Defines a resumption point for pagination.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'maxResults' => array(
                    'description' => 'Integer that sets the maximum number of results to return before paginating.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 10,
                    'maximum' => 100,
                ),
                'language' => array(
                    'description' => 'Specifies the ISO 639-1 code for the language in which AWS provides support. AWS Support currently supports English and Japanese, for which the codes are en and ja, respectively. Language parameters must be passed explicitly for operations that take them.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
                array(
                    'reason' => 'Returned when the CaseId requested could not be located.',
                    'class' => 'CaseIdNotFoundException',
                ),
            ),
        ),
        'DescribeCommunications' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeCommunicationsResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This action returns communications regarding the support case. You can use the AfterTime and BeforeTime parameters to filter by date. The CaseId parameter enables you to identify a specific case by its CaseId number.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.DescribeCommunications',
                ),
                'caseId' => array(
                    'required' => true,
                    'description' => 'String that indicates the AWS Support caseID requested or returned in the call. The caseID is an alphanumeric string formatted as shown in this example CaseId: case-12345678910-2013-c4c1d2bf33c5cf47',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'beforeTime' => array(
                    'description' => 'End date for a filtered date search on support case communications.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'afterTime' => array(
                    'description' => 'Start date for a filtered date search on support case communications.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'nextToken' => array(
                    'description' => 'Defines a resumption point for pagination.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'maxResults' => array(
                    'description' => 'Integer that sets the maximum number of results to return before paginating.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 10,
                    'maximum' => 100,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
                array(
                    'reason' => 'Returned when the CaseId requested could not be located.',
                    'class' => 'CaseIdNotFoundException',
                ),
            ),
        ),
        'DescribeServices' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeServicesResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the current list of AWS services and a list of service categories that applies to each one. You then use service names and categories in your CreateCase requests. Each AWS service has its own set of categories.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.DescribeServices',
                ),
                'serviceCodeList' => array(
                    'description' => 'List in JSON format of service codes available for AWS services.',
                    'type' => 'array',
                    'location' => 'json',
                    'maxItems' => 100,
                    'items' => array(
                        'name' => 'ServiceCode',
                        'type' => 'string',
                    ),
                ),
                'language' => array(
                    'description' => 'Specifies the ISO 639-1 code for the language in which AWS provides support. AWS Support currently supports English and Japanese, for which the codes are en and ja, respectively. Language parameters must be passed explicitly for operations that take them.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeSeverityLevels' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeSeverityLevelsResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This action returns the list of severity levels that you can assign to an AWS Support case. The severity level for a case is also a field in the CaseDetails data type included in any CreateCase request.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.DescribeSeverityLevels',
                ),
                'language' => array(
                    'description' => 'Specifies the ISO 639-1 code for the language in which AWS provides support. AWS Support currently supports English and Japanese, for which the codes are en and ja, respectively. Language parameters must be passed explicitly for operations that take them.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeTrustedAdvisorCheckRefreshStatuses' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeTrustedAdvisorCheckRefreshStatusesResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the status of all refresh requests Trusted Advisor checks called using RefreshTrustedAdvisorCheck.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.DescribeTrustedAdvisorCheckRefreshStatuses',
                ),
                'checkIds' => array(
                    'required' => true,
                    'description' => 'List of the CheckId values for the Trusted Advisor checks for which you want to refresh the status. You obtain the CheckId values by calling DescribeTrustedAdvisorChecks.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeTrustedAdvisorCheckResult' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeTrustedAdvisorCheckResultResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This action responds with the results of a Trusted Advisor check. Once you have obtained the list of available Trusted Advisor checks by calling DescribeTrustedAdvisorChecks, you specify the CheckId for the check you want to retrieve from AWS Support.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.DescribeTrustedAdvisorCheckResult',
                ),
                'checkId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'json',
                ),
                'language' => array(
                    'description' => 'Specifies the ISO 639-1 code for the language in which AWS provides support. AWS Support currently supports English and Japanese, for which the codes are en and ja, respectively. Language parameters must be passed explicitly for operations that take them.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeTrustedAdvisorCheckSummaries' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeTrustedAdvisorCheckSummariesResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This action enables you to get the latest summaries for Trusted Advisor checks that you specify in your request. You submit the list of Trusted Advisor checks for which you want summaries. You obtain these CheckIds by submitting a DescribeTrustedAdvisorChecks request.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.DescribeTrustedAdvisorCheckSummaries',
                ),
                'checkIds' => array(
                    'required' => true,
                    'description' => 'Unique identifier for a Trusted Advisor check.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeTrustedAdvisorChecks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeTrustedAdvisorChecksResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This action enables you to get a list of the available Trusted Advisor checks. You must specify a language code. English ("en") and Japanese ("jp") are currently supported. The response contains a list of TrustedAdvisorCheckDescription objects.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.DescribeTrustedAdvisorChecks',
                ),
                'language' => array(
                    'required' => true,
                    'description' => 'Specifies the ISO 639-1 code for the language in which AWS provides support. AWS Support currently supports English and Japanese, for which the codes are en and ja, respectively. Language parameters must be passed explicitly for operations that take them.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'RefreshTrustedAdvisorCheck' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'RefreshTrustedAdvisorCheckResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This action enables you to query the service to request a refresh for a specific Trusted Advisor check. Your request body contains a CheckId for which you are querying. The response body contains a RefreshTrustedAdvisorCheckResult object containing Status and TimeUntilNextRefresh fields.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.RefreshTrustedAdvisorCheck',
                ),
                'checkId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'ResolveCase' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ResolveCaseResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Takes a CaseId and returns the initial state of the case along with the state of the case after the call to ResolveCase completed.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'AWSSupport_20130415.ResolveCase',
                ),
                'caseId' => array(
                    'description' => 'String that indicates the AWS Support caseID requested or returned in the call. The caseID is an alphanumeric string formatted as shown in this example CaseId: case-12345678910-2013-c4c1d2bf33c5cf47',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returns HTTP error 500.',
                    'class' => 'InternalServerErrorException',
                ),
                array(
                    'reason' => 'Returned when the CaseId requested could not be located.',
                    'class' => 'CaseIdNotFoundException',
                ),
            ),
        ),
    ),
    'models' => array(
        'AddCommunicationToCaseResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'result' => array(
                    'description' => 'Returns true if the AddCommunicationToCase succeeds. Returns an error otherwise.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateCaseResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'caseId' => array(
                    'description' => 'String that indicates the AWS Support caseID requested or returned in the call. The caseID is an alphanumeric string formatted as shown in this example CaseId: case-12345678910-2013-c4c1d2bf33c5cf47',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeCasesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'cases' => array(
                    'description' => 'Array of CaseDetails objects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'CaseDetails',
                        'description' => 'JSON-formatted object that contains the metadata for a support case. It is contained the response from a DescribeCases request. This structure contains the following fields:',
                        'type' => 'object',
                        'properties' => array(
                            'caseId' => array(
                                'description' => 'String that indicates the AWS Support caseID requested or returned in the call. The caseID is an alphanumeric string formatted as shown in this example CaseId: case-12345678910-2013-c4c1d2bf33c5cf47',
                                'type' => 'string',
                            ),
                            'displayId' => array(
                                'description' => 'Represents the Id value displayed on pages for the case in AWS Support Center. This is a numeric string.',
                                'type' => 'string',
                            ),
                            'subject' => array(
                                'description' => 'Represents the subject line for a support case in the AWS Support Center user interface.',
                                'type' => 'string',
                            ),
                            'status' => array(
                                'description' => 'Represents the status of a case submitted to AWS Support.',
                                'type' => 'string',
                            ),
                            'serviceCode' => array(
                                'description' => 'Code for the AWS service returned by the call to DescribeServices.',
                                'type' => 'string',
                            ),
                            'categoryCode' => array(
                                'description' => 'Specifies the category of problem for the AWS Support case.',
                                'type' => 'string',
                            ),
                            'severityCode' => array(
                                'description' => 'Code for the severity level returned by the call to DescribeSeverityLevels.',
                                'type' => 'string',
                            ),
                            'submittedBy' => array(
                                'description' => 'Represents the email address of the account that submitted the case to support.',
                                'type' => 'string',
                            ),
                            'timeCreated' => array(
                                'description' => 'Time that the case was case created in AWS Support Center.',
                                'type' => 'string',
                            ),
                            'recentCommunications' => array(
                                'description' => 'Returns up to the five most recent communications between you and AWS Support Center. Includes a nextToken to retrieve the next set of communications.',
                                'type' => 'object',
                                'properties' => array(
                                    'communications' => array(
                                        'description' => 'List of Commmunication objects.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Communication',
                                            'description' => 'Object that exposes the fields used by a communication for an AWS Support case.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'caseId' => array(
                                                    'description' => 'String that indicates the AWS Support caseID requested or returned in the call. The caseID is an alphanumeric string formatted as shown in this example CaseId: case-12345678910-2013-c4c1d2bf33c5cf47',
                                                    'type' => 'string',
                                                ),
                                                'body' => array(
                                                    'description' => 'Contains the text of the the commmunication between the customer and AWS Support.',
                                                    'type' => 'string',
                                                ),
                                                'submittedBy' => array(
                                                    'description' => 'Email address of the account that submitted the AWS Support case.',
                                                    'type' => 'string',
                                                ),
                                                'timeCreated' => array(
                                                    'description' => 'Time the support case was created.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'nextToken' => array(
                                        'description' => 'Defines a resumption point for pagination.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'ccEmailAddresses' => array(
                                'description' => 'List of email addresses that are copied in any communication about the case.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'CcEmailAddress',
                                    'type' => 'string',
                                ),
                            ),
                            'language' => array(
                                'description' => 'Specifies the ISO 639-1 code for the language in which AWS provides support. AWS Support currently supports English and Japanese, for which the codes are en and ja, respectively. Language parameters must be passed explicitly for operations that take them.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'nextToken' => array(
                    'description' => 'Defines a resumption point for pagination.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeCommunicationsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'communications' => array(
                    'description' => 'Contains a list of Communications objects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Communication',
                        'description' => 'Object that exposes the fields used by a communication for an AWS Support case.',
                        'type' => 'object',
                        'properties' => array(
                            'caseId' => array(
                                'description' => 'String that indicates the AWS Support caseID requested or returned in the call. The caseID is an alphanumeric string formatted as shown in this example CaseId: case-12345678910-2013-c4c1d2bf33c5cf47',
                                'type' => 'string',
                            ),
                            'body' => array(
                                'description' => 'Contains the text of the the commmunication between the customer and AWS Support.',
                                'type' => 'string',
                            ),
                            'submittedBy' => array(
                                'description' => 'Email address of the account that submitted the AWS Support case.',
                                'type' => 'string',
                            ),
                            'timeCreated' => array(
                                'description' => 'Time the support case was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'nextToken' => array(
                    'description' => 'Defines a resumption point for pagination.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeServicesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'services' => array(
                    'description' => 'JSON-formatted list of AWS services.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Service',
                        'description' => 'JSON-formatted object that represents an AWS Service returned by the DescribeServices action.',
                        'type' => 'object',
                        'properties' => array(
                            'code' => array(
                                'description' => 'JSON-formatted string that represents a code for an AWS service returned by DescribeServices response. Has a corrsponding name represented by a service.name string.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'JSON-formatted string that represents the friendly name for an AWS service. Has a corresponding code reprsented by a Service.code string.',
                                'type' => 'string',
                            ),
                            'categories' => array(
                                'description' => 'JSON-formatted list of categories that describe the type of support issue a case describes. Categories are strings that represent a category name and a category code. Category names and codes are passed to AWS Support when you call CreateCase.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Category',
                                    'description' => 'JSON-formatted name/value pair that represents the name and category of problem selected from the DescribeServices response for each AWS service.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'code' => array(
                                            'description' => 'Category code for the support case.',
                                            'type' => 'string',
                                        ),
                                        'name' => array(
                                            'description' => 'Category name for the support case.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSeverityLevelsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'severityLevels' => array(
                    'description' => 'List of available severity levels for the support case. Available severity levels are defined by your service level agreement with AWS.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'SeverityLevel',
                        'description' => 'JSON-formatted pair of strings consisting of a code and name that represent a severity level that can be applied to a support case.',
                        'type' => 'object',
                        'properties' => array(
                            'code' => array(
                                'description' => 'String that represents one of four values: "low," "medium," "high," and "urgent". These values correspond to response times returned to the caller in the string SeverityLevel.name.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'Name of severity levels that correspond to the severity level codes.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeTrustedAdvisorCheckRefreshStatusesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'statuses' => array(
                    'description' => 'List of the statuses of the Trusted Advisor checks you\'ve specified for refresh. Status values are:',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'TrustedAdvisorCheckRefreshStatus',
                        'description' => 'Contains the fields that indicate the statuses Trusted Advisor checks for which refreshes have been requested.',
                        'type' => 'object',
                        'properties' => array(
                            'checkId' => array(
                                'description' => 'String that specifies the checkId value of the Trusted Advisor check.',
                                'type' => 'string',
                            ),
                            'status' => array(
                                'description' => 'Indicates the status of the Trusted Advisor check for which a refresh has been requested.',
                                'type' => 'string',
                            ),
                            'millisUntilNextRefreshable' => array(
                                'description' => 'Indicates the time in milliseconds until a call to RefreshTrustedAdvisorCheck can trigger a refresh.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeTrustedAdvisorCheckResultResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'result' => array(
                    'description' => 'Returns a TrustedAdvisorCheckResult object.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'checkId' => array(
                            'description' => 'Unique identifier for a Trusted Advisor check.',
                            'type' => 'string',
                        ),
                        'timestamp' => array(
                            'description' => 'Time at which Trusted Advisor ran the check.',
                            'type' => 'string',
                        ),
                        'status' => array(
                            'description' => 'Overall status of the check. Status values are "ok," "warning," "error," or "not_available."',
                            'type' => 'string',
                        ),
                        'resourcesSummary' => array(
                            'description' => 'JSON-formatted object that lists details about AWS resources that were analyzed in a call to Trusted Advisor DescribeTrustedAdvisorCheckSummaries.',
                            'type' => 'object',
                            'properties' => array(
                                'resourcesProcessed' => array(
                                    'description' => 'Reports the number of AWS resources that were analyzed in your Trusted Advisor check.',
                                    'type' => 'numeric',
                                ),
                                'resourcesFlagged' => array(
                                    'description' => 'Reports the number of AWS resources that were flagged in your Trusted Advisor check.',
                                    'type' => 'numeric',
                                ),
                                'resourcesIgnored' => array(
                                    'description' => 'Indicates the number of resources ignored by Trusted Advisor due to unavailability of information.',
                                    'type' => 'numeric',
                                ),
                                'resourcesSuppressed' => array(
                                    'description' => 'Indicates whether the specified AWS resource has had its participation in Trusted Advisor checks suppressed.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'categorySpecificSummary' => array(
                            'description' => 'Reports summaries for each Trusted Advisor category. Only the category cost optimizing is currently supported. The other categories are security, fault tolerance, and performance.',
                            'type' => 'object',
                            'properties' => array(
                                'costOptimizing' => array(
                                    'description' => 'Corresponds to the Cost Optimizing tab on the AWS Support Center Trusted Advisor page. This field is only available to checks in the Cost Optimizing category.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'estimatedMonthlySavings' => array(
                                            'description' => 'Reports the estimated monthly savings determined by the Trusted Advisor check for your account.',
                                            'type' => 'numeric',
                                        ),
                                        'estimatedPercentMonthlySavings' => array(
                                            'description' => 'Reports the estimated percentage of savings determined for your account by the Trusted Advisor check.',
                                            'type' => 'numeric',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'flaggedResources' => array(
                            'description' => 'List of AWS resources flagged by the Trusted Advisor check.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'TrustedAdvisorResourceDetail',
                                'description' => 'Structure that contains information about the resource to which the Trusted Advisor check pertains.',
                                'type' => 'object',
                                'properties' => array(
                                    'status' => array(
                                        'description' => 'Status code for the resource identified in the Trusted Advisor check.',
                                        'type' => 'string',
                                    ),
                                    'region' => array(
                                        'description' => 'AWS region in which the identified resource is located.',
                                        'type' => 'string',
                                    ),
                                    'resourceId' => array(
                                        'description' => 'Unique identifier for the identified resource.',
                                        'type' => 'string',
                                    ),
                                    'isSuppressed' => array(
                                        'description' => 'Indicates whether the specified AWS resource has had its participation in Trusted Advisor checks suppressed.',
                                        'type' => 'boolean',
                                    ),
                                    'metadata' => array(
                                        'description' => 'Additional information about the identified resource. The exact metadata and its order can be obtained by inspecting the TrustedAdvisorCheckDescription object returned by the call to DescribeTrustedAdvisorChecks.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeTrustedAdvisorCheckSummariesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'summaries' => array(
                    'description' => 'List of TrustedAdvisorCheckSummary objects returned by the DescribeTrustedAdvisorCheckSummaries request.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'TrustedAdvisorCheckSummary',
                        'description' => 'Reports a summary of the Trusted Advisor check. This object contains the following child objects that report summary information about specific checks by category and resource:',
                        'type' => 'object',
                        'properties' => array(
                            'checkId' => array(
                                'description' => 'Unique identifier for a Trusted Advisor check.',
                                'type' => 'string',
                            ),
                            'timestamp' => array(
                                'type' => 'string',
                            ),
                            'status' => array(
                                'description' => 'Overall status of the Trusted Advisor check.',
                                'type' => 'string',
                            ),
                            'hasFlaggedResources' => array(
                                'description' => 'Indicates that the Trusted Advisor check returned flagged resources.',
                                'type' => 'boolean',
                            ),
                            'resourcesSummary' => array(
                                'description' => 'JSON-formatted object that lists details about AWS resources that were analyzed in a call to Trusted Advisor DescribeTrustedAdvisorCheckSummaries.',
                                'type' => 'object',
                                'properties' => array(
                                    'resourcesProcessed' => array(
                                        'description' => 'Reports the number of AWS resources that were analyzed in your Trusted Advisor check.',
                                        'type' => 'numeric',
                                    ),
                                    'resourcesFlagged' => array(
                                        'description' => 'Reports the number of AWS resources that were flagged in your Trusted Advisor check.',
                                        'type' => 'numeric',
                                    ),
                                    'resourcesIgnored' => array(
                                        'description' => 'Indicates the number of resources ignored by Trusted Advisor due to unavailability of information.',
                                        'type' => 'numeric',
                                    ),
                                    'resourcesSuppressed' => array(
                                        'description' => 'Indicates whether the specified AWS resource has had its participation in Trusted Advisor checks suppressed.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'categorySpecificSummary' => array(
                                'description' => 'Reports the results of a Trusted Advisor check by category. Only Cost Optimizing is currently supported.',
                                'type' => 'object',
                                'properties' => array(
                                    'costOptimizing' => array(
                                        'description' => 'Corresponds to the Cost Optimizing tab on the AWS Support Center Trusted Advisor page. This field is only available to checks in the Cost Optimizing category.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'estimatedMonthlySavings' => array(
                                                'description' => 'Reports the estimated monthly savings determined by the Trusted Advisor check for your account.',
                                                'type' => 'numeric',
                                            ),
                                            'estimatedPercentMonthlySavings' => array(
                                                'description' => 'Reports the estimated percentage of savings determined for your account by the Trusted Advisor check.',
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeTrustedAdvisorChecksResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'checks' => array(
                    'description' => 'List of the checks returned by calling DescribeTrustedAdvisorChecks',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'TrustedAdvisorCheckDescription',
                        'description' => 'Description of each check returned by DescribeTrustedAdvisorChecks.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => 'Unique identifier for a specific Trusted Advisor check description.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'Display name for the Trusted Advisor check. Corresponds to the display name for the check in the Trusted Advisor user interface.',
                                'type' => 'string',
                            ),
                            'description' => array(
                                'description' => 'Description of the Trusted Advisor check.',
                                'type' => 'string',
                            ),
                            'category' => array(
                                'description' => 'Category to which the Trusted Advisor check belongs.',
                                'type' => 'string',
                            ),
                            'metadata' => array(
                                'description' => 'List of metadata returned in TrustedAdvisorResourceDetail objects for a Trusted Advisor check.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'RefreshTrustedAdvisorCheckResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'status' => array(
                    'description' => 'Returns the overall status of the RefreshTrustedAdvisorCheck call.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'checkId' => array(
                            'description' => 'String that specifies the checkId value of the Trusted Advisor check.',
                            'type' => 'string',
                        ),
                        'status' => array(
                            'description' => 'Indicates the status of the Trusted Advisor check for which a refresh has been requested.',
                            'type' => 'string',
                        ),
                        'millisUntilNextRefreshable' => array(
                            'description' => 'Indicates the time in milliseconds until a call to RefreshTrustedAdvisorCheck can trigger a refresh.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'ResolveCaseResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'initialCaseStatus' => array(
                    'description' => 'Status of the case when the ResolveCase request was sent.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'finalCaseStatus' => array(
                    'description' => 'Status of the case after the ResolveCase request was processed.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeCases' => array(
                'token_param' => 'nextToken',
                'token_key' => 'nextToken',
                'limit_key' => 'maxResults',
                'result_key' => 'cases',
            ),
            'DescribeCommunications' => array(
                'token_param' => 'nextToken',
                'token_key' => 'nextToken',
                'limit_key' => 'maxResults',
                'result_key' => 'communications',
            ),
            'DescribeServices' => array(
                'result_key' => 'services',
            ),
            'DescribeTrustedAdvisorCheckRefreshStatuses' => array(
                'result_key' => 'statuses',
            ),
            'DescribeTrustedAdvisorCheckSummaries' => array(
                'result_key' => 'summaries',
            ),
            'DescribeSeverityLevels' => array(
                'result_key' => 'severityLevelsList',
            ),
            'DescribeTrustedAdvisorChecks' => array(
                'result_key' => 'checks',
            ),
        ),
    ),
);
