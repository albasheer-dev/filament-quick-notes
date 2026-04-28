<?php

namespace Rarq\FilamentQuickNotes\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
     * @var bool
     */
    public bool $hasPendingChanges = false;

    /**
     * Returns the full color palette.
     *
     * @return array<int, array{id: string, label: string, hex: string, text: string}>
     */
    public function colors(): array
    {
        return FilamentQuickNote::availableColors();
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
        $this->hasPendingChanges = false;
        $this->resetEditor();
    }

    /**
     * Load the user's notes from the database into the local $notes array.
     * 
     * @return void
     */
    private function loadNotes(): void
    {
        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user = Auth::user();

        $this->notes = $user
            ->filamentQuickNotes()
            ->orderBy('order')
            ->get(['id', 'title', 'content', 'color', 'order'])
            ->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'color' => $n->color,
                'order' => $n->order
            ])
            ->values()
            ->toArray();
    }

    /**
     * Open the editor with empty fields for creating a new note.
     * 
     * @return void
     */
    public function newNote(): void
    {
        $tempId = Str::uuid()->toString();
        $defaultTitle = __('filament-quick-notes::translations.untitled');

        // Prepend to the list so it appears at the top right away
        $this->notes = array_merge(
            [[
                'id' => $tempId,
                'title' => $defaultTitle,
                'content' => '',
                'color' => 'yellow',
                'order' => 0
            ]],
            $this->notes
        );

        // Open it in the editor with clean fields
        $this->activeId = $tempId;
        $this->title = ''; // empty so the placeholder shows
        $this->content = '';
        $this->color = 'yellow';
        $this->editorOpen = true;

        // New unsaved note → pending changes immediately
        $this->hasPendingChanges = true;
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
        $this->title = $note['title'];
        $this->content = $note['content'];
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
     * Stage a note in the local $notes array (no DB hit yet).
     * The user still needs to click "Save Changes" to persist permanently.
     * 
     * @return void
     */
    public function stageNote(): void
    {
        $title = trim($this->title) ?: __('filament-quick-notes::translations.untitled');
        $content = trim($this->content);
        $color = $this->color;

        if ($this->activeId !== null) {
            $this->notes = collect($this->notes)
                ->map(function (array $n) use ($title, $content, $color): array {
                    if ($n['id'] === $this->activeId) {
                        return array_merge($n, compact('title', 'content', 'color'));
                    }
                    return $n;
                })
                ->toArray();
        } else {
            $tempId = Str::uuid()->toString();
            $this->notes = array_merge(
                [[
                    'id' => $tempId,
                    'title' => $title,
                    'content' => $content,
                    'color' => $color,
                    'order' => 0,
                ]],
                $this->notes
            );
            $this->activeId = $tempId;
        }

        $this->hasPendingChanges = true;

        $this->resetEditor();
        $this->dispatch('quick-notes-toast', message: __('filament-quick-notes::translations.note_staged'));
    }

    /**
     * Persist ALL staged notes to the database.
     * 
     * @return void
     */
    public function saveChanges(): void
    {
        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user = Auth::user();

        $persistedIds = $user->filamentQuickNotes()->pluck('id')->toArray();

        $stagedPersistedIds = collect($this->notes)
            ->filter(fn(array $n): bool => is_int($n['id']))
            ->pluck('id')
            ->toArray();

        $toDelete = array_diff($persistedIds, $stagedPersistedIds);
        if ($toDelete) {
            config('filament-quick-notes.deletion_type') === DeletionType::SOFT
                ? $user->filamentQuickNotes()->whereIn('id', $toDelete)->delete()
                : $user->filamentQuickNotes()->whereIn('id', $toDelete)->forceDelete();
        }

        foreach ($this->notes as $order => $note) {
            $isNew = ! is_int($note['id']);

            if ($isNew) {
                $created = $user->filamentQuickNotes()
                    ->create([
                        'title' => $note['title'],
                        'content' => $note['content'],
                        'color' => $note['color'],
                        'order' => $order
                    ]);
                $this->notes[$order]['id'] = $created->id;
            } else {
                $user->filamentQuickNotes()
                    ->where('id', $note['id'])
                    ->update([
                        'title' => $note['title'],
                        'content' => $note['content'],
                        'color' => $note['color'],
                        'order' => $order
                    ]);
            }
        }

        $this->hasPendingChanges = false;

        $this->closeModal();
        $this->dispatch('quick-notes-toast', message: __('filament-quick-notes::translations.changes_saved'));
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
        $this->notes = collect($this->notes)
            ->filter(fn(array $n): bool => $n['id'] !== $id)
            ->values()
            ->toArray();

        if ($this->activeId === $id) {
            $this->resetEditor();
        }

        $this->hasPendingChanges = true;

        $this->dispatch('quick-notes-toast', message: __('filament-quick-notes::translations.note_deleted'));
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

        $this->hasPendingChanges = true;
    }

    /**
     * Discard the current editor state without saving.
     * 
     * @return void
     */
    public function discardEditor(): void
    {
        $this->resetEditor();
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
