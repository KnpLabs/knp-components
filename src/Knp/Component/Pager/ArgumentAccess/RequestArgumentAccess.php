<?php

namespace Knp\Component\Pager\ArgumentAccess;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestArgumentAccess implements ArgumentAccessInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function has(string $name): bool
    {
        return $this->getRequest()->query->has($name);
    }

    public function get(string $name): string|int|float|bool|null
    {
        return $this->getRequest()->query->get($name);
    }

    public function set(string $name, string|int|float|bool|null $value): void
    {
        $this->getRequest()->query->set($name, $value);
    }

    private function getRequest(): Request
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            $request = Request::createFromGlobals();
        }

        return $request;
    }
}
