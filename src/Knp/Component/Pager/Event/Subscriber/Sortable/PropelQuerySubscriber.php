<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function items(ItemsEvent $event): void
    {
        // Check if the result has already been sorted by an other sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        $query = $event->target;
        if ($query instanceof \ModelCriteria) {
            $event->setCustomPaginationParameter('sorted', true);
            $sortField = $event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME];
            $sortDir = $event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME];
            if (null !== $sortField && $this->request->query->has($sortField)) {
                $part = $this->request->query->get($sortField);
                $direction = (null !== $sortDir && $this->request->query->has($sortDir) && strtolower($this->request->query->get($sortDir)) === 'asc')
                                ? 'asc' : 'desc';

                if (isset($event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST])) {
                    if (!in_array($this->request->query->get($sortField), $event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$this->request->query->get($sortField)}] this field is not in allow list.");
                    }
                }

                $query->orderBy($part, $direction);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1],
        ];
    }
}
