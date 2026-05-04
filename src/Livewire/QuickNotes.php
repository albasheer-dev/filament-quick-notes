<?php

namespace Rarq\FilamentQuickNotes\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Rarq\FilamentQuickNotes\Enums\DeletionType;
use Rarq\FilamentQuickNotes\Models\FilamentQuickNote;

class QuickNotes extends Component
{
    /**
     * Modal state
     * 
     * @var bool
     */
    public bool $open = false;

    /**
     * Notes collection
     * 
     * @var array<int, array<string, mixed>>
     * 
     */
    public array $notes = [];

    /**
     * Currently active note in the editor.
     * 
     * @var int|string|null
     *
     */
    public int|string|null $activeId = null;

    /**
     * @var string
     */
    public string $title = '';

    /**
     * @var string
     */
    public string $content = '';

    /**
     * @var string
     */
    public string $color = 'yellow';

    /**
     * @var bool
     */
    public bool $editorOpen = false;

    /**
     * Returns the full color palette.
     *
     * @return array<int, array{id: string, label: string, hex: string, text: string}>
     */
    public function colors(): array
    {
        return FilamentQuickNote::availableColors();
    }

    public function mount(): void
    {
        $this->loadNotes();
    }

    // ─── Modal ─────────────────────────────────────────────────────────────────

    /**
     * Open the modal and load the user's notes.
     * 
     * @return void
     */
    public function openModal(): void
    {
        $this->loadNotes();
        $this->open = true;
    }

    /**
     * Close the modal and reset the editor state.
     * 
     * @return void
     */
    public function closeModal(): void
    {
        $this->open = false;
        $this->editorOpen = false;
        $this->resetEditor();
    }

