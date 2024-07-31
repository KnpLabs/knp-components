<?php

namespace Test\Pager\Subscriber\Sortable;

use Knp\Component\Pager\ArgumentAccess\RequestArgumentAccess;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\ArraySubscriber;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Test\Fixture\TestItem;
use Test\Tool\BaseTestCase;

final class ArraySubscriberTest extends BaseTestCase
{
    #[Test]
    public function shouldSort(): void
    {
        $array = [
            ['entry' => ['sortProperty' => 2]],
            ['entry' => ['sortProperty' => 3]],
            ['entry' => ['sortProperty' => 1]],
        ];

        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = [PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort', PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord'];

        // test asc sort
        $requestStack = $this->createRequestStack(['sort' => '[entry][sortProperty]', 'ord' => 'asc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $arraySubscriber = new ArraySubscriber($accessor);
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(1, $array[0]['entry']['sortProperty']);

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $requestStack = $this->createRequestStack(['sort' => '[entry][sortProperty]', 'ord' => 'desc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $arraySubscriber = new ArraySubscriber($accessor);
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(3, $array[0]['entry']['sortProperty']);
    }

    #[Test]
    public function shouldSortWithCustomCallback(): void
    {
        $array = [
            ['name' => 'hot'],
            ['name' => 'cold'],
            ['name' => 'hot'],
        ];

        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = [
            PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort',
            PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord',
            'sortFunction' => static function (&$target, $sortField, $sortDirection): void {
                \usort($target, static function ($object1, $object2) use ($sortField, $sortDirection) {
                    if ($object1[$sortField] === $object2[$sortField]) {
                        return 0;
                    }

                    return ($object1[$sortField] === 'hot' ? 1 : -1) * ($sortDirection === 'asc' ? 1 : -1);
                });
            },
        ];

        // test asc sort
        $requestStack = $this->createRequestStack(['sort' => '.name', 'ord' => 'asc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $arraySubscriber = new ArraySubscriber($accessor);
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('cold', $array[0]['name']);

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $requestStack = $this->createRequestStack(['sort' => '.name', 'ord' => 'desc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $arraySubscriber = new ArraySubscriber($accessor);
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('hot', $array[0]['name']);
    }

    #[Test]
    public function shouldSortEvenWhenTheSortPropertyIsNotAccessible(): void
    {
        $array = [
            ['entry' => ['sortProperty' => 2]],
            ['entry' => []],
            ['entry' => ['sortProperty' => 1]],
        ];

        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = [PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort', PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord'];

        // test asc sort
        $requestStack = $this->createRequestStack(['sort' => '[entry][sortProperty]', 'ord' => 'asc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $arraySubscriber = new ArraySubscriber($accessor);
        $arraySubscriber->items($itemsEvent);
        $this->assertFalse(isset($array[0]['entry']['sortProperty']));

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $requestStack = $this->createRequestStack(['sort' => '[entry][sortProperty]', 'ord' => 'desc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $arraySubscriber = new ArraySubscriber($accessor);
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(2, $array[0]['entry']['sortProperty']);
    }

    #[Test]
    #[DataProvider('getItemsData')]
    public function shouldBeKeptTheOrderWhenSortPropertyDoesNotExist(array $items): void
    {
        $sameSortOrderItems = [
            $items[0],
            $items[1],
            $items[2],
        ];
        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$items;
        $itemsEvent->options = [
            PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort',
            PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord',
        ];

        // test asc sort
        $requestStack = $this->createRequestStack(['sort' => 'notExistProperty', 'ord' => 'asc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $arraySubscriber = new ArraySubscriber($accessor);
        $arraySubscriber->items($itemsEvent);
        $this->assertSame($sameSortOrderItems, $items);

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $requestStack = $this->createRequestStack(['sort' => 'notExistProperty', 'ord' => 'desc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $arraySubscriber = new ArraySubscriber($accessor);
        $arraySubscriber->items($itemsEvent);
        $this->assertSame($sameSortOrderItems, $items);
    }

    /**
     * @return array<string, array<string, array<int, array<string, int>|TestItem>>>
     */
    public static function getItemsData(): array
    {
        return [
            'Associative array case' => [
                'items' => [
                    ['sortProperty' => 2],
                    ['sortProperty' => 3],
                    ['sortProperty' => 1],
                ],
            ],
            'Object case' => [
                'items' => [
                    new TestItem(2),
                    new TestItem(3),
                    new TestItem(1),
                ],
            ],
        ];
    }
}
