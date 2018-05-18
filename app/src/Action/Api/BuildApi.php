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
        // Add application/json serializer
        $this->registerSerializer('application/json', new JsonSerializer());

        // Register versioned endpoints
        $this->registerEndpoint('1.0', BuildApiEndpointV1::class);
//        $this->registerEndpoint('2.0.0', ExamplesEndpointV2::class);
    }
}
