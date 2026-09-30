<?php

namespace App\Http\Controllers\Storefront;

use App\Support\Settings;
use Illuminate\Http\Response;

/**
 * robots.txt, generated rather than static so the sitemap URL and any
 * administrator-chosen crawl rules stay correct across environments.
 *
 * The AI-crawler section is deliberate: HanbellShop wants its catalogue to be
 * discoverable by answer engines, so GPTBot, ClaudeBot, PerplexityBot and
 * friends are explicitly welcomed and pointed at /llms.txt. An operator can
 * flip that off in settings without editing a file on the server.
 */
class RobotsController
{
    public function index(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
        ];

        // Nothing under these paths is useful to a crawler, and the tokenised
        // URLs are ephemeral by design.
        foreach ([
            '/admin',
            '/vendor',
            '/account',
            '/cart',
            '/checkout',
            '/orders',
            '/webhooks',
            '/locale',
            '/two-factor-challenge',
            '/verify-email',
        ] as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        // Tokenised product/category URLs expire, so crawling every variant
        // wastes crawl budget; the sitemap is the canonical entry point.
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        if (config('hanbell.seo.llms_txt_enabled', true)) {
            $lines[] = '';
            $lines[] = '# Answer engines and language models are welcome to read the catalogue.';
            $lines[] = '# A structured summary is published at /llms.txt.';

            foreach ([
                'GPTBot',
                'OAI-SearchBot',
                'ChatGPT-User',
                'ClaudeBot',
                'Claude-Web',
                'anthropic-ai',
                'PerplexityBot',
                'Perplexity-User',
                'Google-Extended',
                'Applebot-Extended',
                'CCBot',
                'cohere-ai',
                'Bytespider',
                'Meta-ExternalAgent',
            ] as $agent) {
                $lines[] = '';
                $lines[] = 'User-agent: '.$agent;
                $lines[] = 'Allow: /';
                $lines[] = 'Disallow: /admin';
                $lines[] = 'Disallow: /vendor';
                $lines[] = 'Disallow: /account';
                $lines[] = 'Disallow: /cart';
                $lines[] = 'Disallow: /checkout';
                $lines[] = 'Disallow: /orders';
            }
        }

        // An operator-supplied block, appended verbatim.
        if ($extra = Settings::get('seo.robots_extra')) {
            $lines[] = '';
            $lines[] = (string) $extra;
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
