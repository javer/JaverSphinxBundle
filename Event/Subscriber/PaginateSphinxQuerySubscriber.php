<?php

namespace Javer\SphinxBundle\Event\Subscriber;

use Javer\SphinxBundle\Sphinx\Query;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaginateSphinxQuerySubscriber implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 0],
        ];
    }

    public function items(ItemsEvent $event): void
    {
        if ($event->target instanceof Query) {
            $query = $event->target;
            $query->offset($event->getOffset());
            $query->limit($event->getLimit());

            $event->items = $query->getResults();
            $event->count = $query->getTotalFound();

            $event->stopPropagation();
        }
    }
}
