<?php
namespace Aws;

/**
 * AWS command object.
 */
class Command implements CommandInterface
{
    use HasDataTrait;

    /** @var string */
    private $name;

    /** @var HandlerList */
    private $handlerList;

    /** @var array */
    private $authSchemes;

    /** @var MetricsBuilder */
    private $metricsBuilder;

    /**
     * Accepts an associative array of command options, including:
     *
     * - @http: (array) Associative array of transfer options.
     *
     * @param string      $name           Name of the command
     * @param array       $args           Arguments to pass to the command
     * @param HandlerList $list           Handler list
     */
    public function __construct(
        $name,
        array $args = [],
        ?HandlerList $list = null,
        ?MetricsBuilder $metricsBuilder = null
    )
    {
        $this->name = $name;
        $this->data = $args;
        $this->handlerList = $list ?: new HandlerList();

        if (!isset($this->data['@http'])) {
            $this->data['@http'] = [];
        }
        if (!isset($this->data['@context'])) {
            $this->data['@context'] = [];
        }
        $this->metricsBuilder = $metricsBuilder ?: new MetricsBuilder();
    }

    public function __clone()
    {
        $this->handlerList = clone $this->handlerList;
    }

    public function getName()
    {
        return $this->name;
    }

    public function hasParam($name)
    {
        return array_key_exists($name, $this->data);
    }

    public function getHandlerList()
    {
        return $this->handlerList;
    }

    /**
     * For overriding auth schemes on a per endpoint basis when using
     * EndpointV2 provider. Intended for internal use only.
     *
     * @param array $authSchemes
     *
     * @deprecated In favor of using the @context property bag.
     *             Auth Schemes are now accessible via the `signature_version` key
     *             in a Command's context, if applicable. Auth Schemes set using
     *             This method are no longer consumed.
     *
     * @internal
     */
    public function setAuthSchemes(array $authSchemes)
    {
        trigger_error(__METHOD__ . ' is deprecated.  Auth schemes '
            . 'resolved using the service `auth` trait or via endpoint resolution '
            . 'are now set in the command `@context` property.`'
            , E_USER_WARNING
        );

        $this->authSchemes = $authSchemes;
    }

    /**
     * Get auth schemes added to command as required
     * for endpoint resolution
     *
     * @returns array
     *
     * @deprecated In favor of using the @context property bag.
     *             Auth schemes are now accessible via the `signature_version` key
     *             in a Command's context, if applicable.
     */
    public function getAuthSchemes()
    {
        trigger_error(__METHOD__ . ' is deprecated.  Auth schemes '
        . 'resolved using the service `auth` trait or via endpoint resolution '
        . 'can now be found in the command `@context` property.`'
        , E_USER_WARNING
        );

        return $this->authSchemes ?: [];
    }

    /** @deprecated */
    public function get($name)
    {
        return $this[$name];
    }

    /**
     * Returns the metrics builder instance tied up to this command.
     *
     * @internal
     *
     * @return MetricsBuilder
     */
    public function getMetricsBuilder(): MetricsBuilder
    {
        return $this->metricsBuilder;
    }
}
