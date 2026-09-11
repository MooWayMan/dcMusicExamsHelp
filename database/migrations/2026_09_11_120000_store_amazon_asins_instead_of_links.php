<?php

// database/migrations/2026_09_11_120000_store_amazon_asins_instead_of_links.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Amazon affiliate tag used to be baked into every stored buy link, so
     * changing it meant rewriting thousands of rows (4 Aug 2026, twice). Store
     * the bare ASIN instead; App\Support\AmazonLink builds the link with the tag
     * from config.
     *
     * Every existing link is parsed BEFORE any column is dropped. A link in any
     * other shape throws, and Postgres rolls the whole migration back, so nothing
     * can be lost silently.
     */
    public function up(): void
    {
        Schema::table('syllabus_pieces', function (Blueprint $table) {
            $table->string('buy_asin', 10)->nullable()->after('buy_kind');
            $table->string('buy_alt_asin', 10)->nullable()->after('buy_edition');
        });

        DB::table('syllabus_pieces')
            ->where(fn ($q) => $q->whereNotNull('buy_url')->orWhereNotNull('buy_alt_url'))
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('syllabus_pieces')->where('id', $row->id)->update([
                        'buy_asin' => $this->asinFrom($row->buy_url, "syllabus_pieces #{$row->id} buy_url"),
                        'buy_alt_asin' => $this->asinFrom($row->buy_alt_url, "syllabus_pieces #{$row->id} buy_alt_url"),
                    ]);
                }
            });

        DB::table('syllabus_books')
            ->whereNotNull('buy_url')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $asin = $this->asinFrom($row->buy_url, "syllabus_books #{$row->id} buy_url");

                    if ($row->asin === null || $row->asin === '') {
                        DB::table('syllabus_books')->where('id', $row->id)->update(['asin' => $asin]);
                    } elseif ($row->asin !== $asin) {
                        throw new RuntimeException("syllabus_books #{$row->id}: asin {$row->asin} does not match its buy_url ({$row->buy_url}). Nothing was changed.");
                    }
                }
            });

        Schema::table('syllabus_pieces', function (Blueprint $table) {
            $table->dropColumn(['buy_url', 'buy_alt_url']);
        });

        Schema::table('syllabus_books', function (Blueprint $table) {
            $table->dropColumn('buy_url');
        });
    }

    public function down(): void
    {
        $tag = config('services.amazon.associates_tag');

        Schema::table('syllabus_books', function (Blueprint $table) {
            $table->string('buy_url')->nullable()->after('asin');
        });

        Schema::table('syllabus_pieces', function (Blueprint $table) {
            $table->string('buy_url')->nullable()->after('buy_kind');
            $table->string('buy_alt_url')->nullable()->after('buy_edition');
        });

        foreach ([['syllabus_books', 'asin', 'buy_url'], ['syllabus_pieces', 'buy_asin', 'buy_url'], ['syllabus_pieces', 'buy_alt_asin', 'buy_alt_url']] as [$tableName, $from, $to]) {
            DB::table($tableName)->whereNotNull($from)->orderBy('id')->chunkById(500, function ($rows) use ($tableName, $from, $to, $tag) {
                foreach ($rows as $row) {
                    DB::table($tableName)->where('id', $row->id)->update([
                        $to => 'https://www.amazon.co.uk/dp/'.$row->{$from}.'?tag='.$tag,
                    ]);
                }
            });
        }

        Schema::table('syllabus_pieces', function (Blueprint $table) {
            $table->dropColumn(['buy_asin', 'buy_alt_asin']);
        });
    }

    private function asinFrom(?string $url, string $where): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (! preg_match('#^https://www\.amazon\.co\.uk/dp/([A-Z0-9]{10})\?tag=[A-Za-z0-9-]+$#', $url, $m)) {
            throw new RuntimeException("{$where} is not a plain Amazon /dp/ link: {$url}. Nothing was changed.");
        }

        return $m[1];
    }
};
