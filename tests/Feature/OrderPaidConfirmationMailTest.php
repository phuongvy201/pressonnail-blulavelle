<?php

use App\Mail\NewOrderAdminNotification;
use App\Mail\OrderConfirmation;
use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Services\CheckoutAttemptService;
use App\Services\Orders\OrderPaidMailer;
use App\Support\SalesChannel;
use Illuminate\Support\Facades\Mail;

function confirmationOrder(array $overrides = []): Order
{
    return Order::query()->create(array_merge([
        'order_number' => 'ORD-MAIL-'.uniqid(),
        'customer_name' => 'Alex Buyer',
        'customer_email' => 'alex@example.com',
        'shipping_address' => '123 Main St',
        'city' => 'Austin',
        'postal_code' => '78701',
        'country' => 'US',
        'subtotal' => 40,
        'total_amount' => 40,
        'currency' => 'USD',
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_method' => 'stripe',
        'sales_channel' => SalesChannel::APP,
    ], $overrides));
}

test('order confirmation copy names the app or the website', function () {
    $app = confirmationOrder([
        'sales_channel' => SalesChannel::APP,
        'payment_status' => 'paid',
        'status' => 'processing',
    ]);
    $web = confirmationOrder([
        'order_number' => 'ORD-WEB-'.uniqid(),
        'customer_email' => 'web@example.com',
        'sales_channel' => SalesChannel::WEBSITE,
        'payment_status' => 'paid',
        'status' => 'processing',
    ]);

    $appMail = new OrderConfirmation($app);
    $webMail = new OrderConfirmation($web);

    expect($appMail->envelope()->subject)->toContain('(App)')
        ->and($appMail->render())->toContain('Placed in the BluLavelle app')
        ->and($webMail->envelope()->subject)->toContain('(Website)')
        ->and($webMail->render())->toContain('Placed on the BluLavelle website');

    expect((new NewOrderAdminNotification($app))->render())->toContain('Placed in the BluLavelle app');
    expect((new NewOrderAdminNotification($web))->envelope()->subject)->toContain('(Website)');
});

test('marking an app checkout attempt paid sends the confirmation once', function () {
    Mail::fake();
    $order = confirmationOrder();
    $attempt = CheckoutAttempt::query()->create([
        'idempotency_key' => 'mail-'.uniqid(),
        'request_hash' => str_repeat('a', 64),
        'status' => 'requires_action',
        'order_id' => $order->id,
    ]);

    $service = app(CheckoutAttemptService::class);
    $method = new ReflectionMethod($service, 'markSucceededFromIntent');
    $method->setAccessible(true);
    $method->invoke($service, $attempt, 'pi_app_mail');
    $method->invoke($service, $attempt->fresh(), 'pi_app_mail');

    Mail::assertSent(OrderConfirmation::class, function (OrderConfirmation $mail) use ($order) {
        return $mail->hasTo($order->customer_email)
            && str_contains($mail->envelope()->subject, '(App)');
    });
    Mail::assertSent(NewOrderAdminNotification::class, fn (NewOrderAdminNotification $mail) => $mail->hasTo(OrderPaidMailer::ADMIN_EMAIL));
    Mail::assertSentCount(2);
    expect($order->fresh()->payment_status)->toBe('paid');
});

test('stripe webhook sends the app confirmation and skips a second website copy', function () {
    Mail::fake();
    config(['services.stripe.webhook_secret' => null]);

    $app = confirmationOrder([
        'order_number' => 'APP-WH-1',
        'customer_email' => 'app-wh@example.com',
    ]);
    $web = confirmationOrder([
        'order_number' => 'WEB-WH-1',
        'customer_email' => 'web-wh@example.com',
        'sales_channel' => SalesChannel::WEBSITE,
    ]);

    $this->postJson('/webhooks/stripe', stripePaidEvent('evt_app_confirm', 'pi_app_wh', $app))->assertOk();
    $this->postJson('/webhooks/stripe', stripePaidEvent('evt_app_confirm_again', 'pi_app_wh', $app))->assertOk();
    $this->postJson('/webhooks/stripe', stripePaidEvent('evt_web_confirm', 'pi_web_wh', $web))->assertOk();

    Mail::assertSent(OrderConfirmation::class, fn (OrderConfirmation $mail) => $mail->hasTo('app-wh@example.com'));
    Mail::assertNotSent(OrderConfirmation::class, fn (OrderConfirmation $mail) => $mail->hasTo('web-wh@example.com'));
    Mail::assertSent(NewOrderAdminNotification::class, fn (NewOrderAdminNotification $mail) => $mail->hasTo(OrderPaidMailer::ADMIN_EMAIL));
    Mail::assertSentCount(2);
    expect($app->fresh()->payment_status)->toBe('paid');
    expect($web->fresh()->payment_status)->toBe('paid');
});

function stripePaidEvent(string $eventId, string $paymentIntentId, Order $order): array
{
    return [
        'id' => $eventId,
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_'.$eventId,
                'payment_intent' => $paymentIntentId,
                'payment_status' => 'paid',
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                ],
            ],
        ],
    ];
}
