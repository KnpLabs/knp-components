<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query\Query;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class QuerySubscriber implements EventSubscriberInterface
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

        if ($event->target instanceof Query) {
            $event->setCustomPaginationParameter('sorted', true);

            if ($this->request->query->has($event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME])) {
                $field = $this->request->query->get($event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]);
                $dir = strtolower($this->request->query->get($event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME])) == 'asc' ? 1 : -1;

                if (isset($event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                    if (!in_array($field, $event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$field}] this field is not in whitelist");
                    }
                }
                static $reflectionProperty;
                if (is_null($reflectionProperty)) {
                    $reflectionClass = new \ReflectionClass(Query::class);
                    $reflectionProperty = $reflectionClass->getProperty('query');
                    $reflectionProperty->setAccessible(true);
                }
                $queryOptions = $reflectionProperty->getValue($event->target);

                // handle multi sort
                $sortFields = explode('+', $field);
                $sortOption = [];
                foreach ($sortFields as $sortField) {
                    $sortOption[$sortField] = $dir;
                }

                $queryOptions['sort'] = $sortOption;
                $reflectionProperty->setValue($event->target, $queryOptions);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1]
        ];
    }
}
