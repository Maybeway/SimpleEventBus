<?php declare(strict_types=1);

namespace Maybeway\SimpleEventBus;

use Maybeway\Domain\AggregateHistory;
use Maybeway\Domain\DomainEvents;
use Maybeway\Domain\IdentifiesAggregate;
use Maybeway\Event\EventBus;
use Maybeway\Event\EventStore;
use Maybeway\Event\EventStoreRepository;

/**
 * @package Maybeway\Event\SimpleEventBus
 * @author Michal Koričanský <koricansky.michal@gmail.com>
 */
class SimpleEventStore implements EventStore
{
    /**
     * @var EventStoreRepository
     */
    protected $eventStoreRepository;

    /**
     * @var EventBus
     */
    protected $eventBus;

    /**
     * @param EventStoreRepository $eventStoreRepository
     * @param EventBus             $eventBus
     */
    public function __construct(EventStoreRepository $eventStoreRepository, EventBus $eventBus)
    {
        $this->eventStoreRepository = $eventStoreRepository;
        $this->eventBus = $eventBus;
    }

    /**
     * @param DomainEvents $domainEvents
     */
    public function commit(DomainEvents $domainEvents): void
    {
        foreach ($domainEvents as $event) {
            $this->eventStoreRepository->save($event);
            $this->eventBus->publish($event);
        }
    }

    /**
     * @param IdentifiesAggregate $aggregateId
     *
     * @return AggregateHistory
     * @throws \Exception
     */
    public function getAggregateHistoryFor(IdentifiesAggregate $aggregateId): AggregateHistory
    {
        $events = [];
        $rawEvents = $this->eventStoreRepository->get($aggregateId);

        foreach ($rawEvents as $rawEvent) {
            $events[] = unserialize($rawEvent->data); //stream_get_contents
        }

        return new AggregateHistory(
            $aggregateId,
            $events
        );
    }
}
