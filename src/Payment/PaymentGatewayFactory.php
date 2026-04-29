<?php

namespace App\Payment;

use App\Enum\PaymentProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PaymentGatewayFactory
{
    public function __construct(
        #[Autowire(service: 'App\Payment\StripeGateway')]
        private PaymentGatewayInterface $stripeGateway,
        #[Autowire(service: 'App\Payment\PayPalGateway')]
        private PaymentGatewayInterface $payPalGateway,
    ) {
    }

    public function get(PaymentProvider $provider): PaymentGatewayInterface
    {
        return match ($provider) {
            PaymentProvider::STRIPE => $this->stripeGateway,
            PaymentProvider::PAYPAL => $this->payPalGateway,
        };
    }
}
