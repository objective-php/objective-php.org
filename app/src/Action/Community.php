<?php

namespace App\Action;

use ObjectivePHP\Middleware\Action\PhtmlAction\Exception\PhtmlTemplateNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Community
 *
 * @package App\Action
 */
class Community extends AbstractPage
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $this->render([
                'sponsors' => [
                    [
                        'name' => 'OpCoding',
                        'url'  => 'https://opcoding.eu',
                        'logo' => 'https://via.placeholder.com/50x50',
                    ],
                    [
                        'name' => 'Lorem',
                        'url'  => '#',
                        'logo' => 'https://via.placeholder.com/50x50',
                    ],
                    [
                        'name' => 'Ipsum',
                        'url'  => '#',
                        'logo' => 'https://via.placeholder.com/50x50',
                    ]
                ]
            ]);
        } catch (PhtmlTemplateNotFoundException $e) {
        }
    }
}
