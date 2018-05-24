<?php

namespace App\Config;

use ObjectivePHP\Config\Directive\AbstractMultiScalarDirective;

/**
 * Class Paths
 * @package App\Config
 */
class PathsConfig extends AbstractMultiScalarDirective
{
    const KEY = 'paths';

    protected $key = self::KEY;

    /**
     * @config-index "path.id"
     * @var string Correspond au nom du path
     */
    protected $id;

    public function getValue()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/../' . parent::getValue();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }
}