<?php declare(strict_types = 1);

namespace Maybeway\SimpleEventBus;

use Maybeway\Domain\DomainEvent;
use Maybeway\Event\EventBus;
use Maybeway\SimpleEventBus\Exception\MissingListenerClassInContainer;
use Maybeway\SimpleEventBus\Exception\MissingListenerClassMethod;
use Psr\Container\ContainerInterface;

/**
 * @package App\Model\SimpleEventBus
 * @author Michal Koričanský <koricansky.michal@gmail.com>
 */
final class SimpleEventBus implements EventBus
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var array
	 */
	protected $listeners;

	/**
	 * @param ContainerInterface $container
	 * @param array $listeners
	 */
	public function __construct( ContainerInterface $container, array $listeners )
	{
		$this->container = $container;
		$this->listeners = $listeners;
	}

    /**
     * @param DomainEvent $event
     *
     * @throws MissingListenerClassInContainer
     * @throws MissingListenerClassMethod
     */
	public function publish( DomainEvent $event ) : void
	{
		$listeners = $this->findListenersForEvent( $event );

		foreach ( $listeners as $listener ) {
			$this->dispatch( $listener, $event );
		}
	}

    /**
     * @param string      $listener
     * @param DomainEvent $event
     *
     * @throws MissingListenerClassInContainer
     * @throws MissingListenerClassMethod
     */
	public function dispatch( string $listener, DomainEvent $event ) : void
	{
 		if ( $this->container->has( $listener ) ) {
			$this->callMethod(
			    $this->container->get( $listener ),
                'on'.ClassProperties::name( $event ),
                $event
            );
		} else {
 			throw new MissingListenerClassInContainer( $listener );
		}
	}

    /**
     * @param object      $listenerClass
     * @param             $method
     * @param DomainEvent $event
     *
     * @throws MissingListenerClassMethod
     */
	protected function callMethod( object $listenerClass, $method, DomainEvent $event ) : void
	{
		if ( ! method_exists( $listenerClass, $method ) ) {
            throw new MissingListenerClassMethod(
                sprintf('Missing method : %s in class : %s', $listenerClass, $method)
            );
		}

        $listenerClass->{$method}( $event );
	}

	/**
	 * @param DomainEvent $event
	 * @return array
	 */
	protected function findListenersForEvent( DomainEvent $event ) : array
	{
		$eventClass = get_class( $event );

		if ( ! array_key_exists( $eventClass, $this->listeners ) ) {
			return [];
		}

        return $this->listeners[ $eventClass ];
	}
}