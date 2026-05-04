<div
    x-data="{
        toastMsg: '',
        toastVisible: false,
        toastTimer: null,

        confirmOpen: false,
        confirmNoteId: null,
        confirmNoteTitle: '',

        showToast(msg) {
            this.toastMsg = msg;
            this.toastVisible = true;
            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => this.toastVisible = false, 2400);
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
        else { $wire.closeModal(); }
    "
    class="fqn-root"
>
    @php
        $stickyNotes = array_values(array_filter($notes, static fn (array $note): bool => (bool) ($note['is_pinned'] ?? false)));
    @endphp

    @once
        <style>
            .fqn-autosave-hint-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-top: 14px;
                flex-wrap: wrap;
            }

            .fqn-mini-pill {
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(255, 255, 255, 0.06);
                color: rgba(255, 255, 255, 0.82);
                border-radius: 999px;
                padding: 4px 10px;
                font-family: var(--fqn-body);
                font-size: 11px;
                font-weight: 700;
                line-height: 1;
                cursor: pointer;
                transition: transform .16s, background .16s, color .16s;
                white-space: nowrap;
            }

            .fqn-mini-pill:hover {
                transform: translateY(-1px);
                background: rgba(255, 255, 255, 0.14);
            }

            .fqn-mini-pill.active {
                background: rgba(245, 197, 24, 0.18);
                color: #fcd34d;
                border-color: rgba(245, 197, 24, 0.22);
            }

            .fqn-sticky-stage {
                position: fixed;
                inset: 0;
                z-index: 9997;
                pointer-events: none;
            }

            .fqn-sticky-note {
                --fqn-sticky-bg: #F5C518;
                --fqn-sticky-text: rgba(0, 0, 0, 0.72);
                position: fixed;
                pointer-events: auto;
                display: flex;
                flex-direction: column;
                min-width: 240px;
                min-height: 180px;
                max-width: calc(100vw - 24px);
                max-height: calc(100vh - 96px);
                background: var(--fqn-sticky-bg);
                color: var(--fqn-sticky-text);
                border-radius: 18px;
                box-shadow: 0 24px 48px rgba(0, 0, 0, 0.34);
                overflow: hidden;
                resize: both;
                border: 1px solid rgba(255, 255, 255, 0.24);
                backdrop-filter: blur(4px);
                will-change: left, top, width, height;
            }

            .fqn-sticky-note.is-dragging {
                box-shadow: 0 30px 62px rgba(0, 0, 0, 0.42);
            }

            .fqn-sticky-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 12px 14px 10px;
                cursor: grab;
                user-select: none;
                touch-action: none;
                border-bottom: 1px dashed rgba(0, 0, 0, 0.18);
                background: rgba(255, 255, 255, 0.18);
            }

            .fqn-sticky-header:active {
                cursor: grabbing;
            }

            .fqn-sticky-headline {
                display: flex;
                align-items: center;
                gap: 8px;
                min-width: 0;
            }

            .fqn-sticky-dot {
                width: 9px;
                height: 9px;
                border-radius: 999px;
                background: rgba(0, 0, 0, 0.48);
                flex-shrink: 0;
            }

            .fqn-sticky-title {
                min-width: 0;
                font-family: var(--fqn-hand);
                font-size: 21px;
                font-weight: 700;
                line-height: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .fqn-sticky-actions {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-shrink: 0;
            }

            .fqn-sticky-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 5px 9px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.24);
                font-family: var(--fqn-body);
                font-size: 11px;
                font-weight: 700;
                line-height: 1;
            }

            .fqn-sticky-btn {
                width: 28px;
                height: 28px;
                border-radius: 999px;
                border: none;
                background: rgba(255, 255, 255, 0.22);
                color: inherit;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: transform .16s, background .16s;
            }

            .fqn-sticky-btn:hover {
                transform: translateY(-1px);
                background: rgba(255, 255, 255, 0.34);
            }

            .fqn-sticky-body {
                flex: 1;
                min-height: 0;
                overflow: auto;
                padding: 14px 16px 8px;
                font-family: var(--fqn-hand);
                font-size: 21px;
                line-height: 1.45;
                white-space: pre-wrap;
                word-break: break-word;
            }

            .fqn-sticky-footer {
                padding: 8px 14px 12px;
                border-top: 1px dashed rgba(0, 0, 0, 0.14);
                background: rgba(255, 255, 255, 0.12);
                font-family: var(--fqn-body);
                font-size: 11px;
                font-weight: 600;
                opacity: 0.68;
            }

            body.fqn-dragging-note,
            body.fqn-dragging-note * {
                user-select: none !important;
                cursor: grabbing !important;
            }
        </style>
        <script>
            window.fqnStickyNote = window.fqnStickyNote || function (config) {
                return {
                    noteId: Number(config.noteId),
                    x: Number(config.x ?? 24),
                    y: Number(config.y ?? 112),
                    width: Number(config.width ?? 300),
                    height: Number(config.height ?? 230),
                    dragging: false,
                    pointerOffsetX: 0,
                    pointerOffsetY: 0,
                    saveTimer: null,
                    resizeObserver: null,

                    init() {
                        this.$nextTick(() => {
                            this.width = this.normalizeWidth(this.$el.offsetWidth || this.width);
                            this.height = this.normalizeHeight(this.$el.offsetHeight || this.height);
                            this.clampToViewport();

                            this.resizeObserver = new ResizeObserver(() => {
                                const nextWidth = this.normalizeWidth(this.$el.offsetWidth || this.width);
                                const nextHeight = this.normalizeHeight(this.$el.offsetHeight || this.height);

                                if (nextWidth === this.width && nextHeight === this.height) {
                                    return;
                                }

                                this.width = nextWidth;
                                this.height = nextHeight;
                                this.clampToViewport();
                                this.queuePersist();
                            });

                            this.resizeObserver.observe(this.$el);

                            window.addEventListener('resize', () => {
                                this.clampToViewport();
                            }, { passive: true });
                        });
                    },

                    startDrag(event) {
                        if (event.target.closest('[data-fqn-action]')) {
                            return;
                        }

                        clearTimeout(this.saveTimer);
                        this.dragging = true;
                        this.$el.classList.add('is-dragging');
                        this.pointerOffsetX = event.clientX - this.x;
                        this.pointerOffsetY = event.clientY - this.y;
                        document.body.classList.add('fqn-dragging-note');
                        event.currentTarget.setPointerCapture?.(event.pointerId);
                        event.preventDefault();
                        event.stopPropagation();
                    },

                    move(event) {
                        if (! this.dragging) {
                            return;
                        }

                        this.x = Math.round(event.clientX - this.pointerOffsetX);
                        this.y = Math.round(event.clientY - this.pointerOffsetY);
                        this.clampToViewport();
                    },

                    stop() {
                        if (! this.dragging) {
                            return;
                        }

                        this.dragging = false;
                        this.$el.classList.remove('is-dragging');
                        document.body.classList.remove('fqn-dragging-note');
                        this.persist();
                    },

                    queuePersist() {
                        clearTimeout(this.saveTimer);
                        this.saveTimer = setTimeout(() => this.persist(), 240);
                    },

                    persist() {
                        this.$wire.updatePinnedLayout(
                            this.noteId,
                            Math.round(this.x),
                            Math.round(this.y),
                            Math.round(this.width),
                            Math.round(this.height),
                        );
                    },

                    normalizeWidth(value) {
                        const maxWidth = Math.max(240, window.innerWidth - 24);

                        return Math.min(Math.max(Math.round(value), 240), maxWidth);
                    },

                    normalizeHeight(value) {
                        const maxHeight = Math.max(180, window.innerHeight - 96);

                        return Math.min(Math.max(Math.round(value), 180), maxHeight);
                    },

                    clampToViewport() {
                        this.width = this.normalizeWidth(this.width);
                        this.height = this.normalizeHeight(this.height);

                        const maxX = Math.max(12, window.innerWidth - this.width - 12);
                        const maxY = Math.max(72, window.innerHeight - this.height - 12);

                        this.x = Math.min(Math.max(Math.round(this.x), 12), maxX);
                        this.y = Math.min(Math.max(Math.round(this.y), 72), maxY);
                    },

                    style() {
                        return {
                            left: `${this.x}px`,
                            top: `${this.y}px`,
                            width: `${this.width}px`,
                            height: `${this.height}px`,
                        };
                    },
                };
            };
        </script>
    @endonce

    @if (count($stickyNotes))
        <div class="fqn-sticky-stage" aria-live="polite">
            @foreach ($stickyNotes as $note)
                @php
                    $noteColor = collect($this->colors())->firstWhere('id', $note['color']) ?? $this->colors()[0];
                    $noteIdJs = is_int($note['id']) ? $note['id'] : "'" . $note['id'] . "'";
                @endphp
                <section
                    class="fqn-sticky-note"
                    wire:key="sticky-note-{{ $note['id'] }}"
                    wire:ignore.self
                    x-data="window.fqnStickyNote({
                        noteId: {{ (int) $note['id'] }},
                        x: {{ (int) ($note['position_x'] ?? 24) }},
                        y: {{ (int) ($note['position_y'] ?? 112) }},
                        width: {{ (int) ($note['width'] ?? 300) }},
                        height: {{ (int) ($note['height'] ?? 230) }},
                    })"
                    x-init="init()"
                    x-on:pointermove.window="move($event)"
                    x-on:pointerup.window="stop()"
                    x-on:pointercancel.window="stop()"
                    x-bind:style="style()"
                    style="--fqn-sticky-bg: {{ $noteColor['hex'] }}; --fqn-sticky-text: {{ $noteColor['text'] }};"
                >
                    <div class="fqn-sticky-header" x-on:pointerdown="startDrag($event)">
                        <div class="fqn-sticky-headline">
                            <span class="fqn-sticky-dot" aria-hidden="true"></span>
                            <span class="fqn-sticky-title">{{ $note['title'] ?: __('filament-quick-notes::translations.untitled') }}</span>
                        </div>
                        <div class="fqn-sticky-actions">
                            <span class="fqn-sticky-badge">{{ __('filament-quick-notes::translations.pinned') }}</span>
                            <button
                                type="button"
                                class="fqn-sticky-btn"
                                data-fqn-action
                                x-on:click="$wire.selectNote({{ $noteIdJs }}); $wire.openModal()"
                                title="{{ __('filament-quick-notes::translations.open_in_editor') }}"
                            >
                                <x-heroicon-o-pencil-square class="fqn-icon-xs" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                class="fqn-sticky-btn"
                                data-fqn-action
                                wire:click="unpinNote({{ $noteIdJs }})"
                                title="{{ __('filament-quick-notes::translations.unpin_note') }}"
                            >
                                <x-heroicon-m-x-mark class="fqn-icon-xs" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                    <div class="fqn-sticky-body">{{ filled($note['content']) ? $note['content'] : __('filament-quick-notes::translations.empty_note') }}</div>
                    <div class="fqn-sticky-footer">
                        <span>{{ __('filament-quick-notes::translations.drag_resize_hint') }}</span>
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    <button
        wire:click="openModal"
        wire:loading.attr="disabled"
        class="fqn-open-btn"
        title="{{ __('filament-quick-notes::translations.open_notes') }}"
    >
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
            x-on:click.self="$wire.closeModal()"
            style="display:none"
        >
            <div
                class="fqn-modal"
                x-show="shown"
                x-transition:enter="fqn-modal-enter"
                x-transition:enter-start="fqn-modal-enter-start"
                x-transition:enter-end="fqn-modal-enter-end"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('filament-quick-notes::translations.my_notes') }}"
            >
                <div class="fqn-modal-header">
                    <div class="fqn-modal-title">
                        <x-heroicon-s-clipboard-document-list class="fqn-title-icon" aria-hidden="true" />
                        {{ __('filament-quick-notes::translations.my_notes') }}
                    </div>
                    <div class="fqn-header-actions">
                        <div class="fqn-autosave-badge">
                            <x-heroicon-c-bolt class="fqn-btn-sm-icon" aria-hidden="true" />
                            <span>{{ __('filament-quick-notes::translations.autosave_enabled') }}</span>
                        </div>
                        <button
                            x-on:click="$wire.closeModal()"
                            class="fqn-close-btn"
                            title="{{ __('filament-quick-notes::translations.close') }}"
                            aria-label="{{ __('filament-quick-notes::translations.close') }}"
                        >
                            <x-heroicon-m-x-mark class="fqn-icon-sm" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <div class="fqn-modal-body">
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
                                            aria-pressed="{{ $color === $c['id'] ? 'true' : 'false' }}"
                                        >
                                            @if ($color === $c['id'])
                                                <x-heroicon-s-check class="fqn-chip-check" aria-hidden="true" />
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                                @php
                                    $currentColor = collect($this->colors())->firstWhere('id', $color) ?? $this->colors()[0];
                                    $activeNote = collect($notes)->firstWhere('id', $activeId);
                                    $isActivePinned = (bool) ($activeNote['is_pinned'] ?? false);
                                @endphp
                                <div class="fqn-posit-wrapper" style="background: {{ $currentColor['hex'] }};">
                                    <div class="fqn-posit-tape" aria-hidden="true"></div>
                                    <div class="fqn-posit-header">
                                        <input
                                            type="text"
                                            wire:model.live.debounce.500ms="title"
                                            class="fqn-posit-title-input"
                                            placeholder="{{ __('filament-quick-notes::translations.note_title_placeholder') }}"
                                            maxlength="60"
                                            style="color: {{ $currentColor['text'] }};"
                                        >
                                    </div>
                                    <textarea
                                        wire:model.live.debounce.700ms="content"
                                        class="fqn-posit-content-input"
                                        placeholder="{{ __('filament-quick-notes::translations.note_content_placeholder') }}"
                                        style="color: {{ $currentColor['text'] }};"
                                    ></textarea>
                                    <div class="fqn-posit-fold" aria-hidden="true"></div>
                                </div>
                                <div class="fqn-autosave-hint-row">
                                    <div class="fqn-autosave-hint">
                                        <x-heroicon-o-check-badge class="fqn-btn-sm-icon" aria-hidden="true" />
                                        <span>{{ __('filament-quick-notes::translations.autosave_hint') }}</span>
                                    </div>
                                    @if (is_int($activeId))
                                        <button
                                            type="button"
                                            class="fqn-mini-pill {{ $isActivePinned ? 'active' : '' }}"
                                            wire:click="{{ $isActivePinned ? 'unpinNote' : 'pinNote' }}({{ $activeId }})"
                                        >
                                            {{ $isActivePinned ? __('filament-quick-notes::translations.unpin_note') : __('filament-quick-notes::translations.pin_note') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="fqn-list-pane">
                        <div class="fqn-list-header">
                            <span class="fqn-list-title">
                                {{ __('filament-quick-notes::translations.notes') }} ({{ count($notes) }})
                            </span>
                            <button
                                wire:click="newNote"
                                class="fqn-add-btn"
                                title="{{ __('filament-quick-notes::translations.new_note') }}"
                                aria-label="{{ __('filament-quick-notes::translations.new_note') }}"
                            >
                                <x-heroicon-m-plus class="fqn-icon-sm" aria-hidden="true" />
                            </button>
                        </div>
                        <div class="fqn-notes-list">
                            @forelse ($notes as $note)
                                @php
                                    $noteColor = collect($this->colors())->firstWhere('id', $note['color']) ?? $this->colors()[0];
                                    $isActive = $activeId == $note['id'];
                                    $noteIdJs = is_int($note['id']) ? $note['id'] : "'" . $note['id'] . "'";
                                    $isUnsaved = ! is_int($note['id']);
                                @endphp
                                <div
                                    class="fqn-note-card {{ $isActive ? 'active' : '' }} {{ $isUnsaved ? 'unsaved' : '' }}"
                                    wire:key="note-{{ $note['id'] }}"
                                >
                                    <div class="fqn-note-card-band" style="background: {{ $noteColor['hex'] }};"></div>
                                    <button
                                        type="button"
                                        class="fqn-note-card-body"
                                        wire:click="selectNote({{ $noteIdJs }})"
                                        aria-label="{{ __('filament-quick-notes::translations.edit_note') }}: {{ $note['title'] }}"
                                    >
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
                                        <button
                                            type="button"
                                            class="fqn-mini-pill {{ ($note['is_pinned'] ?? false) ? 'active' : '' }}"
                                            wire:click="{{ ($note['is_pinned'] ?? false) ? 'unpinNote' : 'pinNote' }}({{ $noteIdJs }})"
                                        >
                                            {{ ($note['is_pinned'] ?? false) ? __('filament-quick-notes::translations.unpin_note') : __('filament-quick-notes::translations.pin_note') }}
                                        </button>
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
                                            title="{{ __('filament-quick-notes::translations.delete') }}"
                                        >
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
                    aria-modal="true"
                >
                    <div
                        class="fqn-confirm-box"
                        x-show="confirmOpen"
                        x-transition:enter="fqn-confirm-box-enter"
                        x-transition:enter-start="fqn-confirm-box-enter-start"
                        x-transition:enter-end="fqn-confirm-box-enter-end"
                        x-transition:leave="fqn-confirm-box-leave"
                        x-transition:leave-start="fqn-confirm-box-leave-start"
                        x-transition:leave-end="fqn-confirm-box-leave-end"
                    >
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
            </div>
        </div>
    @endif

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
        style="display:none"
    >
        <x-heroicon-s-check-circle class="fqn-toast-icon" aria-hidden="true" />
        <span x-text="toastMsg"></span>
    </div>
</div>
