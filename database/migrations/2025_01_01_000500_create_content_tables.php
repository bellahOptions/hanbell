<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content: CMS pages (policy pages, about, help), site settings, SEO overrides
 * and the LLM-facing content index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('template', 32)->default('default');   // default / policy / contact
            $table->string('featured_image_path')->nullable();

            $table->string('status', 32)->default('draft');
            // Policy pages are grouped so the footer can list them by group.
            $table->string('group', 32)->default('general');     // general / policy / help / legal
            $table->unsignedInteger('position')->default(0);
            $table->boolean('show_in_footer')->default(true);
            $table->boolean('show_in_header')->default(false);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->boolean('is_indexable')->default(true);

            // LLM
            $table->text('llm_summary')->nullable();
            $table->json('translations')->nullable();

            $table->unsignedInteger('token_version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'group', 'position']);
        });

        /* ---------------------------------------------------------------- *
         * Key/value site settings, editable by the administrator.
         * Values are cast by App\Support\Settings per the `type` column.
         * ---------------------------------------------------------------- */
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 32)->default('general');
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type', 24)->default('string');   // string / text / boolean / integer / json / html
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);     // safe to expose to the frontend
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['group', 'position']);
        });

        /* ---------------------------------------------------------------- *
         * SEO overrides for routes that have no model of their own
         * (homepage, shop, cart, checkout, search…).
         * ---------------------------------------------------------------- */
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('route_name')->unique();          // e.g. storefront.home
            $table->string('path')->nullable();              // e.g. /shop
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('twitter_card', 32)->default('summary_large_image');
            $table->string('canonical_url')->nullable();
            $table->string('robots', 64)->default('index,follow');
            $table->json('schema_json')->nullable();         // extra JSON-LD nodes
            $table->text('llm_summary')->nullable();
            $table->json('llm_faq')->nullable();             // [{question, answer}]
            $table->timestamps();
        });

        /* ---------------------------------------------------------------- *
         * Redirects — admins can add 301s when a slug changes.
         * ---------------------------------------------------------------- */
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedBigInteger('hits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* ---------------------------------------------------------------- *
         * Banned / reserved slugs so generated product URLs cannot collide
         * with real routes.
         * ---------------------------------------------------------------- */
        Schema::create('reserved_slugs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserved_slugs');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('seo_meta');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pages');
    }
};
