<?php

namespace GuzzleHttp;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @final
 */
class Client implements ClientInterface, \Psr\Http\Client\ClientInterface
{
    use ClientTrait;

    /**
     * @var array Default request options
     */
    private $config;

    /**
     * Clients accept an array of constructor parameters.
     *
     * Here's an example of creating a client using a base_uri and an array of
     * default request options to apply to each request:
     *
     *     $client = new Client([
     *         'base_uri'        => 'http://www.foo.com/1.0/',
     *         'timeout'         => 0,
     *         'allow_redirects' => false,
     *         'proxy'           => '192.168.16.1:10'
     *     ]);
     *
     * Client configuration settings include the following options:
     *
     * - handler: (callable) Function that transfers HTTP requests over the
     *   wire. The function is called with a Psr7\Http\Message\RequestInterface
     *   and array of transfer options, and must return a
     *   GuzzleHttp\Promise\PromiseInterface that is fulfilled with a
     *   Psr7\Http\Message\ResponseInterface on success.
     *   If no handler is provided, a default handler will be created
     *   that enables all of the request options below by attaching all of the
     *   default middleware to the handler.
     * - base_uri: (string|UriInterface) Base URI of the client that is merged
     *   into relative URIs. Can be a string or instance of UriInterface.
     * - **: any request option
     *
     * @param array $config Client configuration settings.
     *
     * @see \GuzzleHttp\RequestOptions for a list of available request options.
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['handler'])) {
            $config['handler'] = HandlerStack::create();
        } elseif (!\is_callable($config['handler'])) {
            throw new InvalidArgumentException('handler must be a callable');
        }

        // Convert the base_uri to a UriInterface
        if (isset($config['base_uri'])) {
            $config['base_uri'] = Psr7\Utils::uriFor($config['base_uri']);
        }

        $this->configureDefaults($config);
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return PromiseInterface|ResponseInterface
     *
     * @deprecated Client::__call will be removed in guzzlehttp/guzzle:8.0.
     */
    public function __call($method, $args)
    {
        if (\count($args) < 1) {
            throw new InvalidArgumentException('Magic request methods require a URI and optional options array');
        }

        $uri = $args[0];
        $opts = $args[1] ?? [];

        return \substr($method, -5) === 'Async'
            ? $this->requestAsync(\substr($method, 0, -5), $uri, $opts)
            : $this->request($method, $uri, $opts);
    }

    /**
     * Asynchronously send an HTTP request.
     *
     * @param array $options Request options to apply to the given
     *                       request and to the transfer. See \GuzzleHttp\RequestOptions.
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        // Merge the base URI into the request URI if needed.
        $options = $this->prepareDefaults($options);

        return $this->transfer(
            $request->withUri($this->buildUri($request->getUri(), $options), $request->hasHeader('Host')),
            $options
        );
    }

    /**
     * Send an HTTP request.
     *
     * @param array $options Request options to apply to the given
     *                       request and to the transfer. See \GuzzleHttp\RequestOptions.
     *
     * @throws GuzzleException
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        $options[RequestOptions::SYNCHRONOUS] = true;
        return $this->sendAsync($request, $options)->wait();
    }

    /**
     * The HttpClient PSR (PSR-18) specify this method.
     *
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $options[RequestOptions::SYNCHRONOUS] = true;
        $options[RequestOptions::ALLOW_REDIRECTS] = false;
        $options[RequestOptions::HTTP_ERRORS] = false;

        return $this->sendAsync($request, $options)->wait();
    }

    /**
     * Create and send an asynchronous HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string              $method  HTTP method
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply. See \GuzzleHttp\RequestOptions.
     */
    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
    {
        $options = $this->prepareDefaults($options);
        // Remove request modifying parameter because it can be done up-front.
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;
        $version = $options['version'] ?? '1.1';
        // Merge the URI into the base URI.
        $uri = $this->buildUri(Psr7\Utils::uriFor($uri), $options);
        if (\is_array($body)) {
            throw $this->invalidBody();
        }
        $request = new Psr7\Request($method, $uri, $headers, $body, $version);
        // Remove the option so that they are not doubly-applied.
        unset($options['headers'], $options['body'], $options['version']);

        return $this->transfer($request, $options);
    }

