<?php

namespace Javer\SphinxBundle\Event\Subscriber;

use Javer\SphinxBundle\Sphinx\Query;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PaginateSphinxQuerySubscriber
 *
 * @package Javer\SphinxBundle\Event\Subscriber
 */
class PaginateSphinxQuerySubscriber implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.items' => ['items', 0],
        ];
    }

    /**
     * Process pagination for Query.
     *
     * @param ItemsEvent $event
     */
    public function items(ItemsEvent $event)
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
