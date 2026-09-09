<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * @var array<string, array{title_key: string, file: string}>
     */
    private const DOCUMENTS = [
        'terminos' => [
            'title_key' => 'ui.legal.terms_title',
            'file' => 'terminos.md',
        ],
        'privacidad' => [
            'title_key' => 'ui.legal.privacy_title',
            'file' => 'privacidad.md',
        ],
    ];

    public function terms(): Response
    {
        return $this->show('terminos');
    }

    public function privacy(): Response
    {
        return $this->show('privacidad');
    }

    private function show(string $document): Response
    {
        abort_unless(array_key_exists($document, self::DOCUMENTS), 404);

        $meta = self::DOCUMENTS[$document];
        $locale = in_array(app()->getLocale(), ['es', 'en'], true) ? app()->getLocale() : 'es';
        $path = resource_path('content/legal/'.$locale.'/'.$meta['file']);

        if (! File::exists($path)) {
            $path = resource_path('content/legal/es/'.$meta['file']);
        }

        $markdown = File::get($path);

        return Inertia::render('Legal/Show', [
            'slug' => $document,
            'title' => __($meta['title_key']),
            'updated' => __('ui.legal.updated_at'),
            'html' => $this->markdownWithHeadingIds($markdown),
            'toc' => $this->tableOfContents($markdown),
        ]);
    }

    private function markdownWithHeadingIds(string $markdown): string
    {
        $html = Str::markdown($markdown);

        return (string) preg_replace_callback(
            '/<h2>(.*?)<\/h2>/s',
            fn (array $match) => sprintf(
                '<h2 id="%s">%s</h2>',
                Str::slug(html_entity_decode(strip_tags($match[1]), ENT_QUOTES, 'UTF-8')),
                $match[1],
            ),
            $html,
        );
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private function tableOfContents(string $markdown): array
    {
        preg_match_all('/^##\s+(.+)$/m', $markdown, $matches);

        return array_values(collect($matches[1])
            ->map(fn (string $heading) => [
                'id' => Str::slug($heading),
                'label' => $heading,
            ])
            ->values()
            ->all());
    }
}
