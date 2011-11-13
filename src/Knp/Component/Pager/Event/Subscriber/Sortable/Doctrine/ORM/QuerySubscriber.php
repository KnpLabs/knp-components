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
            $alias = $event->getOption('alias');
            if (isset($_GET[$alias.'sort'])) {
                $dir = strtolower($_GET[$alias.'direction']) === 'asc' ? 'asc' : 'desc';
                $parts = explode('.', $_GET[$alias.'sort']);
                if (count($parts) != 2) {
                    throw new \UnexpectedValueException('Invalid sort key came by request, should be example "entityAlias.field" like: "article.title"');
                }

                $whiteList = $event->getOption('whitelist');
                if ($whiteList && !in_array($_GET[$alias.'sort'], $whiteList)) {
                    throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$alias.'sort']}] this field is not in whitelist");
                }

                $query
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_ALIAS, current($parts))
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_DIRECTION, $dir)
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_FIELD, end($parts))
                ;
                QueryHelper::addCustomTreeWalker($query, 'Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\Query\OrderByWalker');
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'items' => array('items', 1)
        );
    }
}