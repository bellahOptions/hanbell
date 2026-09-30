<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue: vendors, brands, categories, products, variants, media,
 * attributes, tags, inventory.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------------------------------------------------------------- *
         * Vendors — the multi-vendor core. A vendor is a Nigerian fashion
         * brand or independent creator selling through HanbellShop.
         * ---------------------------------------------------------------- */
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('website')->nullable();

            $table->text('description')->nullable();
            $table->text('story')->nullable();                 // brand story — Nigerian-made narrative

            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();

            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();               // Nigerian state
            $table->string('country', 2)->default('NG');

            $table->string('status', 32)->default('pending');
            $table->text('status_reason')->nullable();

            // Commission override: when null the category, then the global
            // default, is used (see OrderService::commissionPercentFor).
            $table->decimal('commission_percent', 5, 2)->nullable();

            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('token_version')->default(1);

            $table->timestamp('applied_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['is_featured', 'status']);
        });

        /* ---------------------------------------------------------------- *
         * Departments + categories (two levels: department > category).
         * ---------------------------------------------------------------- */
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('gender', 16)->nullable();          // women / men / unisex / kids
            $table->string('icon')->nullable();                // heroicon name
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // Per-category commission override.
            $table->decimal('commission_percent', 5, 2)->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->unsignedInteger('token_version')->default(1);
            $table->timestamps();

            $table->index(['is_active', 'position']);
            $table->index(['department_id', 'is_active']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('country', 2)->default('NG');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedInteger('token_version')->default(1);
            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
        });

        /* ---------------------------------------------------------------- *
         * Products
         * ---------------------------------------------------------------- */
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->text('summary')->nullable();                // short card blurb
            $table->longText('description')->nullable();

            // Money in minor units (kobo). Never floats.
            $table->unsignedBigInteger('price_minor');
            $table->unsignedBigInteger('compare_at_price_minor')->nullable();   // "was" price
            $table->unsignedBigInteger('cost_minor')->nullable();               // internal
            $table->char('currency', 3)->default('NGN');

            $table->string('status', 32)->default('draft');
            $table->text('status_reason')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_handmade')->default(true);

            // Fashion attributes
            $table->string('gender', 16)->default('unisex');
            $table->string('material')->nullable();
            $table->string('care_instructions')->nullable();
            $table->string('country_of_origin', 2)->default('NG');
            $table->string('made_in_city')->nullable();         // e.g. "Aba", "Kano"

            // Physical
            $table->unsignedInteger('weight_grams')->nullable();

            // Denormalised metrics — kept fresh by services, never aggregated
            // at query time on hot paths.
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);

            // Translation-ready content, keyed by locale.
            $table->json('translations')->nullable();

            // --- SEO ---
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('is_indexable')->default(true);

            // --- LLM / generative-engine metadata ---
            // A compact, factual summary written for retrieval by language
            // models (surfaced in /llms.txt and product JSON-LD).
            $table->text('llm_summary')->nullable();
            $table->json('llm_attributes')->nullable();          // key/value facts for AI answers

            $table->unsignedInteger('token_version')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['vendor_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['gender', 'status']);
            $table->index(['price_minor']);
        });

        /* ---------------------------------------------------------------- *
         * Variants — size / colour combinations that actually hold stock.
         * ---------------------------------------------------------------- */
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('name');                            // "Medium / Indigo"
            $table->string('sku')->nullable()->unique();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('color_hex', 9)->nullable();
            $table->string('material')->nullable();

            // Absolute price for the variant; when null the product price is used.
            $table->unsignedBigInteger('price_minor')->nullable();
            $table->unsignedBigInteger('compare_at_price_minor')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('token_version')->default(1);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index(['size']);
            $table->index(['color']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->string('path');                            // local path or absolute CDN URL
            $table->string('disk')->default('public');
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            // Unsplash attribution — required by the Unsplash licence.
            $table->string('credit_name')->nullable();
            $table->string('credit_url')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'position']);
            $table->index(['product_id', 'is_primary']);
        });

        /* ---------------------------------------------------------------- *
         * Attributes (Material, Fit, Occasion…) and their values.
         * ---------------------------------------------------------------- */
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 24)->default('select');      // select / multiselect / text / boolean
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_variant_axis')->default(false); // may define variants (size, colour)
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('slug');
            $table->string('color_hex', 9)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
        });

        Schema::create('attribute_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'attribute_id', 'attribute_value_id'], 'attr_product_value_unique');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 24)->default('general');     // general / style / occasion / fabric
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamps();
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'tag_id']);
        });

        /* ---------------------------------------------------------------- *
         * Inventory — one row per variant, plus an append-only movement log.
         * ---------------------------------------------------------------- */
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();

            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(3);
            $table->boolean('allow_backorder')->default(false);

            $table->timestamps();

            $table->unique(['variant_id']);
            $table->index(['product_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->string('reason', 32);
            $table->integer('quantity_change');               // signed
            $table->unsignedInteger('quantity_after');
            // NOTE: the polymorphic reference pair (reference_type /
            // reference_id) is added by a later migration via nullableMorphs().
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['reason', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('attribute_product');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('vendors');
    }
};
