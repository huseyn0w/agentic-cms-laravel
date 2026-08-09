<?php

namespace App\Http\Models\CPanel;

use App\Http\Models\Concerns\FlushesSettingsSingletonCache;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

/**
 * GEO settings singleton (row id = 1).
 *
 * Mirrors CPanelSeoSettings: a single row, no timestamps, model-cached for
 * cheap reads on every front request (the JSON-LD + llms.txt builders read it).
 */
class CPanelGeoSettings extends Model
{
    use Cachable;
    use FlushesSettingsSingletonCache;

    public $timestamps = false;

    protected $table = 'geo_settings';

    protected $fillable = [
        'business_name',
        'business_type',
        'description',
        'founder_name',
        'services',
        'service_area',
        'contact_email',
        'contact_phone',
        'address',
        'same_as',
        'faq',
        'emit_jsonld',
        'include_in_llms',
    ];

    protected $casts = [
        'emit_jsonld' => 'boolean',
        'include_in_llms' => 'boolean',
    ];

    /**
     * Services as a clean array (one per line in the textarea).
     *
     * @return array<int, string>
     */
    public function servicesList(): array
    {
        return $this->linesToArray($this->services);
    }

    /**
     * sameAs / authority URLs as a clean array (one per line).
     *
     * @return array<int, string>
     */
    public function sameAsList(): array
    {
        return $this->linesToArray($this->same_as);
    }

    /**
     * FAQ as an array of ['question' => ..., 'answer' => ...] pairs.
     * Input format: one "Question | Answer" per line.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function faqList(): array
    {
        $pairs = [];

        foreach ($this->linesToArray($this->faq) as $line) {
            if (! str_contains($line, '|')) {
                continue;
            }

            [$question, $answer] = array_map('trim', explode('|', $line, 2));

            if ($question !== '' && $answer !== '') {
                $pairs[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $pairs;
    }

    /**
     * Split a textarea value into trimmed, non-empty lines.
     *
     * @return array<int, string>
     */
    private function linesToArray(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value))));
    }
}
