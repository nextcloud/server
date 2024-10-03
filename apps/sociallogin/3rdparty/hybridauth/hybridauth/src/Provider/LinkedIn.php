<?php
/*!
 * Hybridauth
 * https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
 *  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
 */

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

/**
 * LinkedIn OAuth2 provider adapter.
 */
class LinkedIn extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'r_liteprofile r_emailaddress';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.linkedin.com/v2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.linkedin.com/oauth/v2/authorization';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://docs.microsoft.com/en-us/linkedin/shared/authentication/authentication';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $fields = [
            'id',
            'firstName',
            'lastName',
            'profilePicture(displayImage~:playableStreams)',
        ];


        $response = $this->apiRequest('me', 'GET', ['projection' => '(' . implode(',', $fields) . ')']);
        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        // Handle localized names.
        $userProfile->firstName = $data
            ->filter('firstName')
            ->filter('localized')
            ->get($this->getPreferredLocale($data, 'firstName'));

        $userProfile->lastName = $data
            ->filter('lastName')
            ->filter('localized')
            ->get($this->getPreferredLocale($data, 'lastName'));

        $userProfile->identifier = $data->get('id');
        $userProfile->email = $this->getUserEmail();
        $userProfile->emailVerified = $userProfile->email;
        $userProfile->displayName = trim($userProfile->firstName . ' ' . $userProfile->lastName);

        $photo_elements = $data
            ->filter('profilePicture')
            ->filter('displayImage~')
            ->get('elements');
        $userProfile->photoURL = $this->getUserPhotoUrl($photo_elements);

        return $userProfile;
    }

    /**
     * Returns a user photo.
     *
     * @param array $elements
     *   List of file identifiers related to this artifact.
     *
     * @return string
     *   The user photo URL.
     *
     * @see https://docs.microsoft.com/en-us/linkedin/shared/references/v2/profile/profile-picture
     */
    public function getUserPhotoUrl($elements)
    {
        if (is_array($elements)) {
            // Get the largest picture from the list which is the last one.
            $element = end($elements);
            if (!empty($element->identifiers)) {
                return reset($element->identifiers)->identifier;
            }
        }

        return null;
    }

    /**
     * Returns an email address of user.
     *
     * @return string
     *   The user email address.
     *
     * @throws \Exception
     */
    public function getUserEmail()
    {
        $response = $this->apiRequest('emailAddress', 'GET', [
            'q' => 'members',
            'projection' => '(elements*(handle~))',
        ]);
        $data = new Data\Collection($response);

        foreach ($data->filter('elements')->toArray() as $element) {
            $item = new Data\Collection($element);

            if ($email = $item->filter('handle~')->get('emailAddress')) {
                return $email;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/share-on-linkedin
     * @throws \Exception
     */
    public function setUserStatus($status, $userID = null)
    {
        if (strpos($this->scope, 'w_member_social') === false) {
            throw new \Exception('Set user status requires w_member_social permission!');
        }

        if (is_string($status)) {
            $status = [
                'author' => 'urn:li:person:' . $userID,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $status,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];
        }


        $headers = [
            'Content-Type' => 'application/json',
            'x-li-format' => 'json',
            'X-Restli-Protocol-Version' => '2.0.0',
        ];

        $response = $this->apiRequest("ugcPosts", 'POST', $status, $headers);

        return $response;
    }

    /**
     * Returns a preferred locale for given field.
     *
     * @param \Hybridauth\Data\Collection $data
     *   A data to check.
     * @param string $field_name
     *   A field name to perform.
     *
     * @return string
     *   A field locale.
     */
    protected function getPreferredLocale($data, $field_name)
    {
        $locale = $data->filter($field_name)->filter('preferredLocale');
        if ($locale) {
            return $locale->get('language') . '_' . $locale->get('country');
        }

        return 'en_US';
    }
}
