<?php

namespace Tests\Unit;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderLog;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class OrderStatusUpdatedMailTest extends TestCase
{
    #[Test]
    public function it_passes_the_log_from_the_constructor_to_the_view_payload(): void
    {
        $order = new Order(['id' => 4]);
        $log = new OrderLog(['id' => 9]);

        $mailable = new OrderStatusUpdated($order, $log);
        $content = $mailable->content();

        $this->assertSame($order, $content->with['order']);
        $this->assertSame($log, $content->with['log']);
    }

    #[Test]
    public function it_supports_legacy_queued_payloads_that_only_have_order_log(): void
    {
        $order = new Order(['id' => 4]);
        $legacyLog = new OrderLog(['id' => 9]);

        $reflection = new ReflectionClass(OrderStatusUpdated::class);
        /** @var OrderStatusUpdated $mailable */
        $mailable = $reflection->newInstanceWithoutConstructor();
        $mailable->order = $order;
        $mailable->orderLog = $legacyLog;

        $content = $mailable->content();

        $this->assertSame($order, $content->with['order']);
        $this->assertSame($legacyLog, $content->with['log']);
    }
}
