<?php

namespace App;

/**
 * The AppNamespace namespace should be changed to whatever fit your project
 *
 * Many modern IDEs offer powerful refactoring features that should make this
 * renaming operation painless
 */

use App\Config\AuthsConfig;
use App\Config\ComponentsConfig;
use App\Config\PathsConfig;
use ObjectivePHP\Cli\Application\AbstractCliApplication;

/**
 * Class Application
 *
 * @package Showcase
 */
class CliApplication extends AbstractCliApplication
{
    public function init()
    {
        $this->getConfig()->registerDirective(new PathsConfig());
        $this->getConfig()->registerDirective(new ComponentsConfig());
        $this->getConfig()->registerDirective(new AuthsConfig());
    }
}
