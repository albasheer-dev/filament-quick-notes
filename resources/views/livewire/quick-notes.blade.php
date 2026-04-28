<div
    x-data="{
        toastMsg: '',
        toastVisible: false,
        toastTimer: null,

        /* ── Delete confirm ── */
        confirmOpen: false,
        confirmNoteId: null,
        confirmNoteTitle: '',

        /* ── Unsaved-changes warning ── */
        warnOpen: false,

        showToast(msg) {
            this.toastMsg = msg;
            this.toastVisible = true;
            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => this.toastVisible = false, 2400);
        },

        askClose() {
            if ($wire.hasPendingChanges) {
                this.warnOpen = true;
            } else {
                $wire.closeModal();
            }
        },

        cancelClose() {
            this.warnOpen = false;
        },

        confirmClose() {
            this.warnOpen = false;
            $wire.closeModal();
        },

        askDelete(id, title) {
            this.confirmNoteId = id;
            this.confirmNoteTitle = title;
            this.confirmOpen = true;
        },

        cancelDelete() {
            this.confirmOpen = false;
            this.confirmNoteId = null;
            this.confirmNoteTitle = '';
        },

        confirmDelete() {
            if (this.confirmNoteId !== null) {
                $wire.deleteNote(this.confirmNoteId);
            }
            this.cancelDelete();
        }
    }"
    x-on:quick-notes-toast.window="showToast($event.detail.message)"
    x-on:keydown.escape.window="
        if (confirmOpen) { cancelDelete(); }
        else if (warnOpen) { cancelClose(); }
        else { askClose(); }
    "
    class="fqn-root">

    {{-- Trigger --}}
    <button
        wire:click="openModal"
        wire:loading.attr="disabled"
        class="fqn-open-btn"
        title="{{ __('filament-quick-notes::translations.open_notes') }}">
        <x-heroicon-o-pencil-square class="fqn-btn-icon" aria-hidden="true" />
        <span wire:loading.remove wire:target="openModal">{{ __('filament-quick-notes::translations.my_notes') }}</span>
        <span wire:loading wire:target="openModal" class="fqn-loading-dots">{{ __('filament-quick-notes::translations.loading') }}</span>
    </button>

    @if ($open)
    <div
        class="fqn-backdrop"
        x-data="{ shown: false }"
        x-init="$nextTick(() => shown = true)"
        x-show="shown"
        x-transition:enter="fqn-backdrop-enter"
        x-transition:enter-start="fqn-backdrop-enter-start"
        x-transition:enter-end="fqn-backdrop-enter-end"
        x-on:click.self="askClose()"
        style="display:none">
        <div
            class="fqn-modal"
            x-show="shown"
            x-transition:enter="fqn-modal-enter"
            x-transition:enter-start="fqn-modal-enter-start"
            x-transition:enter-end="fqn-modal-enter-end"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('filament-quick-notes::translations.my_notes') }}">

            {{-- Header --}}
            <div class="fqn-modal-header">
                <div class="fqn-modal-title">
                    <x-heroicon-s-clipboard-document-list class="fqn-title-icon" aria-hidden="true" />
                    {{ __('filament-quick-notes::translations.my_notes') }}
                </div>
                <div class="fqn-header-actions">
                    <button
                        wire:click="saveChanges"
                        wire:loading.attr="disabled"
                        x-bind:disabled="!$wire.hasPendingChanges"
                        x-bind:class="$wire.hasPendingChanges
                            ? 'fqn-save-changes-btn fqn-save-changes-btn--active'
                            : 'fqn-save-changes-btn fqn-save-changes-btn--idle'"
                        title="{{ __('filament-quick-notes::translations.save_changes') }}">
                        <span class="fqn-pending-dot" x-show="$wire.hasPendingChanges" style="display:none" aria-hidden="true"></span>
                        <x-heroicon-c-cloud-arrow-up class="fqn-btn-sm-icon" aria-hidden="true" />
                        <span wire:loading.remove wire:target="saveChanges">{{ __('filament-quick-notes::translations.save_changes') }}</span>
                        <span wire:loading wire:target="saveChanges">{{ __('filament-quick-notes::translations.saving') }}…</span>
                    </button>
                    <button
                        x-on:click="askClose()"
                        class="fqn-close-btn"
                        title="{{ __('filament-quick-notes::translations.close') }}"
                        aria-label="{{ __('filament-quick-notes::translations.close') }}">
                        <x-heroicon-m-x-mark class="fqn-icon-sm" aria-hidden="true" />
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="fqn-modal-body">

                {{-- Editor pane --}}
                <div class="fqn-editor-pane">
                    @if (! $editorOpen)
                    <div class="fqn-no-note-msg">
                        <x-heroicon-o-rectangle-stack class="fqn-empty-state-icon" aria-hidden="true" />
                        <div>{{ __('filament-quick-notes::translations.select_or_create') }}</div>
                    </div>
                    @else
                    <div class="fqn-editor">
                        <p class="fqn-color-label">{{ __('filament-quick-notes::translations.note_color') }}</p>
                        <div class="fqn-color-row">
                            @foreach ($this->colors() as $c)
                            <button
                                type="button"
                                wire:click="$set('color', '{{ $c['id'] }}')"
                                class="fqn-color-chip {{ $color === $c['id'] ? 'active' : '' }}"
                                style="background: {{ $c['hex'] }};"
                                title="{{ $c['label'] }}"
                                aria-label="{{ $c['label'] }}"
                                aria-pressed="{{ $color === $c['id'] ? 'true' : 'false' }}">
                                @if ($color === $c['id'])
                                <x-heroicon-s-check class="fqn-chip-check" aria-hidden="true" />
                                @endif
                            </button>
                            @endforeach
                        </div>
                        @php $currentColor = collect($this->colors())->firstWhere('id', $color) ?? $this->colors()[0]; @endphp
                        <div class="fqn-posit-wrapper" style="background: {{ $currentColor['hex'] }};">
                            <div class="fqn-posit-tape" aria-hidden="true"></div>
                            <div class="fqn-posit-header">
                                <input
                                    type="text"
                                    wire:model="title"
                                    class="fqn-posit-title-input"
                                    placeholder="{{ __('filament-quick-notes::translations.note_title_placeholder') }}"
                                    maxlength="60"
                                    style="color: {{ $currentColor['text'] }};">
                            </div>
                            <textarea
                                wire:model="content"
                                class="fqn-posit-content-input"
                                placeholder="{{ __('filament-quick-notes::translations.note_content_placeholder') }}"
                                style="color: {{ $currentColor['text'] }};"></textarea>
                            <div class="fqn-posit-fold" aria-hidden="true"></div>
                        </div>
                        <div class="fqn-save-row">
                            <button wire:click="stageNote" class="fqn-save-btn">
                                <x-heroicon-s-bookmark class="fqn-btn-sm-icon" aria-hidden="true" />
                                {{ __('filament-quick-notes::translations.save_note') }}
                            </button>
                            <button wire:click="discardEditor" class="fqn-discard-btn">
                                <x-heroicon-o-x-circle class="fqn-btn-sm-icon" aria-hidden="true" />
                                {{ __('filament-quick-notes::translations.discard') }}
                            </button>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- List pane --}}
                <div class="fqn-list-pane">
                    <div class="fqn-list-header">
                        <span class="fqn-list-title">
                            {{ __('filament-quick-notes::translations.notes') }} ({{ count($notes) }})
                        </span>
                        <button
                            wire:click="newNote"
                            class="fqn-add-btn"
                            title="{{ __('filament-quick-notes::translations.new_note') }}"
                            aria-label="{{ __('filament-quick-notes::translations.new_note') }}">
                            <x-heroicon-m-plus class="fqn-icon-sm" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="fqn-notes-list">
                        @forelse ($notes as $note)
                        @php
                            $noteColor = collect($this->colors())->firstWhere('id', $note['color']) ?? $this->colors()[0];
                            $isActive  = $activeId == $note['id'];
                            $noteIdJs  = is_int($note['id']) ? $note['id'] : "'" . $note['id'] . "'";
                            $isUnsaved = ! is_int($note['id']);
                        @endphp
                        <div
                            class="fqn-note-card {{ $isActive ? 'active' : '' }} {{ $isUnsaved ? 'unsaved' : '' }}"
                            wire:key="note-{{ $note['id'] }}">
                            <div class="fqn-note-card-band" style="background: {{ $noteColor['hex'] }};"></div>
                            <button
                                type="button"
                                class="fqn-note-card-body"
                                wire:click="selectNote({{ $noteIdJs }})"
                                aria-label="{{ __('filament-quick-notes::translations.edit_note') }}: {{ $note['title'] }}">
                                <div class="fqn-note-card-title-row">
                                    <span class="fqn-note-card-title">{{ $note['title'] ?: __('filament-quick-notes::translations.untitled') }}</span>
                                    @if ($isUnsaved)
                                    <span class="fqn-unsaved-badge" title="{{ __('filament-quick-notes::translations.unsaved') }}">
                                        <x-heroicon-s-ellipsis-horizontal class="fqn-unsaved-icon" aria-hidden="true" />
                                    </span>
                                    @endif
                                </div>
                                <div class="fqn-note-card-preview">{{ $note['content'] ?: '—' }}</div>
                            </button>
                            <div class="fqn-note-card-actions">
                                <div class="fqn-order-btns">
                                    <button class="fqn-icon-btn fqn-up-btn" wire:click="moveNote({{ $noteIdJs }}, 'up')" title="{{ __('filament-quick-notes::translations.move_up') }}">
                                        <x-heroicon-m-chevron-up class="fqn-icon-xs" aria-hidden="true" />
                                    </button>
                                    <button class="fqn-icon-btn fqn-down-btn" wire:click="moveNote({{ $noteIdJs }}, 'down')" title="{{ __('filament-quick-notes::translations.move_down') }}">
                                        <x-heroicon-m-chevron-down class="fqn-icon-xs" aria-hidden="true" />
                                    </button>
                                </div>
                                <button
                                    class="fqn-icon-btn fqn-del-btn"
                                    x-on:click="askDelete({{ $noteIdJs }}, {{ Js::from($note['title'] ?: __('filament-quick-notes::translations.untitled')) }})"
                                    title="{{ __('filament-quick-notes::translations.delete') }}">
                                    <x-heroicon-m-trash class="fqn-icon-xs" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="fqn-empty-list">
                            <x-heroicon-o-document-text class="fqn-empty-list-icon" aria-hidden="true" />
                            {{ __('filament-quick-notes::translations.no_notes') }}
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Sub-modal: Delete confirm --}}
            <div
                class="fqn-confirm-backdrop"
                x-show="confirmOpen"
                x-transition:enter="fqn-confirm-enter"
                x-transition:enter-start="fqn-confirm-enter-start"
                x-transition:enter-end="fqn-confirm-enter-end"
                x-transition:leave="fqn-confirm-leave"
                x-transition:leave-start="fqn-confirm-leave-start"
                x-transition:leave-end="fqn-confirm-leave-end"
                x-on:click.self="cancelDelete()"
                style="display:none"
                role="alertdialog"
                aria-modal="true">
                <div
                    class="fqn-confirm-box"
                    x-show="confirmOpen"
                    x-transition:enter="fqn-confirm-box-enter"
                    x-transition:enter-start="fqn-confirm-box-enter-start"
                    x-transition:enter-end="fqn-confirm-box-enter-end"
                    x-transition:leave="fqn-confirm-box-leave"
                    x-transition:leave-start="fqn-confirm-box-leave-start"
                    x-transition:leave-end="fqn-confirm-box-leave-end">
                    <div class="fqn-confirm-icon-wrap" aria-hidden="true">
                        <x-heroicon-s-trash class="fqn-confirm-icon" />
                    </div>
                    <div class="fqn-confirm-title">{{ __('filament-quick-notes::translations.confirm_delete_title') }}</div>
                    <p class="fqn-confirm-body">
                        {{ __('filament-quick-notes::translations.confirm_delete_body') }}
                        <span class="fqn-confirm-note-name" x-text="'«' + confirmNoteTitle + '»'"></span>
                    </p>
                    <div class="fqn-confirm-actions">
                        <button class="fqn-confirm-cancel-btn" x-on:click="cancelDelete()">
                            <x-heroicon-m-arrow-left class="fqn-btn-sm-icon" aria-hidden="true" />
                            {{ __('filament-quick-notes::translations.delete_cancel') }}
                        </button>
                        <button class="fqn-confirm-delete-btn" x-on:click="confirmDelete()">
                            <x-heroicon-m-trash class="fqn-btn-sm-icon" aria-hidden="true" />
                            {{ __('filament-quick-notes::translations.delete_confirm') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Sub-modal: Unsaved changes --}}
            <div
                class="fqn-confirm-backdrop"
                x-show="warnOpen"
                x-transition:enter="fqn-confirm-enter"
                x-transition:enter-start="fqn-confirm-enter-start"
                x-transition:enter-end="fqn-confirm-enter-end"
                x-transition:leave="fqn-confirm-leave"
                x-transition:leave-start="fqn-confirm-leave-start"
                x-transition:leave-end="fqn-confirm-leave-end"
                x-on:click.self="cancelClose()"
                style="display:none"
                role="alertdialog"
                aria-modal="true">
                <div
                    class="fqn-confirm-box fqn-warn-box"
                    x-show="warnOpen"
                    x-transition:enter="fqn-confirm-box-enter"
                    x-transition:enter-start="fqn-confirm-box-enter-start"
                    x-transition:enter-end="fqn-confirm-box-enter-end"
                    x-transition:leave="fqn-confirm-box-leave"
                    x-transition:leave-start="fqn-confirm-box-leave-start"
                    x-transition:leave-end="fqn-confirm-box-leave-end">
                    <div class="fqn-confirm-icon-wrap fqn-warn-icon-wrap" aria-hidden="true">
                        <x-heroicon-s-exclamation-triangle class="fqn-confirm-icon fqn-warn-icon" />
                    </div>
                    <div class="fqn-confirm-title">{{ __('filament-quick-notes::translations.warn_unsaved_title') }}</div>
                    <p class="fqn-confirm-body">{{ __('filament-quick-notes::translations.warn_unsaved_body') }}</p>
                    <div class="fqn-confirm-actions">
                        <button class="fqn-warn-stay-btn" x-on:click="cancelClose()">
                            <x-heroicon-m-arrow-left class="fqn-btn-sm-icon" aria-hidden="true" />
                            {{ __('filament-quick-notes::translations.warn_stay') }}
                        </button>
                        <button class="fqn-warn-discard-btn" x-on:click="confirmClose()">
                            <x-heroicon-m-x-mark class="fqn-btn-sm-icon" aria-hidden="true" />
                            {{ __('filament-quick-notes::translations.warn_discard') }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
    @endif

    {{-- Toast --}}
    <div
        x-show="toastVisible"
        x-transition:enter="fqn-toast-enter"
        x-transition:enter-start="fqn-toast-enter-start"
        x-transition:enter-end="fqn-toast-enter-end"
        x-transition:leave="fqn-toast-leave"
        x-transition:leave-start="fqn-toast-leave-start"
        x-transition:leave-end="fqn-toast-leave-end"
        class="fqn-toast"
        role="status"
        aria-live="polite"
        style="display:none">
        <x-heroicon-s-check-circle class="fqn-toast-icon" aria-hidden="true" />
        <span x-text="toastMsg"></span>
    </div>

</div>