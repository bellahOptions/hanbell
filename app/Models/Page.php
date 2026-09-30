<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * CMS pages: policy pages (terms, privacy, returns), about, help, contact.
 * Every footer link resolves to a real page rather than a dead 404.
 */
class Page extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'template', 'featured_image_path',
        'status', 'group', 'position', 'show_in_footer', 'show_in_header',
        'meta_title', 'meta_description', 'meta_keywords', 'is_indexable',
        'llm_summary', 'translations', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'show_in_footer' => 'boolean',
            'show_in_header' => 'boolean',
            'is_indexable' => 'boolean',
            'position' => 'integer',
            'translations' => 'array',
            'published_at' => 'datetime',
            'token_version' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeInFooter(Builder $query): Builder
    {
        return $query->published()->where('show_in_footer', true)->orderBy('position');
    }

    public function scopeOfGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published;
    }

    public function publicUrl(): string
    {
        return route('storefront.pages.show', ['slug' => $this->slug]);
    }

    /**
     * Content rendered as safe HTML. The admin editor is trusted (it is
     * admin-only), so a small allow-list of tags is preserved rather than
     * escaping everything — but raw <script> is always removed.
     */
    public function renderedContent(): string
    {
        $content = (string) $this->content;

        if ($content === '') {
            return '';
        }

        // Strip script/style blocks outright, then allow the formatting tags an
        // editor legitimately produces.
        $content = preg_replace('#<(script|style|iframe)\b[^>]*>.*?</\1>#is', '', $content) ?? $content;

        return strip_tags($content, '<p><br><h2><h3><h4><strong><em><b><i><u><ul><ol><li><a><blockquote><table><thead><tbody><tr><th><td><hr>');
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function metaDescription(): string
    {
        return $this->meta_description
            ?: Str::limit(strip_tags((string) ($this->excerpt ?: $this->content)), 155);
    }

    public function llmSummary(): string
    {
        return $this->llm_summary
            ?: Str::limit(strip_tags((string) ($this->excerpt ?: $this->content)), 400);
    }

    public function translated(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $translations = $this->translations ?? [];

        return $translations[$locale][$field]
            ?? $translations['en'][$field]
            ?? $this->getAttribute($field);
    }
}
