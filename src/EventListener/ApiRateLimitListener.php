<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: 'kernel.request', priority: 10)]
#[AsEventListener(event: 'kernel.response', priority: -10)]
class ApiRateLimitListener
{
    public function __construct(
        private RateLimiterFactory $apiReadLimiter,
        private RateLimiterFactory $apiWriteLimiter,
        private RateLimiterFactory $apiUserLimiter,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    private const array EXEMPT_PATHS = [
        '/api/webhooks/stripe',
        '/api/webhooks/paypal',
    ];

    private const array READ_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __invoke(RequestEvent|ResponseEvent $event): void
    {
        if ($event instanceof ResponseEvent) {
            $this->onResponse($event);

            return;
        }

        $this->onRequest($event);
    }

    private function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api/')) {
            return;
        }

        if ('/api/login_check' === $path) {
            return;
        }

        if (\in_array($path, self::EXEMPT_PATHS, true)) {
            return;
        }

        $ip = $request->getClientIp() ?? 'unknown';
        $isRead = \in_array($request->getMethod(), self::READ_METHODS, true);

        // IP-based rate limit (read vs write)
        $limiterFactory = $isRead ? $this->apiReadLimiter : $this->apiWriteLimiter;
        $limiter = $limiterFactory->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time(), 'Too many requests. Please slow down.');
        }

        // Store limit info for response headers
        $request->attributes->set('_rate_limit', $limit->getRemainingTokens());
        $request->attributes->set('_rate_limit_max', $isRead ? 200 : 60);
        $request->attributes->set('_rate_limit_reset', $limit->getRetryAfter()->getTimestamp());

        // Per-user rate limit (if authenticated)
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (null !== $user) {
            $userId = $user->getUserIdentifier();
            $userLimiter = $this->apiUserLimiter->create($userId);
            $userLimit = $userLimiter->consume();

            if (!$userLimit->isAccepted()) {
                throw new TooManyRequestsHttpException($userLimit->getRetryAfter()->getTimestamp() - time(), 'Too many requests for this user. Please slow down.');
            }
        }
    }

    private function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->attributes->has('_rate_limit')) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('X-RateLimit-Limit', (string) $request->attributes->get('_rate_limit_max'));
        $response->headers->set('X-RateLimit-Remaining', (string) $request->attributes->get('_rate_limit'));
        $response->headers->set('X-RateLimit-Reset', (string) $request->attributes->get('_rate_limit_reset'));
    }
}
