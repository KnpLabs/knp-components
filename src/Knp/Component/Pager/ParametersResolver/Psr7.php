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

    public function getFieldToSort(string $parameterName, ?string $defaultValue): ?string
    {
        $fields = $this->serverRequest->getQueryParams()[$parameterName] ?? null;
        if ($fields === null) {
            return $defaultValue;
        }

        return $fields;
    }

    public function getDirection(string $parameterName, string $defaultValue): string
    {
        $direction = $this->serverRequest->getQueryParams()[$parameterName] ?? null;
        if ($direction === null) {
            return $defaultValue;
        }

        return $direction;
    }
}