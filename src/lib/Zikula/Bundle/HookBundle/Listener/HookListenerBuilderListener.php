<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\HookBundle\Listener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zikula\Bundle\HookBundle\Hook\AbstractHookListener;

class HookListenerBuilderListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var bool
     */
    private $installed;

    public function __construct(ContainerInterface $container, $installed)
    {
        $this->container = $container;
        $this->installed = $installed;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['addListeners', 1000]
            ],
        ];
    }

    /**
     * Add dynamically assigned listeners to hookable events at runtime.
     * @param GetResponseEvent $event
     */
    public function addListeners(GetResponseEvent $event)
    {
        if (!$this->installed || !$event->isMasterRequest()) {
            return;
        }

        $handlers = $this->container->get('zikula_hook_bundle.hook_runtime_repository')->findAll();
        foreach ($handlers as $handler) {
            $callable = [$handler['classname'], $handler['method']];
            if (is_callable($callable)) {
                if (!empty($handler['serviceid'])) {
                    if ($this->container->get('kernel')->isBundle($handler['powner']) || \ModUtil::available($handler['powner'])) { // @deprecated call
                        $eventDispatcher = $this->container->get('event_dispatcher');
                        if (!$this->container->has($handler['serviceid'])) {
                            // @deprecated - in Core-2.0 all services must be pre-registered with the container via DI
                            $className = $handler['classname'];
                            $args = is_subclass_of($className, AbstractHookListener::class) ? $eventDispatcher : null;
                            $this->container->set($handler['serviceid'], new $className($args));
                        }
                        $eventDispatcher->addListenerService($handler['eventname'], [$handler['serviceid'], $handler['method']]);
                    }
                } else {
                    throw new \InvalidArgumentException('Hook definitions must include a valid service ID.'); // add 'that is already registered with the container' at Core-2.0
                }
            }
        }
    }
}