    /**
     * Create and send an HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string              $method  HTTP method.
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply. See \GuzzleHttp\RequestOptions.
     *
     * @throws GuzzleException
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $options[RequestOptions::SYNCHRONOUS] = true;
        return $this->requestAsync($method, $uri, $options)->wait();
    }

    /**
     * Get a client configuration option.
     *
     * These options include default request options of the client, a "handler"
     * (if utilized by the concrete client), and a "base_uri" if utilized by
     * the concrete client.
     *
     * @param string|null $option The config option to retrieve.
     *
     * @return mixed
     *
     * @deprecated Client::getConfig will be removed in guzzlehttp/guzzle:8.0.
     */
    public function getConfig(?string $option = null)
    {
        return $option === null
            ? $this->config
            : ($this->config[$option] ?? null);
    }

    private function buildUri(UriInterface $uri, array $config): UriInterface
    {
        if (isset($config['base_uri'])) {
            $uri = Psr7\UriResolver::resolve(Psr7\Utils::uriFor($config['base_uri']), $uri);
        }

        if (isset($config['idn_conversion']) && ($config['idn_conversion'] !== false)) {
            $idnOptions = ($config['idn_conversion'] === true) ? \IDNA_DEFAULT : $config['idn_conversion'];
            $uri = Utils::idnUriConvert($uri, $idnOptions);
        }

        return $uri->getScheme() === '' && $uri->getHost() !== '' ? $uri->withScheme('http') : $uri;
    }

    /**
     * Configures the default options for a client.
     */
    private function configureDefaults(array $config): void
    {
        $defaults = [
            'allow_redirects' => RedirectMiddleware::$defaultSettings,
            'http_errors'     => true,
            'decode_content'  => true,
            'verify'          => true,
            'cookies'         => false,
            'idn_conversion'  => false,
        ];

        // Use the standard Linux HTTP_PROXY and HTTPS_PROXY if set.

        // We can only trust the HTTP_PROXY environment variable in a CLI
        // process due to the fact that PHP has no reliable mechanism to
        // get environment variables that start with "HTTP_".
        if (\PHP_SAPI === 'cli' && ($proxy = Utils::getenv('HTTP_PROXY'))) {
            $defaults['proxy']['http'] = $proxy;
        }

        if ($proxy = Utils::getenv('HTTPS_PROXY')) {
            $defaults['proxy']['https'] = $proxy;
        }

        if ($noProxy = Utils::getenv('NO_PROXY')) {
            $cleanedNoProxy = \str_replace(' ', '', $noProxy);
            $defaults['proxy']['no'] = \explode(',', $cleanedNoProxy);
        }

        $this->config = $config + $defaults;

        if (!empty($config['cookies']) && $config['cookies'] === true) {
            $this->config['cookies'] = new CookieJar();
        }

        // Add the default user-agent header.
        if (!isset($this->config['headers'])) {
            $this->config['headers'] = ['User-Agent' => Utils::defaultUserAgent()];
        } else {
            // Add the User-Agent header if one was not already set.
            foreach (\array_keys($this->config['headers']) as $name) {
                if (\strtolower($name) === 'user-agent') {
                    return;
                }
            }
            $this->config['headers']['User-Agent'] = Utils::defaultUserAgent();
        }
    }

    /**
     * Merges default options into the array.
     *
     * @param array $options Options to modify by reference
     */
    private function prepareDefaults(array $options): array
    {
        $defaults = $this->config;

        if (!empty($defaults['headers'])) {
            // Default headers are only added if they are not present.
            $defaults['_conditional'] = $defaults['headers'];
            unset($defaults['headers']);
        }

        // Special handling for headers is required as they are added as
        // conditional headers and as headers passed to a request ctor.
        if (\array_key_exists('headers', $options)) {
            // Allows default headers to be unset.
            if ($options['headers'] === null) {
                $defaults['_conditional'] = [];
                unset($options['headers']);
            } elseif (!\is_array($options['headers'])) {
                throw new InvalidArgumentException('headers must be an array');
            }
        }

        // Shallow merge defaults underneath options.
        $result = $options + $defaults;

        // Remove null values.
        foreach ($result as $k => $v) {
            if ($v === null) {
                unset($result[$k]);
            }
        }

        return $result;
    }

    /**
     * Transfers the given request and applies request options.
     *
     * The URI of the request is not modified and the request options are used
     * as-is without merging in default options.
     *
     * @param array $options See \GuzzleHttp\RequestOptions.
     */
    private function transfer(RequestInterface $request, array $options): PromiseInterface
    {
        $request = $this->applyOptions($request, $options);
        /** @var HandlerStack $handler */
        $handler = $options['handler'];

        try {
            return P\Create::promiseFor($handler($request, $options));
        } catch (\Exception $e) {
            return P\Create::rejectionFor($e);
        }
    }

