<?php

declare(strict_types=1);

namespace OpenStack\Common\Error;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class responsible for building meaningful exceptions. For HTTP problems, it produces a {@see HttpError}
 * exception, and supplies a error message with reasonable defaults. For user input problems, it produces a
 * {@see UserInputError} exception. For both, the problem is described, a potential solution is offered and
 * a link to further information is included.
 */
class Builder
{
    /**
     * The default domain to use for further link documentation.
     *
     * @var string
     */
    private $docDomain = 'http://docs.php-opencloud.com/en/latest/';

    /**
     * The HTTP client required to validate the further links.
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
    }

    /**
     * Internal method used when outputting headers in the error description.
     *
     * @param $name
     */
    private function header(string $name): string
    {
        return sprintf("%s\n%s\n", $name, str_repeat('~', strlen($name)));
    }

    /**
     * Before outputting custom links, it is validated to ensure that the user is not
     * directed off to a broken link. If a 404 is detected, it is hidden.
     *
     * @param $link The proposed link
     */
    private function linkIsValid(string $link): bool
    {
        $link = $this->docDomain.$link;

        try {
            return $this->client->request('HEAD', $link)->getStatusCode() < 400;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function str(MessageInterface $message): string
    {
        if ($message instanceof RequestInterface) {
            $msg = trim($message->getMethod().' '
                    .$message->getRequestTarget())
                .' HTTP/'.$message->getProtocolVersion();
            if (!$message->hasHeader('host')) {
                $msg .= "\r\nHost: ".$message->getUri()->getHost();
            }
        } elseif ($message instanceof ResponseInterface) {
            $msg = 'HTTP/'.$message->getProtocolVersion().' '
                .$message->getStatusCode().' '
                .$message->getReasonPhrase();
        }

        foreach ($message->getHeaders() as $name => $values) {
            $msg .= "\r\n{$name}: ".implode(', ', $values);
        }

        if (ini_get('memory_limit') < 0 || $message->getBody()->getSize() < ini_get('memory_limit')) {
            $msg .= "\r\n\r\n".$message->getBody();
        }

        return $msg;
    }

    /**
     * Helper method responsible for constructing and returning {@see BadResponseError} exceptions.
     *
     * @param RequestInterface  $request  The faulty request
     * @param ResponseInterface $response The error-filled response
     */
    public function httpError(RequestInterface $request, ResponseInterface $response): BadResponseError
    {
        $message = $this->header('HTTP Error');

        $message .= sprintf(
            "The remote server returned a \"%d %s\" error for the following transaction:\n\n",
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        $message .= $this->header('Request');
        $message .= trim($this->str($request)).PHP_EOL.PHP_EOL;

        $message .= $this->header('Response');
        $message .= trim($this->str($response)).PHP_EOL.PHP_EOL;

        $message .= $this->header('Further information');
        $message .= $this->getStatusCodeMessage($response->getStatusCode());

        $message .= 'Visit http://docs.php-opencloud.com/en/latest/http-codes for more information about debugging '
            .'HTTP status codes, or file a support issue on https://github.com/php-opencloud/openstack/issues.';

        $e = new BadResponseError($message);
        $e->setRequest($request);
        $e->setResponse($response);

        return $e;
    }

    private function getStatusCodeMessage(int $statusCode): string
    {
        $errors = [
            400 => 'Please ensure that your input values are valid and well-formed. ',
            401 => 'Please ensure that your authentication credentials are valid. ',
            404 => "Please ensure that the resource you're trying to access actually exists. ",
            500 => 'Please try this operation again once you know the remote server is operational. ',
        ];

        return isset($errors[$statusCode]) ? $errors[$statusCode] : '';
    }

    /**
     * Helper method responsible for constructing and returning {@see UserInputError} exceptions.
     *
     * @param string      $expectedType The type that was expected from the user
     * @param mixed       $userValue    The incorrect value the user actually provided
     * @param string|null $furtherLink  a link to further information if necessary (optional)
     */
    public function userInputError(string $expectedType, $userValue, string $furtherLink = null): UserInputError
    {
        $message = $this->header('User Input Error');

        $message .= sprintf(
            "%s was expected, but the following value was passed in:\n\n%s\n",
            $expectedType,
            print_r($userValue, true)
        );

        $message .= 'Please ensure that the value adheres to the expectation above. ';

        if ($furtherLink && $this->linkIsValid($furtherLink)) {
            $message .= sprintf('Visit %s for more information about input arguments. ', $this->docDomain.$furtherLink);
        }

        $message .= 'If you run into trouble, please open a support issue on https://github.com/php-opencloud/openstack/issues.';

        return new UserInputError($message);
    }
}
