<?php

namespace Rarq\FilamentQuickNotes\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Rarq\FilamentQuickNotes\Models\FilamentQuickNote;

trait HasFilamentQuickNotes
{
    /**
     * @return MorphMany
     */
    public function filamentQuickNotes(): MorphMany
    {
        return $this
            ->morphMany(config('filament-quick-notes.quick_notes_model', FilamentQuickNote::class), 'user');
    }
}
