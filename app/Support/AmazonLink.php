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
}
