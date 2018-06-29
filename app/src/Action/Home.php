<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Home
 *
 * @package Showcase\Action
 */
class Home extends AbstractPage
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws \ObjectivePHP\Middleware\Action\PhtmlAction\Exception\PhtmlTemplateNotFoundException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->render([
            'page.title'    => 'Objective PHP',
            'page.subtitle' => 'This project provides developers the documentation associated to the Objective PHP framework.'
        ]);
    }
}
