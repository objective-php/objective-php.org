<?php

namespace App\Config;

use ObjectivePHP\Config\Directive\AbstractMultiComplexDirective;
use ObjectivePHP\Config\Directive\IgnoreDefaultInterface;

/**
 * Class ComponentsConfig
 * @package App\Config
 */
class ComponentsConfig extends AbstractMultiComplexDirective implements IgnoreDefaultInterface
{
    const KEY = 'components';

    protected $key = self::KEY;

    /**
     * @config-index "component.id"
     * @var string Correspond au username/reponame
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
     * Minimal version
     *
     * Define here the minimal version documented of your component. The versions bellow will be skipped.
     *
     * @config-attribute
     * @config-example-value 2.0.0
     * @var string
     */
    protected $minVersion;


    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return ComponentsConfig
     */
    public function setHost(string $host): ComponentsConfig
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getMinVersion(): string
    {
        return $this->minVersion;
    }

    /**
     * @param string $minVersion
     * @return ComponentsConfig
     */
    public function setMinVersion(string $minVersion): ComponentsConfig
    {
        $this->minVersion = $minVersion;
        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $reference
     * @return ComponentsConfig
     */
    public function setId(string $reference): ComponentsConfig
    {
        $this->id = $reference;
        return $this;
    }
}