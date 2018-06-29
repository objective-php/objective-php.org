<?php

namespace App\Config;

use ObjectivePHP\Config\Directive\AbstractMultiScalarDirective;

/**
 * Class Paths
 *
 * @config-example-reference "tmp"
 * @config-example-value "app/tmp"
 * @package App\Config
 */
class PathsConfig extends AbstractMultiScalarDirective
{
    const KEY = 'paths';

    protected $key = self::KEY;


    /**
     * If its a dir and doesn t already exist, try to create it
     *
     * @return mixed|string
     * @throws \RuntimeException
     */
    public function getValue()
    {
        if (!parent::getValue()) {
            return '';
        }

        $path = getcwd() . '/' . parent::getValue();

        if (basename($path) !== '..' && strpos(basename($path), '.')) {
            return $path;
        }

        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        return $path . '/';
    }
}
