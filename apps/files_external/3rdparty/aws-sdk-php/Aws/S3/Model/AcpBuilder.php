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

namespace Aws\S3\Model;

use Aws\S3\Enum\GranteeType;

/**
 * Builder for creating Access Control Policies
 */
class AcpBuilder
{
    /**
     * @var Grantee The owner for the ACL
     */
    protected $owner;

    /**
     * @var array An array of Grant objects for the ACL
     */
    protected $grants = array();

    /**
     * Static method for chainable instantiation
     *
     * @return static
     */
    public static function newInstance()
    {
        return new static;
    }

    /**
     * Sets the owner to be set on the ACL
     *
     * @param string $id          Owner identifier
     * @param string $displayName Owner display name
     *
     * @return $this
     */
    public function setOwner($id, $displayName = null)
    {
        $this->owner = new Grantee($id, $displayName ?: $id, GranteeType::USER);

        return $this;
    }

    /**
     * Create and store a Grant with a CanonicalUser Grantee for the ACL
     *
     * @param string $permission  Permission for the Grant
     * @param string $id          Grantee identifier
     * @param string $displayName Grantee display name
     *
     * @return $this
     */
    public function addGrantForUser($permission, $id, $displayName = null)
    {
        $grantee = new Grantee($id, $displayName ?: $id, GranteeType::USER);
        $this->addGrant($permission, $grantee);

        return $this;
    }

    /**
     * Create and store a Grant with a AmazonCustomerByEmail Grantee for the ACL
     *
     * @param string $permission Permission for the Grant
     * @param string $email      Grantee email address
     *
     * @return $this
     */
    public function addGrantForEmail($permission, $email)
    {
        $grantee = new Grantee($email, null, GranteeType::EMAIL);
        $this->addGrant($permission, $grantee);

        return $this;
    }

    /**
     * Create and store a Grant with a Group Grantee for the ACL
     *
     * @param string $permission Permission for the Grant
     * @param string $group      Grantee group
     *
     * @return $this
     */
    public function addGrantForGroup($permission, $group)
    {
        $grantee = new Grantee($group, null, GranteeType::GROUP);
        $this->addGrant($permission, $grantee);

        return $this;
    }

    /**
     * Create and store a Grant for the ACL
     *
     * @param string  $permission Permission for the Grant
     * @param Grantee $grantee    The Grantee for the Grant
     *
     * @return $this
     */
    public function addGrant($permission, Grantee $grantee)
    {
        $this->grants[] = new Grant($grantee, $permission);

        return $this;
    }

    /**
     * Builds the ACP and returns it
     *
     * @return Acp
     */
    public function build()
    {
        return new Acp($this->owner, $this->grants);
    }
}
