<?php

namespace App\Config;

use ObjectivePHP\Config\Directive\AbstractMultiComplexDirective;
use ObjectivePHP\Config\Directive\IgnoreDefaultInterface;

/**
 * Class AuthsConfig
 * @package App\Config
 */
class AuthsConfig extends AbstractMultiComplexDirective implements IgnoreDefaultInterface
{
    const KEY = 'auths';

    protected $key = self::KEY;

    /**
     * @config-index "auth.id"
     * @var string Correspond au id de l'host
     */
    protected $id;

    /**
     * Component repository host
     *
     * Define here the name of the host where your components is stored. ( github... )
     *
     * @config-attribute
     * @config-example-value github
     * @var string
     */
    protected $host;

    /**
     * Host's client Id
     *
     * Define here the client ID for the OAuth connection
     *
     * @config-attribute
     * @config-example-value 2b90f3380f225b1e
     * @var string
     */
    protected $client_id;

    /**
     * Host's client secret
     *
     * Define here the client Secret for the OAuth connection
     *
     * @config-attribute
     * @config-example-value 22d5b1cc12a0bdda9a3e8d50b1bc08b
     * @var string
     */
    protected $client_secret;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return AuthsConfig
     */
    public function setId(string $id): AuthsConfig
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return AuthsConfig
     */
    public function setHost(string $host): AuthsConfig
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->client_id;
    }

    /**
     * @param string $client_id
     * @return AuthsConfig
     */
    public function setClientId(string $client_id): AuthsConfig
    {
        $this->client_id = $client_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->client_secret;
    }

    /**
     * @param string $client_secret
     * @return AuthsConfig
     */
    public function setClientSecret(string $client_secret): AuthsConfig
    {
        $this->client_secret = $client_secret;
        return $this;
    }
}