    /**
     * Load the user's notes from the database into the local $notes array.
     * 
     * @return void
     */
    private function loadNotes(): void
    {
        $this->notes = $this->user()
            ->filamentQuickNotes()
            ->orderBy('order')
            ->get([
                'id',
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
            ])
            ->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'color' => $n->color,
                'order' => $n->order,
                'is_pinned' => (bool) $n->is_pinned,
                'is_docked' => (bool) $n->is_docked,
                'dock_order' => $n->dock_order,
                'position_x' => $n->position_x,
                'position_y' => $n->position_y,
                'width' => $n->width,
                'height' => $n->height,
            ])
            ->values()
            ->toArray();
    }

    /**
     * @return Model
     */
    private function user(): Model
    {
        /** @var Model $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * Open the editor with empty fields for creating a new note.
     * 
     * @return void
     */
    public function newNote(): void
    {
        $this->user()->filamentQuickNotes()->increment('order');

        $note = $this->user()->filamentQuickNotes()->create([
            'title' => null,
            'content' => null,
            'color' => 'yellow',
            'order' => 0,
            'is_pinned' => false,
            'is_docked' => false,
            'dock_order' => 0,
        ]);

        $this->loadNotes();

        $this->activeId = $note->id;
        $this->title = '';
        $this->content = '';
        $this->color = 'yellow';
        $this->editorOpen = true;
    }


    /**
     * Load a note's content into the editor for viewing/editing.
     * 
     * @param int|string $id
     * 
     * @return void
     */
    public function selectNote(int|string $id): void
    {
        $note = collect($this->notes)->firstWhere('id', $id);

        if (! $note) {
            return;
        }

        $this->activeId = $note['id'];
        $this->title = $note['title'] ?? '';
        $this->content = $note['content'] ?? '';
        $this->color = $note['color'];
        $this->editorOpen = true;
    }

    /**
     * Reset the editor state to default.
     * 
     * @return void
     */
    private function resetEditor(): void
    {
        $this->activeId = null;
        $this->title = '';
        $this->content = '';
        $this->color = 'yellow';
        $this->editorOpen = false;
    }

    /**
     * Auto-save the currently active note when an editor field changes.
     *
     * @return void
     */
    public function updatedTitle(): void
    {
        $this->persistActiveNote();
    }

    /**
     * @return void
     */
    public function updatedContent(): void
    {
        $this->persistActiveNote();
    }

    /**
     * @return void
     */
    public function updatedColor(): void
    {
        $this->persistActiveNote();
    }

    /**
     * Persist the active note to the database immediately.
     *
     * @return void
     */
    private function persistActiveNote(): void
    {
        if (! is_int($this->activeId)) {
            return;
        }

        $title = blank(trim($this->title)) ? null : trim($this->title);
        $content = blank(trim((string) $this->content)) ? null : $this->content;
        $color = $this->color;

        $this->user()->filamentQuickNotes()
            ->where('id', $this->activeId)
            ->update([
                'title' => $title,
                'content' => $content,
                'color' => $color,
            ]);

        $this->notes = collect($this->notes)
            ->map(function (array $note) use ($title, $content, $color): array {
                if ($note['id'] !== $this->activeId) {
                    return $note;
                }

                return array_merge($note, [
                    'title' => $title,
                    'content' => $content,
                    'color' => $color,
                ]);
            })
            ->toArray();
    }

    /**
     * Remove a note from the staged list and delete from DB if already persisted.
     * 
     * @param int|string $id
     * 
     * @return void
     */
    public function deleteNote(int|string $id): void
    {
        if (is_int($id)) {
            $query = $this->user()->filamentQuickNotes()->where('id', $id);

            config('filament-quick-notes.deletion_type') === DeletionType::SOFT
                ? $query->delete()
                : $query->forceDelete();
        }

        if ($this->activeId === $id) {
            $this->resetEditor();
        }

        $this->loadNotes();
        $this->persistOrder();

        $this->dispatch('quick-notes-toast', message: __('filament-quick-notes::translations.note_deleted'));
    }

    /**
     * Pin a note to the viewport so it stays visible across pages.
     *
     * @param int|string $id
     *
     * @return void
     */
    public function pinNote(int|string $id): void
    {
        if (! is_int($id)) {
            return;
        }

        /** @var FilamentQuickNote|null $note */
        $note = $this->user()->filamentQuickNotes()->find($id);

        if (! $note) {
            return;
        }

        $layout = $this->defaultPinnedLayout($id, [
            'position_x' => $note->position_x,
            'position_y' => $note->position_y,
            'width' => $note->width,
            'height' => $note->height,
        ]);

        $this->user()->filamentQuickNotes()
            ->where('id', $id)
            ->update(array_merge($layout, [
                'is_pinned' => true,
                'is_docked' => false,
            ]));

        $this->syncNote($id, array_merge($layout, [
            'is_pinned' => true,
            'is_docked' => false,
        ]));

        $this->dispatch('quick-notes-toast', message: __('filament-quick-notes::translations.note_pinned'));
    }

    /**
     * Remove a note from the fixed viewport list.
     *
     * @param int|string $id
     *
     * @return void
     */
    public function unpinNote(int|string $id): void
    {
        if (! is_int($id)) {
            return;
        }

        $this->user()->filamentQuickNotes()
            ->where('id', $id)
            ->update(['is_pinned' => false]);

        $this->syncNote($id, ['is_pinned' => false]);

        $this->dispatch('quick-notes-toast', message: __('filament-quick-notes::translations.note_unpinned'));
    }

    /**
     * Dock a note in the left rail and shift the page content.
     *
     * @param int|string $id
     *
     * @return void
     */
    public function dockNote(int|string $id): void
    {
        if (! is_int($id)) {
            return;
        }

        $nextOrder = $this->nextDockOrder();

        $this->user()->filamentQuickNotes()
            ->where('id', $id)
            ->update([
                'is_docked' => true,
                'dock_order' => $nextOrder,
                'is_pinned' => false,
            ]);

        $this->syncNote($id, [
            'is_docked' => true,
            'dock_order' => $nextOrder,
            'is_pinned' => false,
        ]);

        $this->dispatch('quick-notes-toast', message: __('filament-quick-notes::translations.note_docked'));
    }

    /**
     * Remove a note from the dock rail.
     *
     * @param int|string $id
     *
     * @return void
     */
    public function undockNote(int|string $id): void
    {
        if (! is_int($id)) {
            return;
        }

        $this->user()->filamentQuickNotes()
            ->where('id', $id)
            ->update([
                'is_docked' => false,
                'dock_order' => 0,
            ]);

        $this->syncNote($id, [
            'is_docked' => false,
            'dock_order' => 0,
        ]);

        $this->normalizeDockOrder();

        $this->dispatch('quick-notes-toast', message: __('filament-quick-notes::translations.note_undocked'));
    }

    /**
     * Move a docked note up or down within the rail.
     *
     * @param int|string $id
     * @param string $direction
     *
     * @return void
     */
    public function moveDockedNote(int|string $id, string $direction): void
    {
        $dockedNotes = collect($this->notes)
            ->filter(fn (array $note): bool => (bool) ($note['is_docked'] ?? false))
            ->sortBy('dock_order')
            ->values();

        $idx = $dockedNotes->search(fn (array $note): bool => $note['id'] === $id);

        if ($idx === false) {
            return;
        }

        $newIdx = $direction === 'up' ? $idx - 1 : $idx + 1;

        if ($newIdx < 0 || $newIdx >= $dockedNotes->count()) {
            return;
        }

        [$dockedNotes[$idx], $dockedNotes[$newIdx]] = [$dockedNotes[$newIdx], $dockedNotes[$idx]];

        $dockedNotes->values()->each(function (array $note, int $order): void {
            if (! is_int($note['id'])) {
                return;
            }

            $this->user()->filamentQuickNotes()
                ->where('id', $note['id'])
                ->update(['dock_order' => $order]);

            $this->syncNote($note['id'], ['dock_order' => $order]);
        });
    }

    /**
     * Persist the fixed note layout after dragging or resizing.
     *
     * @param int|string $id
     * @param int $positionX
     * @param int $positionY
     * @param int $width
     * @param int $height
     *
     * @return void
     */
    public function updatePinnedLayout(
        int|string $id,
        int $positionX,
        int $positionY,
        int $width,
        int $height,
    ): void {
        if (! is_int($id)) {
            return;
        }

        $layout = [
            'position_x' => max(12, $positionX),
            'position_y' => max(12, $positionY),
            'width' => max(240, min(520, $width)),
            'height' => max(180, min(640, $height)),
        ];

        $this->user()->filamentQuickNotes()
            ->where('id', $id)
            ->where('is_pinned', true)
            ->update($layout);

        $this->syncNote($id, $layout);
    }

    /**
     * Move a note up or down in the list.
     * 
     * @param int|string $id
     * @param string $direction
     * 
     * @return void
     */
    public function moveNote(int|string $id, string $direction): void
    {
        $notes = collect($this->notes)->values();
        $idx = $notes->search(fn(array $n): bool => $n['id'] === $id);

        if ($idx === false) {
            return;
        }

        $newIdx = $direction === 'up' ? $idx - 1 : $idx + 1;

        if ($newIdx < 0 || $newIdx >= $notes->count()) {
            return;
        }

        [$notes[$idx], $notes[$newIdx]] = [$notes[$newIdx], $notes[$idx]];

        $this->notes = $notes->values()->toArray();
        $this->persistOrder();
    }

    /**
     * Persist the current in-memory note order to the database.
     *
     * @return void
     */
    private function persistOrder(): void
    {
        $this->notes = collect($this->notes)
            ->values()
            ->map(function (array $note, int $order): array {
                if (! is_int($note['id'])) {
                    return array_merge($note, ['order' => $order]);
                }

                $this->user()->filamentQuickNotes()
                    ->where('id', $note['id'])
                    ->update(['order' => $order]);

                return array_merge($note, ['order' => $order]);
            })
            ->toArray();
    }

    /**
     * @param int $noteId
     * @param array<string, mixed> $attributes
     *
     * @return void
     */
    private function syncNote(int $noteId, array $attributes): void
    {
        $this->notes = collect($this->notes)
            ->map(function (array $note) use ($noteId, $attributes): array {
                if ($note['id'] !== $noteId) {
                    return $note;
                }

                return array_merge($note, $attributes);
            })
            ->values()
            ->toArray();
    }

    /**
     * @return int
     */
    private function nextDockOrder(): int
    {
        return collect($this->notes)
            ->filter(fn (array $note): bool => (bool) ($note['is_docked'] ?? false))
            ->max('dock_order') + 1;
    }

    /**
     * @return void
     */
    private function normalizeDockOrder(): void
    {
        collect($this->notes)
            ->filter(fn (array $note): bool => (bool) ($note['is_docked'] ?? false))
            ->sortBy('dock_order')
            ->values()
            ->each(function (array $note, int $order): void {
                if (! is_int($note['id'])) {
                    return;
                }

                $this->user()->filamentQuickNotes()
                    ->where('id', $note['id'])
                    ->update(['dock_order' => $order]);

                $this->syncNote($note['id'], ['dock_order' => $order]);
            });
    }

    /**
     * @param int $noteId
     * @param array<string, mixed> $currentLayout
     *
     * @return array<string, int>
     */
    private function defaultPinnedLayout(int $noteId, array $currentLayout): array
    {
        $pinnedCount = collect($this->notes)
            ->filter(fn (array $note): bool => $note['id'] !== $noteId && (bool) ($note['is_pinned'] ?? false))
            ->count();

        return [
            'position_x' => $currentLayout['position_x'] ?? (24 + (($pinnedCount % 4) * 34)),
            'position_y' => $currentLayout['position_y'] ?? (112 + (($pinnedCount % 5) * 28)),
            'width' => $currentLayout['width'] ?? 300,
            'height' => $currentLayout['height'] ?? 230,
        ];
    }

    /**
     * Render the Livewire component.
     * 
     * @return View
     */
    public function render(): View
    {
        return view('filament-quick-notes::livewire.quick-notes');
    }
}
