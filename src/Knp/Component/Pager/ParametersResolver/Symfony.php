<?php

namespace Knp\Component\Pager\ParametersResolver;

use Knp\Component\Pager\ParametersResolver;
use Symfony\Component\HttpFoundation\RequestStack;

class Symfony implements ParametersResolver
{
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getFieldToSort(string $parameterName, ?string $defaultValue): ?string
    {
        return $this->request->query->get($parameterName, $defaultValue);
    }

    public function getDirection(string $parameterName, string $defaultValue): string
    {
        return $this->request->get($parameterName, $defaultValue);
    }
}