<?php

use Elastica\SearchableInterface;
use Elastica\ResultSet;
use Elastica\Query;
use Elastica\Query\Term;
use Elastica\Result;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Event\Subscriber\Paginate\ElasticaQuerySubscriber;
use Knp\Component\Pager\ParametersResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Test\Tool\BaseTestCase;

class ElasticaTest extends BaseTestCase
{
    public function testElasticaSubscriber()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new ElasticaQuerySubscriber());
        $dispatcher->addSubscriber(new MockPaginationSubscriber()); // pagination view

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $query = Query::create(new Term(array(
            'name' => 'Fred',
        )));
        $response = $this->getMockBuilder(ResultSet::class)->disableOriginalConstructor()->getMock();
        $response->expects($this->once())
            ->method('getTotalHits')
            ->will($this->returnValue(2));
        $response->expects($this->once())
            ->method('getResults')
            ->will($this->returnValue(array(new Result(array()), new Result(array()))));
        $searchable = $this->getMockBuilder(SearchableInterface::class)->getMock();
        $searchable->expects($this->once())
            ->method('search')
            ->with($query)
            ->will($this->returnValue($response));

        $view = $paginator->paginate(array($searchable, $query), 1, 10);

        $this->assertEquals(0, $query->getParam('from'), 'Query offset set correctly');
        $this->assertEquals(10, $query->getParam('size'), 'Query limit set correctly');
        $this->assertSame($response, $view->getCustomParameter('resultSet'), 'Elastica ResultSet available in Paginator');

        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertCount(2, $view->getItems());
        $this->assertEquals(2, $view->getTotalItemCount());
    }
}
