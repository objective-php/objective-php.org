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
use ObjectivePHP\Application\AbstractHttpApplication;
use ObjectivePHP\Middleware\Action\PhtmlAction\ExceptionHandler\DefaultExceptionRenderer;
use ObjectivePHP\Middleware\Action\PhtmlAction\PhtmlActionPackage;

/**
 * Class Application
 *
 * @package Showcase
 */
class Application extends AbstractHttpApplication
{
    public function init()
    {
        $this->getExceptionHandlers()
            ->registerMiddleware(new DefaultExceptionRenderer());

        // register Phtml action package
        $this->registerPackage(new PhtmlActionPackage());

        $this->getConfig()->registerDirective(new PathsConfig());
        $this->getConfig()->registerDirective(new AuthsConfig());
    }
}
