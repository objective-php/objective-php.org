<?php

namespace Project\Action\Api;

use ObjectivePHP\Middleware\Action\RestAction\AbstractRestAction;
use ObjectivePHP\Middleware\Action\RestAction\Serializer\JsonSerializer;

/**
 * Class BuildApi
 *
 * @package Project\Action\DocApi
 */
class BuildApi extends AbstractRestAction
{
    public function __construct()
    {
        $this->registerSerializer('application/json', new JsonSerializer());
        $this->registerEndpoint('1.0', BuildApiEndpointV1::class);
    }
}
