<?php

namespace Rarq\FilamentQuickNotes;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Rarq\FilamentQuickNotes\Livewire\QuickNotes;

class FilamentQuickNotesPlugin implements Plugin
{
    protected bool | Closure $isVisible = true;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'filament-quick-notes';
    }

    /**
     * @param Panel $panel
     *
     * @return void
     */
    public function register(Panel $panel): void
    {
        $panel->renderHook(
            config('filament-quick-notes.position', PanelsRenderHook::GLOBAL_SEARCH_BEFORE),
            fn(): string => $this->isVisible() ? view('filament-quick-notes::render-hook')->render() : ''
        );
    }

    /**
     * @param Panel $panel
     *
     * @return void
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * @return static
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Control visibility — accepts a boolean or a Closure that returns bool.
     *
     * @param bool|Closure $condition
     *
     * @return static
     */
    public function visible(bool | Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    /**
     * @return bool
     */
    private function isVisible(): bool
    {
        return (bool) value($this->isVisible);
    }
}
