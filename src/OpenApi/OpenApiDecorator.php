<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;

class OpenApiDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $openApi = $this->addAuthEndpoints($openApi);
        $openApi = $this->addWebhookEndpoints($openApi);
        $openApi = $this->addOperationDescriptions($openApi);

        return $openApi;
    }

    private function addAuthEndpoints(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        // POST /api/login_check
        $paths->addPath('/api/login_check', new Model\PathItem(
            post: new Model\Operation(
                operationId: 'login_check',
                tags: ['Authentication'],
                summary: 'Authenticate and obtain JWT token',
                description: 'Authenticate with email and password. Returns a JWT access token and a refresh token.',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email', 'password'],
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'user@example.com'],
                                    'password' => ['type' => 'string', 'example' => 'password123'],
                                ],
                            ],
                        ],
                    ]),
                ),
                responses: [
                    '200' => new Model\Response(
                        description: 'JWT token pair returned',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'token' => ['type' => 'string', 'description' => 'JWT access token'],
                                        'refresh_token' => ['type' => 'string', 'description' => 'Refresh token for obtaining new access tokens'],
                                    ],
                                ],
                            ],
                        ]),
                    ),
                    '401' => new Model\Response(description: 'Invalid credentials'),
                    '429' => new Model\Response(description: 'Too many login attempts'),
                ],
            ),
        ));

        // POST /api/token/refresh
        $paths->addPath('/api/token/refresh', new Model\PathItem(
            post: new Model\Operation(
                operationId: 'token_refresh',
                tags: ['Authentication'],
                summary: 'Refresh JWT access token',
                description: 'Exchange a valid refresh token for a new JWT access token.',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['refresh_token'],
                                'properties' => [
                                    'refresh_token' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ]),
                ),
                responses: [
                    '200' => new Model\Response(
                        description: 'New JWT token pair',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'token' => ['type' => 'string'],
                                        'refresh_token' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ]),
                    ),
                    '401' => new Model\Response(description: 'Invalid or expired refresh token'),
                ],
            ),
        ));

        return $openApi->withPaths($paths);
    }

    private function addWebhookEndpoints(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        // POST /api/webhooks/stripe
        $paths->addPath('/api/webhooks/stripe', new Model\PathItem(
            post: new Model\Operation(
                operationId: 'webhook_stripe',
                tags: ['Webhooks'],
                summary: 'Stripe webhook endpoint',
                description: 'Receives and processes Stripe payment events (payment_intent.succeeded, payment_intent.payment_failed). Requires valid Stripe-Signature header.',
                responses: [
                    '200' => new Model\Response(description: 'Webhook processed or ignored'),
                    '400' => new Model\Response(description: 'Invalid webhook signature'),
                ],
            ),
        ));

        // POST /api/webhooks/paypal
        $paths->addPath('/api/webhooks/paypal', new Model\PathItem(
            post: new Model\Operation(
                operationId: 'webhook_paypal',
                tags: ['Webhooks'],
                summary: 'PayPal webhook endpoint',
                description: 'Receives and processes PayPal payment events (PAYMENT.CAPTURE.COMPLETED, PAYMENT.CAPTURE.DENIED). Requires valid PayPal signature headers.',
                responses: [
                    '200' => new Model\Response(description: 'Webhook processed or ignored'),
                    '400' => new Model\Response(description: 'Invalid webhook signature'),
                ],
            ),
        ));

        return $openApi->withPaths($paths);
    }

    private function addOperationDescriptions(OpenApi $openApi): OpenApi
    {
        $descriptions = $this->getOperationDescriptions();

        $paths = $openApi->getPaths();
        foreach ($paths->getPaths() as $path => $pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                $getter = 'get'.ucfirst($method);
                $operation = $pathItem->$getter();
                if (null === $operation) {
                    continue;
                }

                $key = strtoupper($method).' '.$path;
                if (isset($descriptions[$key]) && ('' === ($operation->getSummary() ?? ''))) {
                    $operation = $operation->withSummary($descriptions[$key]['summary']);
                    if (isset($descriptions[$key]['description'])) {
                        $operation = $operation->withDescription($descriptions[$key]['description']);
                    }

                    $wither = 'with'.ucfirst($method);
                    $pathItem = $pathItem->$wither($operation);
                    $paths->addPath($path, $pathItem);
                }
            }
        }

        return $openApi->withPaths($paths);
    }

    /**
     * @return array<string, array{summary: string, description?: string}>
     */
    private function getOperationDescriptions(): array
    {
        return [
            // Auth
            'GET /api/auth/me' => ['summary' => 'Get current user profile', 'description' => 'Returns the authenticated user\'s profile information.'],
            'GET /api/me/data-export' => ['summary' => 'Export personal data (GDPR)', 'description' => 'Download all personal data associated with the authenticated user.'],
            'DELETE /api/me/account' => ['summary' => 'Delete account', 'description' => 'Permanently delete the authenticated user\'s account and anonymize associated data.'],

            // Lodgings
            'GET /api/lodgings' => ['summary' => 'List public lodgings', 'description' => 'Returns active lodgings visible to all users.'],
            'GET /api/lodgings/{id}' => ['summary' => 'Get lodging details'],
            'GET /api/me/lodgings' => ['summary' => 'List my lodgings', 'description' => 'Returns lodgings owned by the authenticated host.'],
            'POST /api/lodgings' => ['summary' => 'Create a lodging'],
            'PATCH /api/lodgings/{id}' => ['summary' => 'Update a lodging'],
            'DELETE /api/lodgings/{id}' => ['summary' => 'Delete a lodging'],

            // Lodging images & amenities
            'POST /api/lodgings/{lodgingId}/images' => ['summary' => 'Add image to lodging'],
            'PUT /api/lodging-images/{id}' => ['summary' => 'Update image metadata'],
            'DELETE /api/lodging-images/{id}' => ['summary' => 'Delete lodging image'],
            'POST /api/lodgings/{lodgingId}/amenities' => ['summary' => 'Add amenity to lodging'],
            'GET /api/amenities' => ['summary' => 'List available amenities'],

            // Seasons & pricing
            'GET /api/lodgings/{lodgingId}/seasons' => ['summary' => 'List seasons for a lodging'],
            'GET /api/seasons/{id}' => ['summary' => 'Get season details'],
            'POST /api/lodgings/{lodgingId}/seasons' => ['summary' => 'Create a season'],
            'PUT /api/seasons/{id}' => ['summary' => 'Update a season'],
            'DELETE /api/seasons/{id}' => ['summary' => 'Delete a season'],
            'GET /api/lodgings/{lodgingId}/price-overrides' => ['summary' => 'List price overrides'],
            'POST /api/lodgings/{lodgingId}/price-overrides' => ['summary' => 'Create a price override'],
            'PUT /api/price-overrides/{id}' => ['summary' => 'Update a price override'],
            'DELETE /api/price-overrides/{id}' => ['summary' => 'Delete a price override'],

            // Bookings
            'POST /api/bookings' => ['summary' => 'Create a booking', 'description' => 'Creates a pending booking with 15-minute confirmation window. Price is calculated server-side.'],
            'GET /api/bookings/{id}' => ['summary' => 'Get booking details'],
            'GET /api/bookings' => ['summary' => 'Find booking by reference', 'description' => 'Lookup a booking by its reference code (query parameter ?reference=).'],
            'GET /api/me/bookings' => ['summary' => 'List my bookings as customer'],
            'GET /api/lodgings/{lodgingId}/bookings' => ['summary' => 'List bookings for a lodging'],
            'GET /api/bookings/{id}/nights' => ['summary' => 'Get night-by-night pricing breakdown'],
            'GET /api/bookings/{id}/history' => ['summary' => 'Get booking status history'],
            'POST /api/bookings/{id}/confirm' => ['summary' => 'Confirm a pending booking'],
            'POST /api/bookings/{id}/cancel' => ['summary' => 'Cancel a booking', 'description' => 'Cancels a booking. Refund amount depends on the cancellation policy.'],
            'PATCH /api/bookings/{id}/dates' => ['summary' => 'Modify booking dates', 'description' => 'Updates check-in/check-out dates. Price is recalculated. Availability and orphan protection are checked.'],

            // Modification requests
            'POST /api/bookings/{bookingId}/modification-request' => ['summary' => 'Request booking modification', 'description' => 'Creates a modification request requiring approval from the other party. Expires after 48 hours.'],
            'GET /api/booking-modifications/{id}' => ['summary' => 'Get modification request details'],
            'GET /api/bookings/{bookingId}/modification-requests' => ['summary' => 'List modification requests for a booking'],
            'POST /api/booking-modifications/{id}/accept' => ['summary' => 'Accept a modification request'],
            'POST /api/booking-modifications/{id}/reject' => ['summary' => 'Reject a modification request'],

            // Payments
            'POST /api/bookings/{bookingId}/payments' => ['summary' => 'Create a payment', 'description' => 'Initiates payment for a confirmed booking. Supports Idempotency-Key header for deduplication.'],
            'GET /api/bookings/{bookingId}/payments' => ['summary' => 'List payments for a booking'],
            'GET /api/me/payments' => ['summary' => 'List received payments as host'],
            'POST /api/payments/{id}/refund' => ['summary' => 'Refund a payment'],

            // Deposits
            'GET /api/bookings/{bookingId}/deposit' => ['summary' => 'Get deposit for a booking'],
            'POST /api/bookings/{bookingId}/deposit/retain' => ['summary' => 'Retain deposit (partial or full)'],
            'POST /api/bookings/{bookingId}/deposit/release' => ['summary' => 'Release deposit to guest'],

            // Staff
            'GET /api/me/staff' => ['summary' => 'List staff assignments'],
            'POST /api/me/staff' => ['summary' => 'Invite a staff member'],
            'PATCH /api/staff-assignments/{id}/permissions' => ['summary' => 'Update staff permissions'],
            'PATCH /api/staff-assignments/{id}/lodgings' => ['summary' => 'Update staff lodging scope'],
            'POST /api/staff-assignments/{id}/revoke' => ['summary' => 'Revoke staff access'],

            // Messaging
            'POST /api/lodgings/{lodgingId}/conversations' => ['summary' => 'Start a conversation about a lodging'],
            'GET /api/me/conversations' => ['summary' => 'List my conversations'],
            'POST /api/conversations/{id}/read' => ['summary' => 'Mark conversation as read'],
            'GET /api/conversations/{conversationId}/messages' => ['summary' => 'List messages in a conversation'],
            'POST /api/conversations/{conversationId}/messages' => ['summary' => 'Send a message'],

            // Favorites
            'POST /api/me/favorites' => ['summary' => 'Add lodging to favorites'],
            'GET /api/me/favorites' => ['summary' => 'List my favorite lodgings'],
            'DELETE /api/favorites/{id}' => ['summary' => 'Remove from favorites'],

            // Reviews
            'POST /api/bookings/{bookingId}/review' => ['summary' => 'Leave a review for a completed booking'],
            'GET /api/lodgings/{lodgingId}/reviews' => ['summary' => 'List reviews for a lodging'],
            'GET /api/me/reviews' => ['summary' => 'List my reviews as host'],
            'POST /api/reviews/{id}/response' => ['summary' => 'Respond to a review as host'],
            'DELETE /api/reviews/{id}' => ['summary' => 'Delete a review (admin only)'],

            // Notifications
            'GET /api/me/notifications' => ['summary' => 'List my notifications'],
            'PATCH /api/notifications/{id}' => ['summary' => 'Mark notification as read'],
            'POST /api/me/notifications/read-all' => ['summary' => 'Mark all notifications as read'],

            // iCal
            'GET /api/lodgings/{lodgingId}/ical-feeds' => ['summary' => 'List iCal feeds for a lodging'],
            'POST /api/lodgings/{lodgingId}/ical-feeds' => ['summary' => 'Add an iCal feed'],
            'POST /api/ical-feeds/{id}/sync' => ['summary' => 'Trigger manual iCal sync'],

            // Blocked dates
            'GET /api/lodgings/{lodgingId}/blocked-dates' => ['summary' => 'List blocked dates for a lodging'],
            'POST /api/lodgings/{lodgingId}/blocked-dates' => ['summary' => 'Block dates on a lodging'],
        ];
    }
}
