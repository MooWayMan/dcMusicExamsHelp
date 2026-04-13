<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageMaintenance extends Model
{
    protected $table = 'page_maintenance';

    protected $fillable = [
        'page_slug',
        'page_name',
        'is_active',
        'message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Pages that can be put into maintenance mode.
     * Add new data-heavy pages here as they're built.
     */
    public const MAINTAINABLE_PAGES = [
        'recognition' => 'Recognition (Hall of Fame)',
        'exam-fees' => 'Exam Fees & Dates',
        'incentives' => 'Incentives & Awards',
    ];

    /**
     * Default maintenance message shown to visitors.
     */
    public const DEFAULT_MESSAGE = "We're updating this page with the latest data. Please check back shortly — everything else on the site is working as normal.";

    /**
     * Get all page slugs currently in maintenance mode.
     */
    public static function activeSlugs(): array
    {
        return static::where('is_active', true)->pluck('page_slug')->toArray();
    }

    /**
     * Check if a specific page is in maintenance.
     */
    public static function isDown(string $slug): bool
    {
        return static::where('page_slug', $slug)->where('is_active', true)->exists();
    }

    /**
     * Ensure all maintainable pages have a row in the database.
     * Called from the seeder or admin controller.
     */
    public static function seed(): void
    {
        foreach (static::MAINTAINABLE_PAGES as $slug => $name) {
            static::firstOrCreate(
                ['page_slug' => $slug],
                [
                    'page_name' => $name,
                    'is_active' => false,
                    'message' => static::DEFAULT_MESSAGE,
                ]
            );
        }
    }
}
