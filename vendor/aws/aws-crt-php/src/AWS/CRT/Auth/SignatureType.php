<?php
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace AWS\CRT\Auth;

class SignatureType {
    const HTTP_REQUEST_HEADERS = 0;
    const HTTP_REQUEST_QUERY_PARAMS = 1;
    const HTTP_REQUEST_CHUNK = 2;
    const HTTP_REQUEST_EVENT = 3;
    const CANONICAL_REQUEST_HEADERS = 4;
    const CANONICAL_REQUEST_QUERY_PARAMS = 5;
}