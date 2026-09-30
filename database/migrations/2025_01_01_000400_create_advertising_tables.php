<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advertising: campaigns, creatives, the placement registry, the event stream
 * and the daily rollup that the serving algorithm reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------------------------------------------------------------- *
         * Advertisers (an in-house record of who bought the space; may be a
         * vendor or an external brand).
         * ---------------------------------------------------------------- */
        Schema::create('advertisers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('billing_reference')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /* ---------------------------------------------------------------- *
         * Placement registry — kept in sync from AdPlacementKey by a seeder,
         * so slots can be joined and counted instead of living as free-form
         * strings scattered around the views.
         * ---------------------------------------------------------------- */
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('recommended_size')->nullable();
            $table->string('aspect_class')->nullable();
            $table->unsignedTinyInteger('max_creatives')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('advertiser_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('pricing_model', 16)->default('cpm');

            // Budget in minor units. `spend_minor` is denormalised so the
            // serving path never aggregates the unbounded events table.
            $table->unsignedBigInteger('budget_minor')->default(0);
            $table->unsignedBigInteger('spend_minor')->default(0);
            $table->unsignedBigInteger('bid_minor')->default(0);      // per 1000 impressions, or per click

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // --- Targeting ---
            $table->string('audience', 32)->default('everyone');
            $table->string('device', 16)->default('all');
            $table->json('target_department_ids')->nullable();
            $table->json('target_category_ids')->nullable();
            $table->json('target_locales')->nullable();
            $table->json('target_genders')->nullable();

            // --- Delivery controls ---
            $table->unsignedInteger('max_impressions_per_session')->default(3);
            $table->unsignedInteger('daily_impression_cap')->nullable();
            $table->unsignedInteger('priority')->default(0);          // manual weight, breaks ties
            $table->boolean('is_exclusive')->default(false);          // sole occupant of its slots

            // --- Denormalised counters (kept fresh by AdTrackingService) ---
            $table->unsignedBigInteger('impressions_count')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->unsignedBigInteger('conversions_count')->default(0);

            $table->unsignedInteger('token_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index(['vendor_id', 'status']);
            $table->index(['budget_minor', 'spend_minor']);
        });

        Schema::create('ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('headline')->nullable();
            $table->string('subheadline')->nullable();
            $table->string('cta_label')->default('Shop now');
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();
            $table->string('mobile_image_path')->nullable();
            $table->string('background_color', 9)->nullable();
            $table->string('text_color', 9)->nullable();
            $table->string('destination_url');
            $table->string('destination_type', 16)->default('external');   // internal / external / product

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('weight')->default(1);                 // rotation weight inside a slot

            // Denormalised counters, same reasoning as the campaign.
            $table->unsignedBigInteger('impressions_count')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);

            // Thompson-sampling state for creative rotation.
            $table->decimal('ctr_prior_alpha', 8, 3)->default(1);
            $table->decimal('ctr_prior_beta', 8, 3)->default(99);

            $table->unsignedInteger('token_version')->default(1);
            $table->timestamps();

            $table->index(['ad_campaign_id', 'is_active']);
        });

        Schema::create('ad_campaign_placement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_placement_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('weight')->default(1);      // relative share inside this slot
            $table->timestamps();

            $table->unique(['ad_campaign_id', 'ad_placement_id'], 'campaign_placement_unique');
        });

        /* ---------------------------------------------------------------- *
         * Event stream. One row per counted impression/click/conversion.
         * The raw IP is never stored — only a daily-rotating SHA-256 hash.
         * ---------------------------------------------------------------- */
        Schema::create('ad_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_creative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_placement_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event_type', 16);
            $table->string('visitor_hash', 64)->nullable();     // sha256(ip + ua + day)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_authenticated')->default(false);

            $table->unsignedBigInteger('spend_minor')->default(0);   // what this event cost
            $table->string('device', 16)->nullable();
            $table->string('locale', 8)->nullable();
            $table->string('path')->nullable();                      // where it was served
            $table->string('referrer')->nullable();
            $table->timestamp('occurred_at');

            $table->timestamps();

            $table->index(['ad_campaign_id', 'occurred_at']);
            $table->index(['ad_creative_id', 'event_type']);
            $table->index(['visitor_hash', 'ad_campaign_id', 'occurred_at'], 'ad_events_visitor_campaign_idx');
        });

        /* ---------------------------------------------------------------- *
         * Daily rollup — the serving algorithm and admin charts read this
         * instead of scanning ad_events.
         * ---------------------------------------------------------------- */
        Schema::create('ad_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_creative_id')->nullable()->constrained()->cascadeOnDelete();

            // Deliberately NOT date-cast in the model: a `date` cast makes
            // Eloquent serialise the column as `Y-m-d 00:00:00` in WHERE
            // clauses, which never matches a stored DATE, so upserts miss the
            // existing row and die on the unique index.
            $table->date('date');

            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->unsignedBigInteger('spend_minor')->default(0);
            $table->unsignedBigInteger('revenue_minor')->default(0);

            $table->timestamps();

            $table->unique(['ad_campaign_id', 'ad_creative_id', 'date'], 'ad_daily_stats_unique');
            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_daily_stats');
        Schema::dropIfExists('ad_events');
        Schema::dropIfExists('ad_campaign_placement');
        Schema::dropIfExists('ad_creatives');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('ad_placements');
        Schema::dropIfExists('advertisers');
    }
};
