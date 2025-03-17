<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\DBALQueryBuilderSubscriber;
use PHPUnit\Framework\Attributes\Test;
use Test\Fixture\Entity\Article;
use Test\Fixture\Entity\Shop\Product;
use Test\Fixture\Entity\Shop\Tag;
use Test\Tool\BaseTestCaseORM;

final class DBALQueryBuilderTest extends BaseTestCaseORM
{
    #[Test]
    public function shouldPaginateSimpleDoctrineQuery(): void
    {
        $this->populate();
        $p = $this->getPaginatorInstance(null, null, $this->em->getConnection());

        $qb = new QueryBuilder($this->em->getConnection());
        $qb->select('*')
            ->from('Article', 'a')
        ;
        $view = $p->paginate($qb, 1, 2);

        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(2, $view->getItemNumberPerPage());
        $this->assertEquals(4, $view->getTotalItemCount());

        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]['title']);
        $this->assertEquals('winter', $items[1]['title']);
    }

    #[Test]
    public function shouldStripOrderByForCount(): void
    {
        $this->populate();
        $qb = new QueryBuilder($this->em->getConnection());
        $qb
            ->select('*')
            ->from('Article', 'a')
            ->orderBy('a.title');

        $event = new ItemsEvent(0, 2, $this->mockArgumentAccess());
        $event->target = $qb;

        $this->queryAnalyzer->enable();
        $service = new DBALQueryBuilderSubscriber($this->em->getConnection());
        $service->items($event);
        $this->queryAnalyzer->disable();
        $countQuery = null;
        foreach ($this->queryAnalyzer->getExecutedQueries() as $query) {
            $query = strtolower($query);
            if (str_contains($query, 'count(*)')) {
                $countQuery = $query;
                break;
            }
        }

        $this->assertNotNull($countQuery);
        $this->assertFalse(str_contains($countQuery, 'order by'));
        //Ensure original query builder is not affected by the order by removal.
        $this->assertTrue(str_contains(strtolower($qb->getSQL()), 'order by'));
    }

    #[Test]
    public function shouldWorkWithGroupBy(): void
    {
        $this->populateProducts();
        $qb = new QueryBuilder($this->em->getConnection());
        $qb
            ->select('p.title, count(pt.tag_id) as totalTags')
            ->from('Product', 'p')
            ->join('p', 'product_tag', 'pt', 'pt.product_id = p.id')
            ->groupBy('p.id');

        $event = new ItemsEvent(0, 2, $this->mockArgumentAccess());
        $event->target = $qb;

        $service = new DBALQueryBuilderSubscriber($this->em->getConnection());
        $service->items($event);
        $this->assertCount(2, $event->items);
        $this->assertEquals(4, $event->count);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Article::class, Product::class, Tag::class];
    }

    private function populate(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $summer = new Article;
        $summer->setTitle('summer');

        $winter = new Article;
        $winter->setTitle('winter');

        $autumn = new Article;
        $autumn->setTitle('autumn');

        $spring = new Article;
        $spring->setTitle('spring');

        $em->persist($summer);
        $em->persist($winter);
        $em->persist($autumn);
        $em->persist($spring);
        $em->flush();
    }

    private function populateProducts(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $product = new Product();
        $product->setTitle('Item 1');
        $product->addTag($this->createTag($em, 'A'));
        $product->addTag($this->createTag($em, 'B'));
        $product->addTag($this->createTag($em, 'C'));
        $em->persist($product);

        $product = new Product();
        $product->setTitle('Item 2');
        $product->addTag($this->createTag($em, 'A'));
        $em->persist($product);

        $product = new Product();
        $product->setTitle('Item 3');
        $product->addTag($this->createTag($em, 'A'));
        $product->addTag($this->createTag($em, 'B'));
        $em->persist($product);

        $product = new Product();
        $product->setTitle('Item 4');
        $product->addTag($this->createTag($em, 'A'));
        $product->addTag($this->createTag($em, 'B'));
        $product->addTag($this->createTag($em, 'C'));
        $em->persist($product);
        $em->flush();
        $this->queryAnalyzer->disable();
    }

    private function createTag(EntityManager $em, string $name): Tag
    {
        $tag = new Tag();
        $tag->setName($name);
        $em->persist($tag);

        return $tag;
    }

    private function mockArgumentAccess(): ArgumentAccessInterface
    {
        return new class implements ArgumentAccessInterface {
            public function has(string $name): bool
            {
                return false;
            }

            public function get(string $name): string|int|float|bool|null
            {
                return null;
            }

            public function set(string $name, float|bool|int|string|null $value): void {}
        };
    }
}
