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
        foreach ($this->notes as $order => $note) {
            if (! is_int($note['id'])) {
                continue;
            }

            $this->user()->filamentQuickNotes()
                ->where('id', $note['id'])
                ->update(['order' => $order]);
        }

        $this->loadNotes();
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
