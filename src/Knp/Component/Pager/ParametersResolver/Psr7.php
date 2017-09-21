<?php

namespace Knp\Component\Pager\ParametersResolver;

use Knp\Component\Pager\ParametersResolver;
use Psr\Http\Message\ServerRequestInterface;

class Psr7 implements ParametersResolver
{
    private $serverRequest;

    public function __construct(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
    }

    public function get(string $parameterName, ?string $defaultValue): ?string
    {
        return $this->serverRequest->getQueryParams()[$parameterName] ?? $defaultValue;
    }
}