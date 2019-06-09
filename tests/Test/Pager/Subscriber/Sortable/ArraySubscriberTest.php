<?php

namespace Test\Pager\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\ArraySubscriber;
use Knp\Component\Pager\Fixtures\TestItem;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\PaginatorInterface;

class ArraySubscriberTest extends BaseTestCase
{
    /**
     * @test
     */
    public function shouldSort()
    {
        $array = array(
            array('entry' => array('sortProperty' => 2)),
            array('entry' => array('sortProperty' => 3)),
            array('entry' => array('sortProperty' => 1)),
        );

        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = array(PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort', PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord');

        $arraySubscriber = new ArraySubscriber();

        // test asc sort
        $_GET = array('sort' => '[entry][sortProperty]', 'ord' => 'asc');
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(1, $array[0]['entry']['sortProperty']);

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $_GET ['ord'] = 'desc';
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(3, $array[0]['entry']['sortProperty']);
    }

    /**
     * @test
     */
    public function shouldSortWithCustomCallback()
    {
        $array = array(
            array('name' => 'hot'),
            array('name' => 'cold'),
            array('name' => 'hot'),
        );

        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = array(
            PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort',
            PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord',
            'sortFunction' => function (&$target, $sortField, $sortDirection) {
                usort($target, function($object1, $object2) use ($sortField, $sortDirection) {
                    if ($object1[$sortField] === $object2[$sortField]) {
                        return 0;
                    }

                    return ($object1[$sortField] === 'hot' ? 1 : -1) * ($sortDirection === 'asc' ? 1 : -1);
                });
            },
        );

        $arraySubscriber = new ArraySubscriber();

        // test asc sort
        $_GET = array('sort' => '.name', 'ord' => 'asc');
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('cold', $array[0]['name']);


        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $_GET['ord'] = 'desc';
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('hot', $array[0]['name']);

    }

    /**
     * @test
     */
    public function shouldSortEvenWhenTheSortPropertyIsNotAccessible()
    {
        $array = array(
            array('entry' => array('sortProperty' => 2)),
            array('entry' => array()),
            array('entry' => array('sortProperty' => 1)),
        );

        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = array(PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort', PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord');

        $arraySubscriber = new ArraySubscriber();

        // test asc sort
        $_GET = array('sort' => '[entry][sortProperty]', 'ord' => 'asc');
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(false, isset($array[0]['entry']['sortProperty']));

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $_GET ['ord'] = 'desc';
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(2, $array[0]['entry']['sortProperty']);
    }

    /**
     * @test
     * @dataProvider getArrayData
     */
    public function shouldBeJustIgnoredWhenSpecifiedSortPropertyDoesNotExist($array)
    {
        $sameSortOrderData = array(
            $array[0],
            $array[1],
            $array[2],
        );
        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = array(PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort', PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord');

        $arraySubscriber = new ArraySubscriber();

        // test asc sort
        $_GET = array('sort' => 'notExistProperty', 'ord' => 'asc');
        $arraySubscriber->items($itemsEvent);
        $this->assertSame($sameSortOrderData, $array);

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $_GET ['ord'] = 'desc';
        $arraySubscriber->items($itemsEvent);
        $this->assertSame($sameSortOrderData, $array);
    }

    /**
     * @return array
     */
    public function getArrayData()
    {
        return array(
            'Associative array case' => array(
                'array' => [
                    ['sortProperty' => 2],
                    ['sortProperty' => 3],
                    ['sortProperty' => 1],
                ],
            ),
            'Object case' => array(
                'array' => [
                    new TestItem(2),
                    new TestItem(3),
                    new TestItem(1),
                ],
            ),
        );
    }
}
