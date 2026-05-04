# Filament Quick Notes
<div class="filament-hidden">

![Header](https://raw.githubusercontent.com/rodrigoarq14/filament-quick-notes/main/.github/images/cover.webp)

</div>

[![Latest Version](https://img.shields.io/packagist/v/rarq/filament-quick-notes?style=for-the-badge)](https://packagist.org/packages/rarq/filament-quick-notes)
[![Downloads](https://img.shields.io/packagist/dt/rarq/filament-quick-notes?style=for-the-badge)](https://packagist.org/packages/rarq/filament-quick-notes)
[![PHP Version](https://img.shields.io/packagist/php-v/rarq/filament-quick-notes?style=for-the-badge)](https://packagist.org/packages/rarq/filament-quick-notes)
[![Filament](https://img.shields.io/badge/Filament-3%20%7C%204%20%7C%205-F59E0B?style=for-the-badge)](https://github.com/filamentphp/filament)
[![License](https://img.shields.io/packagist/l/rarq/filament-quick-notes?style=for-the-badge)](https://packagist.org/packages/rarq/filament-quick-notes)

A FilamentPHP panel plugin that adds a persistent sticky-note system to your admin panel. Users can create, edit, color-code, reorder and manage personal notes directly from the panel topbar — without leaving the page they are working on.

## Preview

![Screenshot 1](https://raw.githubusercontent.com/rodrigoarq14/filament-quick-notes/main/.github/images/screenshot-1.webp)
<br>
![Screenshot 2](https://raw.githubusercontent.com/rodrigoarq14/filament-quick-notes/main/.github/images/screenshot-2.webp)
<br>

## Features

- 🗒️ Personal notes per authenticated user
- 💾 Auto-save while you type
- 📌 Pin notes as floating sticky notes that stay visible across pages
- 🧲 Dock selected notes into a persistent left rail
- ↔️ Drag, resize, and reorder notes for your own workflow
- 🎨 Multiple color choices for visual organization
- 🧩 Polymorphic relation — works with numeric, UUID, or ULID user keys
- 🌐 Multilingual support

## Compatibility

| Filament Version | Laravel Version | PHP Version |
|------------------|-----------------|-------------|
| v3.x             | Laravel 10      | 8.1 – 8.3   |
| v4.x             | Laravel 11      | 8.2 – 8.4   |
| v5.x             | Laravel 11–12   | 8.2 – 8.5   |

> PHP compatibility is determined by the supported Laravel version.

## Installation

Install the package via Composer:

```bash
composer require rarq/filament-quick-notes
```

Run the install command:

```bash
php artisan filament-quick-notes:install
```

This will publish the config file, publish the migrations, and optionally run them.

If your authenticated user model uses UUID or ULID primary keys, publish the config, set `user_morph_key_type`, and then run the migration.
If you use the install command above, simply answer `no` when it asks to run migrations, update the config, and then run:

```bash
php artisan migrate
```

## Setup

### 1. Register the plugin in your Panel Provider

Open the `PanelProvider` where you want the Quick Notes button to appear and register the plugin:

```php
use Rarq\FilamentQuickNotes\FilamentQuickNotesPlugin;

$panel->plugins([
    FilamentQuickNotesPlugin::make()
        ->visible(true),
]);
```

### 2. Add the trait to your User model

Add the `HasFilamentQuickNotes` trait to the Eloquent model that represents the authenticated user of your panel:

```php
use Rarq\FilamentQuickNotes\Traits\HasFilamentQuickNotes;

class User extends Authenticatable
{
    use HasFilamentQuickNotes;
}
```

That's all. The Quick Notes button will appear in the panel topbar immediately.

## Configuration

After installation, a config file is published to `config/filament-quick-notes.php`:

```php
<?php

use Filament\View\PanelsRenderHook;
use Rarq\FilamentQuickNotes\Enums\DeletionType;
use Rarq\FilamentQuickNotes\Models\FilamentQuickNote;

return [
    /*
    |--------------------------------------------------------------------------
    | Render hook position
    |--------------------------------------------------------------------------
    |
    | Determines where the Quick Notes button appears inside the Filament panel.
    | Any value from \Filament\View\PanelsRenderHook is valid.
    |
    | Common options:
    |   PanelsRenderHook::GLOBAL_SEARCH_BEFORE  (default — left of the search bar)
    |   PanelsRenderHook::GLOBAL_SEARCH_AFTER
    |   PanelsRenderHook::TOPBAR_START
    |   PanelsRenderHook::TOPBAR_END
    |
    */
    'position' => PanelsRenderHook::GLOBAL_SEARCH_BEFORE,

    /*
    |--------------------------------------------------------------------------
    | Database table name
    |--------------------------------------------------------------------------
    |
    | Override this if the default table name conflicts with your existing schema.
    |
    */
    'table_name' => 'filament_quick_notes',

    /*
    |--------------------------------------------------------------------------
    | User morph key type
    |--------------------------------------------------------------------------
    |
    | Determines which morph column type is used for the authenticated user
    | relation in the package migration.
    |
    | Supported values:
    |   - 'numeric' (default) => $table->morphs('user')
    |   - 'uuid'              => $table->uuidMorphs('user')
    |   - 'ulid'              => $table->ulidMorphs('user')
    |
    | If your authenticatable model uses UUID or ULID primary keys, set this
    | value before running the package migration.
    |
    */
    'user_morph_key_type' => 'numeric',

    /*
    |--------------------------------------------------------------------------
    | Quick Notes model
    |--------------------------------------------------------------------------
    |
    | You may swap in your own model as long as it extends FilamentQuickNote
    | or implements the same interface / fillable attributes.
    |
    */
    'quick_notes_model' => FilamentQuickNote::class,

    /*
    |--------------------------------------------------------------------------
    | Note deletion method
    |--------------------------------------------------------------------------
    | Determines how notes are deleted when the user clicks "Delete".
    | Options:
    |   - DeletionType::SOFT (default) — notes are soft-deleted and can be restored later.
    |   - DeletionType::FORCE — notes are permanently deleted immediately.
    |
    */
    'deletion_type' => DeletionType::SOFT,
];
```

## Plugin Options

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `visible()` | `bool\|Closure` | `true` | Show or hide the Quick Notes button. Accepts a closure for conditional visibility. |

### Conditional visibility example

```php
FilamentQuickNotesPlugin::make()
    ->visible(fn () => auth()->user()?->hasRole('admin')),
```

## How it works

Notes are saved **per authenticated user** through a polymorphic relation, so the plugin works with any user model — including multi-tenancy setups where different models represent different user types.

Editing is auto-saved while the user types, so notes stay in sync without a separate save step.

Users can also:

1. **Pin** a note to keep it floating above the panel while they navigate.
2. **Dock** selected notes into a left-side rail that keeps the note visible while freeing the main workspace.
3. **Resize and drag** sticky notes to match the way they prefer to work.

## Contributing

Contributions are welcome! Please open an issue before submitting a pull request.

## Credits

- [Rodrigo A. Ríos Q.](https://github.com/rodrigoarq14)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
