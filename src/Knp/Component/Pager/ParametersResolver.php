<?php

namespace Knp\Component\Pager;

interface ParametersResolver
{
    public function get(string $parameterName, ?string $defaultValue): ?string;
}