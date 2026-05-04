<?php

namespace Rarq\FilamentQuickNotes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FilamentQuickNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'user_type',
        'title',
        'content',
        'color',
        'order',
        'is_pinned',
        'is_docked',
        'dock_order',
        'position_x',
        'position_y',
        'width',
        'height',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_pinned' => 'boolean',
        'is_docked' => 'boolean',
        'dock_order' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        $config = config('filament-quick-notes');

        if (isset($config['table_name'])) {
            $this->setTable($config['table_name']);
        }

        parent::__construct($attributes);
    }

    /**
     * @return MorphTo
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Available colors.
     *
     * @return array<int, array{id: string, label: string, hex: string, text: string}>
     */
    public static function availableColors(): array
    {
        return [
            ['id' => 'yellow', 'label' => __('filament-quick-notes::translations.colors.yellow'), 'hex' => '#F5C518', 'text' => 'rgba(0,0,0,0.72)'],
            ['id' => 'sky', 'label' => __('filament-quick-notes::translations.colors.sky'), 'hex' => '#7EDDD6', 'text' => 'rgba(0,0,0,0.72)'],
            ['id' => 'blue', 'label' => __('filament-quick-notes::translations.colors.blue'), 'hex' => '#4A7FDB', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'white', 'label' => __('filament-quick-notes::translations.colors.white'), 'hex' => '#F9F7F2', 'text' => 'rgba(0,0,0,0.72)'],
            ['id' => 'black', 'label' => __('filament-quick-notes::translations.colors.black'), 'hex' => '#2A2A2A', 'text' => 'rgba(255,255,255,0.8)'],
            ['id' => 'orange', 'label' => __('filament-quick-notes::translations.colors.orange'), 'hex' => '#F07B3F', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'indigo', 'label' => __('filament-quick-notes::translations.colors.indigo'), 'hex' => '#6A5ACD', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'green', 'label' => __('filament-quick-notes::translations.colors.green'), 'hex' => '#5BBD72', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'red', 'label' => __('filament-quick-notes::translations.colors.red'), 'hex' => '#FF4C4C', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'purple', 'label' => __('filament-quick-notes::translations.colors.purple'), 'hex' => '#9B59B6', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'pink', 'label' => __('filament-quick-notes::translations.colors.pink'), 'hex' => '#FF69B4', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'teal', 'label' => __('filament-quick-notes::translations.colors.teal'), 'hex' => '#008080', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'brown', 'label' => __('filament-quick-notes::translations.colors.brown'), 'hex' => '#A52A2A', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'gray', 'label' => __('filament-quick-notes::translations.colors.gray'), 'hex' => '#808080', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'cyan', 'label' => __('filament-quick-notes::translations.colors.cyan'), 'hex' => '#00FFFF', 'text' => 'rgba(0,0,0,0.72)'],
            ['id' => 'magenta', 'label' => __('filament-quick-notes::translations.colors.magenta'), 'hex' => '#FF00FF', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'lime', 'label' => __('filament-quick-notes::translations.colors.lime'), 'hex' => '#00FF00', 'text' => 'rgba(0,0,0,0.72)'],
            ['id' => 'navy', 'label' => __('filament-quick-notes::translations.colors.navy'), 'hex' => '#000080', 'text' => 'rgba(255,255,255,0.85)'],
            ['id' => 'silver', 'label' => __('filament-quick-notes::translations.colors.silver'), 'hex' => '#C0C0C0', 'text' => 'rgba(0,0,0,0.72)'],
        ];
    }
}
