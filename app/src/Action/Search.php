<?php

namespace App\Action;

use ObjectivePHP\Middleware\Action\PhtmlAction\PhtmlAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Home
 *
 * @package Showcase\Action
 */
class Search extends PhtmlAction
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
        return $this->render();
    }
}
