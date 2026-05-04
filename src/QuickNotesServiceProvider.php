<?php

namespace Rarq\FilamentQuickNotes;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Rarq\FilamentQuickNotes\Livewire\QuickNotes;
use Rarq\FilamentQuickNotes\Testing\TestsFilamentQuickNotes;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuickNotesServiceProvider extends PackageServiceProvider
{
    /**
     * @var string
     */
    public static string $name = 'filament-quick-notes';

    /**
     * @var string
     */
    public static string $viewNamespace = 'filament-quick-notes';

    /**
     * @param Package $package
     *
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('rodrigoarq14/filament-quick-notes');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile($configFileName);
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    /**
     * @return void
     */
    public function packageRegistered(): void
    {
        //
    }

    /**
     * @return void
     */
    public function packageBooted(): void
    {
        // ── Register the CSS asset with Filament ───────────────────────────────
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // ── Register the Livewire component ────────────────────────────────────
        Livewire::component('filament-quick-notes.quick-notes', QuickNotes::class);

        // ── Testing mixin ──────────────────────────────────────────────────────
        Testable::mixin(new TestsFilamentQuickNotes);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return string|null
     */
    protected function getAssetPackageName(): ?string
    {
        return 'rarq/filament-quick-notes';
    }

    /**
     * Register the package CSS so Filament injects it automatically.
     *
     * @return array<\Filament\Support\Assets\Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('filament-quick-notes', __DIR__ . '/../resources/dist/quick-notes.css'),
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        $migrations = glob(__DIR__ . '/../database/migrations/*.php.stub') ?: [];

        sort($migrations);

        return array_map(
            fn (string $migration): string => basename($migration, '.php.stub'),
            $migrations,
        );
    }
}
