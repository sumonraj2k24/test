<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\Request;

final class SeoController
{
    public function sitemap(Request $request, array $params = []): void
    {
        $urls = [
            ['/', 'weekly', '1.0'],
            ['/courses', 'daily', '0.9'],
            ['/live', 'daily', '0.6'],
            ['/contact', 'monthly', '0.4'],
            ['/teach', 'monthly', '0.5'],
        ];
        foreach (Database::select("SELECT slug, updated_at FROM courses WHERE status = 'published'") as $course) {
            $urls[] = ['/courses/' . $course['slug'], 'weekly', '0.8'];
        }
        foreach (Database::select("SELECT slug FROM pages WHERE status = 'published'") as $page) {
            $urls[] = ['/pages/' . $page['slug'], 'monthly', '0.3'];
        }
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as [$path, $freq, $priority]) {
            echo '<url><loc>' . e(url($path)) . '</loc><changefreq>' . $freq . '</changefreq><priority>' . $priority . '</priority></url>';
        }
        echo '</urlset>';
    }

    public function robots(Request $request, array $params = []): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /studio\nDisallow: /checkout\nSitemap: " . url('/sitemap.xml') . "\n";
    }
}
