<?php

namespace App\Action;

use ObjectivePHP\Middleware\Action\PhtmlAction\PhtmlAction;

/**
 * Class AbstractPage
 * @package App\Action
 */
abstract class AbstractPage extends PhtmlAction
{
    /**
     * AbstractPage constructor.
     */
    public function __construct()
    {
        $this->set('navbar-main-menu', [
            [
                'name' => 'Documentation',
                'url' => '/doc',
                'active' => false ? 'active' : '' // @TODO handle documentation pages in Objective PHP
            ],
            [
                'name' => 'Community',
                'url' => '/community',
                'active' => get_class($this) === Community::class ? 'active' : ''
            ]
        ]);
    }
}
