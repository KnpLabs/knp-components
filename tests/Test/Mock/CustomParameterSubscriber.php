<?php

namespace Test\Mock;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\ArraySubscriber;

final class CustomParameterSubscriber extends ArraySubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 10],
        ];
    }

    public function items(ItemsEvent $event): void
    {
        $event->setCustomPaginationParameter('test', 'val');
        parent::items($event);
    }
}
