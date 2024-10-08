<?php

namespace Knp\Component\Pager\Event;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
final class ItemsEvent extends Event
{
    /**
     * A target being paginated
     */
    public mixed $target = null;

    /**
     * @var array<string, mixed>
     */
    public array $options;

    /**
     * Items result
     */
    public mixed $items = null;

    /**
     * Count result
     */
    public int $count;

    /**
     * @var array<string, mixed>
     */
    private array $customPaginationParams = [];

    public function __construct(
        private readonly int $offset,
        private readonly int $limit,
        private readonly ArgumentAccessInterface $argumentAccess
    ) {
    }

    public function setCustomPaginationParameter(string $name, mixed $value): void
    {
        $this->customPaginationParams[$name] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomPaginationParameters(): array
    {
        return $this->customPaginationParams;
    }

    public function unsetCustomPaginationParameter(string $name): void
    {
        if (isset($this->customPaginationParams[$name])) {
            unset($this->customPaginationParams[$name]);
        }
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getArgumentAccess(): ArgumentAccessInterface
    {
        return $this->argumentAccess;
    }
}
