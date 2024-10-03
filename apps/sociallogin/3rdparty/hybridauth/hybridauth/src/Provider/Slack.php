<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2019 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Slack OAuth2 provider adapter.
 */
class Slack extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'identity.basic identity.email identity.avatar';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://slack.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://slack.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://slack.com/api/oauth.access';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://api.slack.com/docs/sign-in-with-slack';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('api/users.identity');

        $data = new Data\Collection($response);

        if (!$data->exists('ok') || !$data->get('ok')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->filter('user')->get('id');
        $userProfile->displayName = $data->filter('user')->get('name');
        $userProfile->email = $data->filter('user')->get('email');
        $userProfile->photoURL = $this->findLargestImage($data);

        return $userProfile;
    }

    /**
     * Returns the url of the image with the highest resolution in the user
     * object.
     *
     * Slack sends multiple image urls with different resolutions. As they make
     * no guarantees which resolutions will be included we have to search all
     * <code>image_*</code> properties for the one with the highest resolution.
     * The resolution is attached to the property name such as
     * <code>image_32</code> or <code>image_192</code>.
     *
     * @param Data\Collection $data response object as returned by
     *     <code>api/users.identity</code>
     *
     * @return string|null the value of the <code>image_*</code> property with
     *     the highest resolution.
     */
    private function findLargestImage(Data\Collection $data)
    {
        $maxSize = 0;
        foreach ($data->filter('user')->properties() as $property) {
            if (preg_match('/^image_(\d+)$/', $property, $matches) === 1) {
                $availableSize = (int)$matches[1];
                if ($maxSize < $availableSize) {
                    $maxSize = $availableSize;
                }
            }
        }
        if ($maxSize > 0) {
            return $data->filter('user')->get('image_' . $maxSize);
        }
        return null;
    }
}
