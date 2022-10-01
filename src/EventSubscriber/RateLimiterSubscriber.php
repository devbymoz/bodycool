<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RateLimiterSubscriber implements EventSubscriberInterface
{
    /**
     * @var RateLimiterFactory
     */
    private $resetPasswordLimiter;

    public function __construct(RateLimiterFactory $resetPasswordLimiter)
    {
        $this->resetPasswordLimiter = $resetPasswordLimiter;
    }
 
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (strpos($request->get("_route"), 'app_mot_de_passe_perdu') !== false) {
            $limiter = $this->resetPasswordLimiter->create($request->getClientIp());
            if (false === $limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }
        }
    }
}
