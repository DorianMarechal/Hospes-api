<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: 'kernel.request', priority: 15)]
class SensitiveEndpointRateLimitListener
{
    public function __construct(
        private RateLimiterFactory $forgotPasswordLimiter,
        private RateLimiterFactory $registerLimiter,
        private RateLimiterFactory $acceptInvitationLimiter,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ('POST' !== $request->getMethod()) {
            return;
        }

        $path = $request->getPathInfo();
        $ip = $request->getClientIp() ?? 'unknown';

        $limiterFactory = match (true) {
            '/api/auth/forgot-password' === $path => $this->forgotPasswordLimiter,
            '/api/auth/register' === $path => $this->registerLimiter,
            str_starts_with($path, '/api/staff-invitations/') && str_ends_with($path, '/accept') => $this->acceptInvitationLimiter,
            default => null,
        };

        if (null === $limiterFactory) {
            return;
        }

        $limiter = $limiterFactory->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time(), 'Too many requests. Please try again later.');
        }
    }
}
