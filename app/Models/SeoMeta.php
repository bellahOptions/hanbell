<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * SEO/LLM metadata for routes that have no model behind them — the homepage,
 * shop listing, cart, checkout, search and so on.
 */
class SeoMeta extends Model
{
    use HasUuid;

    protected $table = 'seo_meta';

    protected $fillable = [
        'route_name', 'path', 'title', 'description', 'keywords',
        'og_title', 'og_description', 'og_image_path', 'twitter_card',
        'canonical_url', 'robots', 'schema_json', 'llm_summary', 'llm_faq',
    ];

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'llm_faq' => 'array',
        ];
    }

    public function isIndexable(): bool
    {
        return ! str_contains((string) $this->robots, 'noindex');
    }

    public static function forRoute(?string $routeName): ?self
    {
        if (blank($routeName)) {
            return null;
        }

        return static::query()->where('route_name', $routeName)->first();
    }

    /** @return array<int,array{question:string,answer:string}> */
    public function faqs(): array
    {
        $faq = $this->llm_faq ?? [];

        return collect($faq)
            ->filter(fn ($item) => is_array($item) && filled($item['question'] ?? null))
            ->map(fn ($item) => [
                'question' => (string) $item['question'],
                'answer' => (string) ($item['answer'] ?? ''),
            ])
            ->values()
            ->all();
    }
}
