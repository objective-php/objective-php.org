<?php

namespace App\Config;

use ObjectivePHP\Config\Directive\AbstractMultiComplexDirective;
use ObjectivePHP\Config\Directive\IgnoreDefaultInterface;

/**
 * Class AuthsConfig
 *
 * @package App\Config
 */
class AuthsConfig extends AbstractMultiComplexDirective implements IgnoreDefaultInterface
{
    const KEY = 'auths';

    protected $key = self::KEY;

    /**
     * @config-example-reference "auth.id"
     * @var string Correspond au id de l'host
     */
    protected $id;

    /**
     * Host's client Id
     *
     * Define here the client ID for the OAuth connection
     *
     * @config-attribute
     * @config-example-value 2b90f3380f225b
     * @var string
     */
    protected $clientId;

    /**
     * Host's client key
     *
     * Define here the client key (or secret) for the OAuth connection
     *
     * @config-attribute
     * @config-example-value 22d5b1cc12a0bdda9a3e8d50b1bc0
     * @var string
     */
    protected $clientKey;

    /**
     * Host's client admin key
     *
     * Define here the client admin key  for the admin  OAuth connection
     *
     * @config-attribute
     * @config-example-value 22d5b1cda9a3e8d50b1bc0
     * @var string
     */
    protected $adminKey;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
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
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     *
     * @return AuthsConfig
     */
    public function setClientId(string $clientId): AuthsConfig
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    /**
     * @param string $clientKey
     *
     * @return AuthsConfig
     */
    public function setClientKey(string $clientKey): AuthsConfig
    {
        $this->clientKey = $clientKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdminKey(): string
    {
        return $this->clientKey;
    }

    /**
     * @param string $adminKey
     *
     * @return AuthsConfig
     */
    public function setAdminKey(string $adminKey): AuthsConfig
    {
        $this->adminKey = $adminKey;

        return $this;
    }
}
