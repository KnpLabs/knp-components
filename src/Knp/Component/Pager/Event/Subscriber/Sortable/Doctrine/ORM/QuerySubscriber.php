<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\Query\OrderByWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Doctrine\ORM\Query;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        $query = $event->getTarget();
        if ($query instanceof Query) {
            if (isset($_GET[$event->getAlias().'sort'])) {
                $dir = strtolower($_GET[$event->getAlias().'direction']) == 'asc' ? 1 : -1;
                $parts = explode('.', $_GET[$event->getAlias().'sort']);
                if (count($parts) != 2) {
                    throw new UnexpectedValueException('Invalid sort key came by request, should be example: "article.title"');
                }

                $query
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_ALIAS, current($parts))
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_DIRECTION, $dir)
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_FIELD, end($parts))
                ;
                QueryHelper::addCustomTreeWalker($query, self::TREE_WALKER_ORDER_BY);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'items' => array('items', 0)
        );
    }
}