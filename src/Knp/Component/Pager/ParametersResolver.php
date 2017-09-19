<?php

namespace Knp\Component\Pager;

interface ParametersResolver
{
    public function getFieldToSort(string $parameterName, ?string $defaultValue): ?string;

    public function getDirection(string $parameterName, string $defaultValue): string;
}