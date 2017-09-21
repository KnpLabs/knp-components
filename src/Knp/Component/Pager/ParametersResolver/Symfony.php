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

    public function get(string $parameterName, ?string $defaultValue): ?string
    {
        return $this->request->get($parameterName, $defaultValue);
    }
}