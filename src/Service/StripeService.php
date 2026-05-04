<?php

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeService
{
    private string $secretKey;
    private string $publicKey;

    // Point packs: [points => price in cents (EUR)]
    public const PACKS = [
        100  => ['price' => 500,  'label' => '100 points',  'priceLabel' => '5 €',  'popular' => false, 'icon' => '⚡',  'color' => '#06b6d4', 'save' => null],
        250  => ['price' => 1000, 'label' => '250 points',  'priceLabel' => '10 €', 'popular' => true,  'icon' => '🔥',  'color' => '#4f46e5', 'save' => '17%'],
        500  => ['price' => 1800, 'label' => '500 points',  'priceLabel' => '18 €', 'popular' => false, 'icon' => '💎',  'color' => '#7c3aed', 'save' => '28%'],
    ];

    public function __construct(string $stripeSecretKey, string $stripePublicKey)
    {
        $this->secretKey = $stripeSecretKey;
        $this->publicKey = $stripePublicKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Create a Stripe Checkout Session for buying points.
     */
    public function createCheckoutSession(int $userId, int $points, string $successUrl, string $cancelUrl): string
    {
        Stripe::setApiKey($this->secretKey);

        $pack = self::PACKS[$points] ?? null;
        if (!$pack) {
            throw new \InvalidArgumentException('Invalid points pack: ' . $points);
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $pack['price'],
                    'product_data' => [
                        'name'        => $pack['label'] . ' — Talentos',
                        'description' => 'Achat de ' . $points . ' points pour accéder aux formations payantes',
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'metadata'    => [
                'user_id' => $userId,
                'points'  => $points,
            ],
        ]);

        return $session->url;
    }

    /**
     * Retrieve and validate a Checkout Session.
     */
    public function retrieveSession(string $sessionId): ?Session
    {
        Stripe::setApiKey($this->secretKey);

        try {
            $session = Session::retrieve($sessionId);
            return ($session->payment_status === 'paid') ? $session : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
