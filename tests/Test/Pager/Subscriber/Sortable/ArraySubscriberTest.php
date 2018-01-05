<?php

namespace Test\Pager\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\ArraySubscriber;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\PaginatorInterface;

class ArraySubscriberTest extends BaseTestCase
{
    /**
     * @test
     */
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
        $_GET = ['sort' => '[entry][sortProperty]', 'ord' => 'asc'];

        $this->assertEquals(2, $array[0]['entry']['sortProperty']);
        $arraySubscriber = new ArraySubscriber();
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(1, $array[0]['entry']['sortProperty']);
        $_GET ['ord'] = 'desc';
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(3, $array[0]['entry']['sortProperty']);
    }

    /**
     * @test
     */
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
            'sortFunction' => function (&$target, $sortField, $sortDirection): void {
                usort($target, function($object1, $object2) use ($sortField, $sortDirection) {
                    if ($object1[$sortField] == $object2[$sortField]) {
                        return 0;
                    }

                    return ($object1[$sortField] == 'hot' ? 1 : -1) * ($sortDirection == 'asc' ? 1 : -1);
                });
            },
        ];
        $_GET = ['sort' => '.name', 'ord' => 'asc'];

        $this->assertEquals('hot', $array[0]['name']);
        $arraySubscriber = new ArraySubscriber();
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('cold', $array[0]['name']);
        $_GET['ord'] = 'desc';
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('hot', $array[0]['name']);

    }
}
