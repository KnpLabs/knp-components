<?php

use Knp\Component\Pager\ParametersResolver;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;

class TraversableItemsTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldBeAbleToUseTraversableItems()
    {
        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $items = new \ArrayObject(range(1, 23));
        $view = $paginator->paginate($items, 3, 10);

        $view->renderer = function($data) {
            return 'custom';
        };
        $this->assertEquals('custom', (string)$view);

        $items = $view->getItems();
        $this->assertInstanceOf(\ArrayObject::class, $items);
        $i = 21;
        foreach ($view as $item) {
            $this->assertEquals($i++, $item);
        }
    }
}
