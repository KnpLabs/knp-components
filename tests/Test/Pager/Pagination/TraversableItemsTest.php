<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\Pagination\SlidingPagination;
use PHPUnit\Framework\Attributes\Test;
use Test\Tool\BaseTestCase;

final class TraversableItemsTest extends BaseTestCase
{
    #[Test]
    public function shouldBeAbleToUseTraversableItems(): void
    {
        $p = $this->getPaginatorInstance();

        $items = new \ArrayObject(\range(1, 23));
        $view = $p->paginate($items, 3, 10);

        $view->renderer = static fn($data) => 'custom';
        $this->assertEquals('custom', (string) $view);

        $items = $view->getItems();
        $this->assertInstanceOf(\ArrayObject::class, $items);
        $i = 21;
        foreach ($view as $item) {
            $this->assertEquals($i++, $item);
        }
    }

    #[Test]
    public function shouldCheckOffsetsOfTraversableItems(): void
    {
        $p = $this->getPaginatorInstance();

        $view = $p->paginate(new \ArrayObject(\range(1, 23)), 3, 10);

        $this->assertTrue(isset($view[0]));
        $this->assertFalse(isset($view[10]));
    }

    #[Test]
    public function shouldCheckOffsetsOfItemsWithoutArrayAccess(): void
    {
        $pagination = new SlidingPagination([]);
        $pagination->setItems(new class(['first', 'second']) implements \IteratorAggregate {
            /**
             * @param array<int|string, mixed> $elements
             */
            public function __construct(private array $elements)
            {
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->elements);
            }
        });

        $this->assertTrue(isset($pagination[1]));
        $this->assertFalse(isset($pagination[2]));
    }
}
