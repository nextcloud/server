<?php
/*!
* This simple example illustrate how to authenticate users with GitHub.
*
* Most other providers work pretty much the same.
*/

/**
 * Step 0: Start PHP session
 *
 * Normally this step is not required as Hybridauth will attempt to start the session for you, however
 * in some cases it might be better to call session_start() at top of script to avoid cookie-based sessions
 * issues.
 *
 * See: http://php.net/manual/en/function.session-start.php#refsect1-function.session-start-notes
 *      http://stackoverflow.com/a/8028987
 */

session_start();

/**
 * Step 1: Require the Hybridauth Library
 *
 * Should be as simple as including Composer's autoloader.
 */

include 'vendor/autoload.php';

/**
 * Step 2: Configuring Your Application
 *
 * If you're already familiar with the process, you can skip the explanation below.
 *
 * To get started with GitHub authentication, you need to create a new GitHub application.
 *
 * First, navigate to https://github.com/settings/developers then click the Register
 * new application button at the top right of that page and fill in any required fields
 * such as the application name, description and website.
 *
 * Set the Authorization callback URL to https://path/to/hybridauth/examples/example_01.php.
 * Understandably, you need to replace 'path/to/hybridauth' with the real path to this script.
 *
 * Note that Hybridauth provides an utility function that can generate the current page url for you
 * and can be used for the callback. Example: 'callback' => Hybridauth\HttpClient\Util::getCurrentUrl()
 *
 * After configuring your GitHub application, simple replace 'your-app-id' and 'your-app-secret'
 * with your application credentials (Client ID and Client Secret).
 *
 * Providers who uses OAuth 2.0 protocol (i.g., GitHub, Facebook, Google, etc.) may need
 * an Authorization scope as additional parameter. Authorization scopes are strings that
 * enable access to particular resources, such as user data.
 *
 * https://developer.github.com/v3/oauth/
 * https://developer.github.com/v3/oauth/#scopes
 */

$config = [
    'callback' => 'https://path/to/hybridauth/examples/example_01.php', // or Hybridauth\HttpClient\Util::getCurrentUrl()

    'keys' => ['id' => 'your-app-id', 'secret' => 'your-app-secret'], // Your Github application credentials

    /* optional : set scope
        'scope' => 'user:email', */

    /* optional : set debug mode
        'debug_mode' => true,
        // Path to file writeable by the web server. Required if 'debug_mode' is not false
        'debug_file' => __FILE__ . '.log', */

    /* optional : customize Curl settings
        // for more information on curl, refer to: http://www.php.net/manual/fr/function.curl-setopt.php
        'curl_options' => [
            // setting custom certificates
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CAINFO => '/path/to/your/certificate.crt',

            // set a valid proxy ip address
            CURLOPT_PROXY => '*.*.*.*:*',

            // set a custom user agent
            CURLOPT_USERAGENT => ''
        ] */
];

/**
 * Step 3: Instantiate Github Adapter
 *
 * This example instantiates a GitHub adapter using the array $config we just built.
 */

$github = new Hybridauth\Provider\GitHub($config);

/**
 * Step 4: Authenticating Users
 *
 * When invoked, `authenticate()` will redirect users to GitHub login page where they
 * will be asked to grant access to your application. If they do, GitHub will redirect
 * the users back to Authorization callback URL (i.e., this script).
 *
 * Note that GitHub and few other providers will ask their users for authorisation
 * only once.
 */

$github->authenticate();

/**
 * Step 5: Retrieve Users Profiles
 *
 * Calling getUserProfile returns an instance of class Hybridauth\User\Profile which contain the
 * connected user's profile in simple and standardized structure across all the social APIs supported
 * by Hybridauth.
 */

$userProfile = $github->getUserProfile();

echo 'Hi ' . $userProfile->displayName;

/**
 * Bonus: Access GitHub API
 *
 * Now that the user is authenticated with Gihub, and depending on the authorization given to your
 * application, you should be able to query the said API on behalf of the user.
 *
 * As an example we list the authenticated user's public gists.
 */

$apiResponse = $github->apiRequest('gists');

/**
 * Step 6: Disconnect the adapter
 *
 * This will erase the current user authentication data from session, and any further
 * attempt to communicate with Github API will result on an authorisation exception.
 */

$github->disconnect();

/**
 * Final note: Catching Exceptions
 *
 * Hybridauth use exceptions extensively and it's important that these exceptions
 * be properly caught/handled in your code.
 *
 * Below is a basic example of how to catch exceptions.
 *
 * Note that on the previous step we disconnected from the API; meaning Hybridauth
 * has erased the oauth access token used to sign http requests from the current
 * session, thus, any new request we now make will now throw an exception.
 *
 * It's important that you don't show Hybridauth exception's messages to the end user as
 * they may include sensitive data, and that you use your own error messages instead.
 */

try {
    $github->getUserProfile();
}

/**
 * Catch Curl Errors
 *
 * This kind of error may happen in case of:
 *     - Internet or Network issues.
 *     - Your server configuration is not setup correctly.
 *
 * The full list of curl errors that may happen can be found at http://curl.haxx.se/libcurl/c/libcurl-errors.html
 */
catch (Hybridauth\Exception\HttpClientFailureException $e) {
    echo 'Curl text error message : ' . $github->getHttpClient()->getResponseClientError();
}

/**
 * Catch API Requests Errors
 *
 * This usually happens when requesting a:
 *     - Wrong URI or a mal-formatted http request.
 *     - Protected resource without providing a valid access token.
 */
catch (Hybridauth\Exception\HttpRequestFailedException $e) {
    echo 'Raw API Response: ' . $github->getHttpClient()->getResponseBody();
}

/**
 * Base PHP's exception that catches everything [else]
 */
catch (\Exception $e) {
    echo 'Oops! We ran into an unknown issue: ' . $e->getMessage();
}
