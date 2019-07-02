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
        $arraySubscriber = new ArraySubscriber($requestStack->getCurrentRequest());
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(1, $array[0]['entry']['sortProperty']);

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $requestStack = $this->createRequestStack(['sort' => '[entry][sortProperty]', 'ord' => 'desc']);
        $arraySubscriber = new ArraySubscriber($requestStack->getCurrentRequest());
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
            'sortFunction' => static function (&$target, $sortField, $sortDirection): void {
                usort($target, static function($object1, $object2) use ($sortField, $sortDirection) {
                    if ($object1[$sortField] === $object2[$sortField]) {
                        return 0;
                    }

                    return ($object1[$sortField] === 'hot' ? 1 : -1) * ($sortDirection === 'asc' ? 1 : -1);
                });
            },
        ];

        // test asc sort
        $requestStack = $this->createRequestStack(['sort' => '.name', 'ord' => 'asc']);
        $arraySubscriber = new ArraySubscriber($requestStack->getCurrentRequest());
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('cold', $array[0]['name']);

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $requestStack = $this->createRequestStack(['sort' => '.name', 'ord' => 'desc']);
        $arraySubscriber = new ArraySubscriber($requestStack->getCurrentRequest());
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

        // test asc sort
        $requestStack = $this->createRequestStack(['sort' => '[entry][sortProperty]', 'ord' => 'asc']);
        $arraySubscriber = new ArraySubscriber($requestStack->getCurrentRequest());
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(false, isset($array[0]['entry']['sortProperty']));

        $itemsEvent->unsetCustomPaginationParameter('sorted');

        // test desc sort
        $requestStack = $this->createRequestStack(['sort' => '[entry][sortProperty]', 'ord' => 'desc']);
        $arraySubscriber = new ArraySubscriber($requestStack->getCurrentRequest());
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(2, $array[0]['entry']['sortProperty']);
    }

    /**
     * @test
     * @dataProvider getArrayData
     */
    public function shouldBeJustIgnoredWhenSpecifiedSortPropertyDoesNotExist($array)
    {
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            // @see https://bugs.php.net/bug.php?id=50688
            $this->markTestSkipped('Under PHP7 avoid usort() warning');
        }
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
        $_GET['ord'] = 'desc';
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
                'array' => array(
                    array('sortProperty' => 2),
                    array('sortProperty' => 3),
                    array('sortProperty' => 1),
                 ),
            ),
            'Object case' => array(
                'array' => array(
                    new TestItem(2),
                    new TestItem(3),
                    new TestItem(1),
                ),
            ),
        );
    }
}
