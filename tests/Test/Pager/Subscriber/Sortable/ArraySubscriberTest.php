<?php

namespace Test\Pager\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\ArraySubscriber;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\PaginatorInterface;

class TestItem
{
    private $sortProperty;

    /**
     * TestItem constructor.
     *
     * @param $sortProperty
     */
    public function __construct($sortProperty)
    {
        $this->sortProperty = $sortProperty;
    }

    /**
     * @return mixed
     */
    public function getSortProperty()
    {
        return $this->sortProperty;
    }
}

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
        $_GET = array('sort' => '[entry][sortProperty]', 'ord' => 'asc');

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
    public function classShouldBeSort()
    {
        /** @var TestItem[] $array */
        $array = array(
            new TestItem(2),
            new TestItem(3),
            new TestItem(1),
        );

        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = array(PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort', PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord');
        $_GET = array('sort' => 'sortProperty', 'ord' => 'asc');

        $this->assertEquals(2, $array[0]->getSortProperty());
        $arraySubscriber = new ArraySubscriber();
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(1, $array[0]->getSortProperty());
        $_GET ['ord'] = 'desc';
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(3, $array[0]->getSortProperty());
    }

    /**
     * @test
     */
    public function shouldRunSortFunctionWithoutException()
    {
        /** @var TestItem[] $array */
        $array = array(
            new TestItem(2),
            new TestItem(3),
            new TestItem(1),
        );

        $itemsEvent = new ItemsEvent(0, 10);
        $itemsEvent->target = &$array;
        $itemsEvent->options = array(PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort', PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord');
        $_GET = array('sort' => 'noExistingProperty', 'ord' => 'asc');

        $this->assertEquals(2, $array[0]->getSortProperty());
        $arraySubscriber = new ArraySubscriber();
        $arraySubscriber->items($itemsEvent);

        // Just count. because this sort result of relative order is indeterminate.
        $this->assertCount(3, $array);
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
                    if ($object1[$sortField] == $object2[$sortField]) {
                        return 0;
                    }

                    return ($object1[$sortField] == 'hot' ? 1 : -1) * ($sortDirection == 'asc' ? 1 : -1);
                });
            },
        );
        $_GET = array('sort' => '.name', 'ord' => 'asc');

        $this->assertEquals('hot', $array[0]['name']);
        $arraySubscriber = new ArraySubscriber();
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('cold', $array[0]['name']);
        $_GET['ord'] = 'desc';
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('hot', $array[0]['name']);

    }
}
