<?php

namespace App\Action\Api;

use ObjectivePHP\Middleware\Action\RestAction\AbstractRestAction;
use ObjectivePHP\Middleware\Action\RestAction\Serializer\JsonSerializer;

/**
 * Class BuildApi
 *
 * @package App\Action\DocApi
 */
class BuildApi extends AbstractRestAction
{
    public function __construct()
    {
        $this->registerSerializer('application/json', new JsonSerializer());
        $this->registerEndpoint('1.0', BuildApiEndpointV1::class);
    }
}
