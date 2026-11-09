<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Locale;

/**
 * Countries for the "Popular Destinations" menu and the /country/{slug} listing.
 *
 * There is no countries table: a country is a distinct `cities.country` value.
 */
class Destinations
{
    private const CACHE_KEY = 'destinations_countries';

    /**
     * URL slug of a country name ("South Korea" => "south-korea").
     */
    public static function slug(string $country): string
    {
        return strtolower(Text::slug($country));
    }

    /**
     * Distinct countries, ordered by name.
     *
     * @return array<string, string> slug => country name
     */
    public static function all(): array
    {
        $cached = Cache::read(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $names = TableRegistry::getTableLocator()->get('Cities')->find()
            ->select(['country'])
            ->distinct(['country'])
            ->orderBy(['country' => 'ASC'])
            ->all()
            ->extract('country')
            ->toList();

        $countries = [];
        foreach ($names as $name) {
            $name = trim((string)$name);
            if ($name !== '') {
                $countries[self::slug($name)] = $name;
            }
        }
        Cache::write(self::CACHE_KEY, $countries);

        return $countries;
    }

    /**
     * Display name: countries are stored in capitals ("SOUTH KOREA" => "South Korea").
     */
    public static function label(string $country): string
    {
        return mb_convert_case(mb_strtolower(trim($country)), MB_CASE_TITLE);
    }

    /**
     * ISO 3166-1 alpha-2 code (lowercase) for a country name, for the flag image; null if unknown.
     * Matches the English region names from ext-intl, plus a few common short forms.
     */
    public static function isoCode(string $country): ?string
    {
        static $byName = null;
        $aliases = [
            'hong kong' => 'hk', 'macau' => 'mo', 'macao' => 'mo', 'korea' => 'kr', 'usa' => 'us',
            'united states of america' => 'us', 'uk' => 'gb', 'england' => 'gb', 'uae' => 'ae',
            'russia' => 'ru', 'czech republic' => 'cz', 'turkey' => 'tr', 'myanmar' => 'mm', 'burma' => 'mm',
        ];
        $name = mb_strtolower(trim($country));
        if (isset($aliases[$name])) {
            return $aliases[$name];
        }
        if ($byName === null) {
            $byName = [];
            if (class_exists(Locale::class)) {
                foreach (range('A', 'Z') as $a) {
                    foreach (range('A', 'Z') as $b) {
                        $region = Locale::getDisplayRegion('-' . $a . $b, 'en');
                        // Only codes we have a flag for (skips retired codes like VD, which ICU also calls "Vietnam").
                        if ($region !== '' && $region !== $a . $b && self::hasFlag(strtolower($a . $b))) {
                            $byName[mb_strtolower($region)] ??= strtolower($a . $b);
                        }
                    }
                }
            }
        }
        if (isset($byName[$name])) {
            return $byName[$name];
        }
        // "Hong Kong SAR China", "Myanmar (Burma)": accept a name the region name starts with.
        foreach ($byName as $region => $code) {
            if (str_starts_with($region, $name . ' ')) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Flag image path (webroot-relative), or null when there is none.
     */
    public static function flagPath(?string $code): ?string
    {
        return $code !== null && self::hasFlag($code) ? '/plugins/flag-icons/4x3/' . $code . '.svg' : null;
    }

    /**
     * Whether a flag SVG exists for a lowercase alpha-2 code.
     */
    private static function hasFlag(string $code): bool
    {
        return preg_match('/^[a-z]{2}$/', $code) === 1
            && is_file(WWW_ROOT . 'plugins' . DS . 'flag-icons' . DS . '4x3' . DS . $code . '.svg');
    }

    /**
     * Navbar menu entries, cached with the country list.
     *
     * @return array<string, array{name: string, label: string, flag: string|null}> slug => entry
     */
    public static function menu(): array
    {
        $cached = Cache::read(self::CACHE_KEY . '_menu');
        if (is_array($cached)) {
            return $cached;
        }
        $menu = [];
        foreach (self::all() as $slug => $name) {
            $menu[$slug] = [
                'name' => $name,
                'label' => self::label($name),
                'flag' => self::flagPath(self::isoCode($name)),
            ];
        }
        Cache::write(self::CACHE_KEY . '_menu', $menu);

        return $menu;
    }

    /**
     * Country name for a slug, or null when unknown.
     */
    public static function findBySlug(string $slug): ?string
    {
        return self::all()[strtolower($slug)] ?? null;
    }

    /**
     * Forget the cached list (e.g. after cities change).
     */
    public static function clearCache(): void
    {
        Cache::delete(self::CACHE_KEY);
        Cache::delete(self::CACHE_KEY . '_menu');
    }
}
