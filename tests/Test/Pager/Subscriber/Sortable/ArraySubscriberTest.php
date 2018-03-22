<?php

namespace Test\Pager\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\ArraySubscriber;
use Knp\Component\Pager\ParametersResolver;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\PaginatorInterface;

class ArraySubscriberTest extends BaseTestCase
{
    /**
     * @test
     */
    public function shouldSort()
    {
        $array = [
            ['entry' => ['sortProperty' => 2]],
            ['entry' => ['sortProperty' => 3]],
            ['entry' => ['sortProperty' => 1]],
        ];

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['sort', null, '[entry][sortProperty]'],
                ['ord', 'asc', 'asc'],
            ]));

        $itemsEvent = new ItemsEvent(0, 10, $parametersResolver);
        $itemsEvent->target = &$array;
        $itemsEvent->options = [PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort', PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord'];

        $this->assertEquals(2, $array[0]['entry']['sortProperty']);
        $arraySubscriber = new ArraySubscriber();

        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(1, $array[0]['entry']['sortProperty']);

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['sort', null, '[entry][sortProperty]'],
                ['ord', 'asc', 'desc'],
            ]));

        $itemsEvent = new ItemsEvent(0, 10, $parametersResolver);
        $itemsEvent->target = &$array;
        $itemsEvent->options = ['sortFieldParameterName' => 'sort', 'sortDirectionParameterName' => 'ord'];

        $arraySubscriber->items($itemsEvent);
        $this->assertEquals(3, $array[0]['entry']['sortProperty']);
    }

    /**
     * @test
     */
    public function shouldSortWithCustomCallback()
    {
        $array = [
            ['name' => 'hot'],
            ['name' => 'cold'],
            ['name' => 'hot'],
        ];

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['sort', null, '.name'],
                ['ord', 'asc', 'asc'],
            ]));

        $itemsEvent = new ItemsEvent(0, 10, $parametersResolver);
        $itemsEvent->target = &$array;
        $itemsEvent->options = [
            PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'sort',
            PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'ord',
            'sortFunction' => function (&$target, $sortField, $sortDirection) {
                usort($target, function($object1, $object2) use ($sortField, $sortDirection) {
                    if ($object2[$sortField] === $object1[$sortField]) {
                        return 0;
                    }

                    return ($object1[$sortField] === 'hot' ? 1 : -1) * ($sortDirection === 'asc' ? 1 : -1);
                });
            },
        ];

        $this->assertEquals('hot', $array[0]['name']);
        $arraySubscriber = new ArraySubscriber();
        $arraySubscriber->items($itemsEvent);
        $this->assertEquals('cold', $array[0]['name']);
    }
}
