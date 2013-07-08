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

namespace Aws\Sts\Exception;

/**
 * The non-AWS identity provider (IDP) that was asked to verify the incoming identity token rejected the identity claim. This might be because the claim is invalid, has expired, or has been explicitly revoked by the user. The error message contains details about the response from the non-AWS identity provider.
 */
class IDPRejectedClaimException extends StsException {}
