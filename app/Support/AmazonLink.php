<?php

// app/Support/AmazonLink.php

namespace App\Support;

/**
 * Builds every Amazon buy link on the site from a bare ASIN.
 *
 * The affiliate tag lives in config('services.amazon.associates_tag') and
 * nowhere else. The database and the seed files store ASINs only, so a tag
 * change is one config value and a deploy, never a data rewrite.
 * Guarded by tests/Feature/AmazonLinkTest.php.
 */
class AmazonLink
{
    public static function forAsin(?string $asin): ?string
    {
        if ($asin === null || $asin === '') {
            return null;
        }

        return 'https://www.amazon.co.uk/dp/'.$asin.'?tag='.config('services.amazon.associates_tag');
    }

    /**
     * Amazon search link (with the Associates tag) for a book we have no ASIN
     * for yet. Used by the Piece Finder instead of "not stocked on Amazon",
     * because most of those books ARE on Amazon, just never matched.
     */
    public static function forSearch(?string $terms): ?string
    {
        $terms = trim(preg_replace('/\s+/', ' ', (string) $terms));

        if ($terms === '') {
            return null;
        }

        return 'https://www.amazon.co.uk/s?k='.rawurlencode($terms).'&tag='.config('services.amazon.associates_tag');
    }
}