    /**
     * Applies the array of request options to a request.
     */
    private function applyOptions(RequestInterface $request, array &$options): RequestInterface
    {
        $modify = [
            'set_headers' => [],
        ];

        if (isset($options['headers'])) {
            $modify['set_headers'] = $options['headers'];
            unset($options['headers']);
        }

        if (isset($options['form_params'])) {
            if (isset($options['multipart'])) {
                throw new InvalidArgumentException('You cannot use '
                    . 'form_params and multipart at the same time. Use the '
                    . 'form_params option if you want to send application/'
                    . 'x-www-form-urlencoded requests, and the multipart '
                    . 'option to send multipart/form-data requests.');
            }
            $options['body'] = \http_build_query($options['form_params'], '', '&');
            unset($options['form_params']);
            // Ensure that we don't have the header in different case and set the new value.
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        if (isset($options['multipart'])) {
            $options['body'] = new Psr7\MultipartStream($options['multipart']);
            unset($options['multipart']);
        }

        if (isset($options['json'])) {
            $options['body'] = Utils::jsonEncode($options['json']);
            unset($options['json']);
            // Ensure that we don't have the header in different case and set the new value.
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'application/json';
        }

        if (!empty($options['decode_content'])
            && $options['decode_content'] !== true
        ) {
            // Ensure that we don't have the header in different case and set the new value.
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Accept-Encoding'], $options['_conditional']);
            $modify['set_headers']['Accept-Encoding'] = $options['decode_content'];
        }

        if (isset($options['body'])) {
            if (\is_array($options['body'])) {
                throw $this->invalidBody();
            }
            $modify['body'] = Psr7\Utils::streamFor($options['body']);
            unset($options['body']);
        }

        if (!empty($options['auth']) && \is_array($options['auth'])) {
            $value = $options['auth'];
            $type = isset($value[2]) ? \strtolower($value[2]) : 'basic';
            switch ($type) {
                case 'basic':
                    // Ensure that we don't have the header in different case and set the new value.
                    $modify['set_headers'] = Psr7\Utils::caselessRemove(['Authorization'], $modify['set_headers']);
                    $modify['set_headers']['Authorization'] = 'Basic '
                        . \base64_encode("$value[0]:$value[1]");
                    break;
                case 'digest':
                    // @todo: Do not rely on curl
                    $options['curl'][\CURLOPT_HTTPAUTH] = \CURLAUTH_DIGEST;
                    $options['curl'][\CURLOPT_USERPWD] = "$value[0]:$value[1]";
                    break;
                case 'ntlm':
                    $options['curl'][\CURLOPT_HTTPAUTH] = \CURLAUTH_NTLM;
                    $options['curl'][\CURLOPT_USERPWD] = "$value[0]:$value[1]";
                    break;
            }
        }

        if (isset($options['query'])) {
            $value = $options['query'];
            if (\is_array($value)) {
                $value = \http_build_query($value, '', '&', \PHP_QUERY_RFC3986);
            }
            if (!\is_string($value)) {
                throw new InvalidArgumentException('query must be a string or array');
            }
            $modify['query'] = $value;
            unset($options['query']);
        }

        // Ensure that sink is not an invalid value.
        if (isset($options['sink'])) {
            // TODO: Add more sink validation?
            if (\is_bool($options['sink'])) {
                throw new InvalidArgumentException('sink must not be a boolean');
            }
        }

        $request = Psr7\Utils::modifyRequest($request, $modify);
        if ($request->getBody() instanceof Psr7\MultipartStream) {
            // Use a multipart/form-data POST if a Content-Type is not set.
            // Ensure that we don't have the header in different case and set the new value.
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'multipart/form-data; boundary='
                . $request->getBody()->getBoundary();
        }

        // Merge in conditional headers if they are not present.
        if (isset($options['_conditional'])) {
            // Build up the changes so it's in a single clone of the message.
            $modify = [];
            foreach ($options['_conditional'] as $k => $v) {
                if (!$request->hasHeader($k)) {
                    $modify['set_headers'][$k] = $v;
                }
            }
            $request = Psr7\Utils::modifyRequest($request, $modify);
            // Don't pass this internal value along to middleware/handlers.
            unset($options['_conditional']);
        }

        return $request;
    }

    /**
     * Return an InvalidArgumentException with pre-set message.
     */
    private function invalidBody(): InvalidArgumentException
    {
        return new InvalidArgumentException('Passing in the "body" request '
            . 'option as an array to send a request is not supported. '
            . 'Please use the "form_params" request option to send a '
            . 'application/x-www-form-urlencoded request, or the "multipart" '
            . 'request option to send a multipart/form-data request.');
    }
}
