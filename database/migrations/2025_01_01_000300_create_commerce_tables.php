<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commerce: carts, wishlist, orders, payments, webhook events, reviews, coupons.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------------------------------------------------------------- *
         * Carts — a guest holds one via an opaque cookie token; a signed-in
         * user holds one bound to their id. They merge on login.
         * ---------------------------------------------------------------- */
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('guest_token', 64)->nullable()->unique();
            $table->char('currency', 3)->default('NGN');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(1);

            // NOTE: no price is stored here. Line totals are always
            // recalculated from the live product/variant at read time so a
            // stale or tampered client price can never be honoured.
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'variant_id'], 'cart_line_unique');
            $table->index(['cart_id']);
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('guest_token', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id']);
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['wishlist_id', 'product_id', 'variant_id'], 'wishlist_line_unique');
        });

        /* ---------------------------------------------------------------- *
         * Coupons
         * ---------------------------------------------------------------- */
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 16)->default('percentage');   // percentage / fixed
            $table->unsignedBigInteger('value');                 // percent (0-100) or minor units
            $table->unsignedBigInteger('minimum_order_minor')->nullable();
            $table->unsignedBigInteger('maximum_discount_minor')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
        });

        /* ---------------------------------------------------------------- *
         * Orders. Everything price-bearing is snapshotted at order time so a
         * later product edit can never rewrite order history.
         * ---------------------------------------------------------------- */
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number')->unique();                  // human-readable: HB-2025-000123
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();

            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->string('customer_name');

            // Shipping address snapshot (denormalised on purpose — an order
            // must keep the address it was shipped to even if the address
            // book entry is later edited or deleted).
            $table->string('shipping_recipient_name');
            $table->string('shipping_phone', 32);
            $table->string('shipping_line1');
            $table->string('shipping_line2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state');
            $table->string('shipping_postal_code', 16)->nullable();
            $table->string('shipping_country', 2)->default('NG');

            $table->text('notes')->nullable();

            $table->char('currency', 3)->default('NGN');
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('shipping_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor');
            $table->unsignedBigInteger('commission_minor')->default(0);

            $table->string('status', 32)->default('pending');
            $table->string('payment_status', 32)->default('pending');
            $table->string('channel', 16)->default('web');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('token_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['payment_status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['created_at']);
        });

        // One sub-order per vendor inside a single checkout.
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();

            $table->string('number');                            // HB-2025-000123-01
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('shipping_minor')->default(0);
            $table->unsignedBigInteger('commission_minor')->default(0);
            $table->unsignedBigInteger('payout_minor')->default(0);   // subtotal - commission
            $table->decimal('commission_percent', 5, 2)->default(0);

            $table->string('status', 32)->default('pending');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('courier')->nullable();
            $table->unsignedInteger('token_version')->default(1);
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['order_id']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Snapshot — never joined live for display.
            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->string('sku')->nullable();
            $table->string('vendor_name');
            $table->string('image_path')->nullable();

            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('line_total_minor');
            $table->unsignedBigInteger('commission_minor')->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);

            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['vendor_id']);
            $table->index(['product_id']);
        });

        /* ---------------------------------------------------------------- *
         * Payments
         * ---------------------------------------------------------------- */
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('provider', 32);
            $table->string('status', 32)->default('pending');
            $table->string('reference')->unique();               // our reference sent to the gateway
            $table->string('provider_reference')->nullable();    // the gateway's own id
            $table->char('currency', 3)->default('NGN');
            $table->unsignedBigInteger('amount_minor');

            $table->string('channel')->nullable();               // card / bank / ussd …
            $table->json('gateway_payload')->nullable();         // last verified response
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('refunded_minor')->default(0);

            $table->timestamp('initialized_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['status', 'created_at']);
            $table->index(['provider', 'status']);
        });

        // Webhook dedupe: a replayed event must never re-mutate order state.
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('event_id');
            $table->string('event_type')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
            $table->index(['created_at']);
        });

        /* ---------------------------------------------------------------- *
         * Reviews
         * ---------------------------------------------------------------- */
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('rating');               // 1..5
            $table->string('title')->nullable();
            $table->text('body')->nullable();

            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->text('moderation_note')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'user_id', 'order_item_id'], 'review_unique_per_purchase');
            $table->index(['product_id', 'is_approved']);
        });

        /* ---------------------------------------------------------------- *
         * Newsletter + stock notifications
         * ---------------------------------------------------------------- */
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email')->unique();
            $table->string('locale', 8)->default('en');
            $table->boolean('is_active')->default(true);
            $table->string('source')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('vendor_orders');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
