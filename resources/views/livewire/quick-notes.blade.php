<div
    x-data="{
        toastMsg: '',
        toastVisible: false,
        toastTimer: null,

        confirmOpen: false,
        confirmNoteId: null,
        confirmNoteTitle: '',
        dockActive: false,
        dockCleanup: [],

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
        },

        initDockLayout(active) {
            this.dockActive = Boolean(active);
            this.applyDockLayout();
            this.registerDockLayoutListeners();
        },

        toggleDockLayout(active) {
            this.dockActive = Boolean(active);
            this.applyDockLayout();
        },

        applyDockLayout() {
            document.documentElement.classList.toggle('fqn-has-dock', this.dockActive);
            document.body?.classList.toggle('fqn-has-dock', this.dockActive);
        },

        registerDockLayoutListeners() {
            if (this.dockCleanup.length) {
                return;
            }

            const refresh = () => {
                this.$nextTick(() => this.applyDockLayout());
                requestAnimationFrame(() => this.applyDockLayout());
                setTimeout(() => this.applyDockLayout(), 80);
            };

            ['livewire:navigate', 'livewire:navigated', 'filament:navigated'].forEach((eventName) => {
                document.addEventListener(eventName, refresh);
                this.dockCleanup.push(() => document.removeEventListener(eventName, refresh));
            });
        },

        destroy() {
            this.dockCleanup.forEach((cleanup) => cleanup());
            this.dockCleanup = [];
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
        $dockedNotes = array_values(array_filter($notes, static fn (array $note): bool => (bool) ($note['is_docked'] ?? false)));
    @endphp

    @once
        <style>
            :root {
                --fqn-dock-width: 22rem;
            }

            html.fqn-has-dock .fi-main-ctn,
            body.fqn-has-dock .fi-main-ctn {
                width: calc(100vw - var(--fqn-dock-width)) !important;
                max-width: calc(100vw - var(--fqn-dock-width)) !important;
                margin-left: var(--fqn-dock-width);
                transition: none !important;
            }

            html.fqn-has-dock .fi-topbar nav,
            html.fqn-has-dock .fi-main,
            body.fqn-has-dock .fi-topbar nav,
            body.fqn-has-dock .fi-main {
                transition: none !important;
            }

            @media (max-width: 1279px) {
                html.fqn-has-dock .fi-main-ctn,
                body.fqn-has-dock .fi-main-ctn {
                    width: 100vw !important;
                    max-width: 100vw !important;
                    margin-left: 0;
                }
            }

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

            .fqn-dock-rail {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: var(--fqn-dock-width);
                z-index: 18;
                display: flex;
                flex-direction: column;
                background: linear-gradient(180deg, rgba(17, 24, 39, 0.96), rgba(12, 17, 28, 0.98));
                border-right: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.32);
                overflow: hidden;
                backdrop-filter: blur(12px);
            }

            .fqn-dock-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 16px 16px 12px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                color: #fff;
            }

            .fqn-dock-title {
                font-family: var(--fqn-hand);
                font-size: 24px;
                font-weight: 700;
                letter-spacing: .3px;
            }

            .fqn-dock-count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 28px;
                height: 28px;
                padding: 0 10px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.08);
                color: rgba(255, 255, 255, 0.84);
                font-family: var(--fqn-body);
                font-size: 12px;
                font-weight: 700;
            }

            .fqn-dock-list {
                flex: 1;
                overflow: auto;
                padding: 14px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .fqn-dock-card {
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 16px 30px rgba(0, 0, 0, 0.24);
                border: 1px solid rgba(255, 255, 255, 0.12);
            }

            .fqn-dock-card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 12px 14px 10px;
                background: rgba(255, 255, 255, 0.18);
                border-bottom: 1px dashed rgba(0, 0, 0, 0.16);
            }

            .fqn-dock-card-title {
                min-width: 0;
                font-family: var(--fqn-hand);
                font-size: 20px;
                font-weight: 700;
                line-height: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .fqn-dock-card-body {
                padding: 14px;
                font-family: var(--fqn-hand);
                font-size: 19px;
                line-height: 1.5;
                white-space: pre-wrap;
                word-break: break-word;
                min-height: 110px;
                max-height: 36vh;
                overflow: auto;
            }

            .fqn-dock-card-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                padding: 10px 12px 12px;
                background: rgba(255, 255, 255, 0.14);
                border-top: 1px dashed rgba(0, 0, 0, 0.12);
            }

            .fqn-dock-order {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            @media (max-width: 1279px) {
                .fqn-dock-rail {
                    width: min(88vw, var(--fqn-dock-width));
                    z-index: 45;
                }
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

            .fqn-sticky-handle {
                position: absolute;
                z-index: 3;
                pointer-events: auto;
                padding: 0;
                margin: 0;
                border: none;
                background: transparent;
                appearance: none;
            }

            .fqn-sticky-handle.top,
            .fqn-sticky-handle.bottom {
                left: 18px;
                right: 18px;
                height: 14px;
                cursor: ns-resize;
            }

            .fqn-sticky-handle.left,
            .fqn-sticky-handle.right {
                top: 18px;
                bottom: 18px;
                width: 14px;
                cursor: ew-resize;
            }

            .fqn-sticky-handle.top {
                top: 0;
            }

            .fqn-sticky-handle.bottom {
                bottom: 0;
            }

            .fqn-sticky-handle.left {
                left: 0;
            }

            .fqn-sticky-handle.right {
                right: 0;
            }

            .fqn-sticky-handle.top-left,
            .fqn-sticky-handle.top-right,
            .fqn-sticky-handle.bottom-left,
            .fqn-sticky-handle.bottom-right {
                width: 16px;
                height: 16px;
            }

            .fqn-sticky-handle.top-left {
                top: 0;
                left: 0;
                cursor: nwse-resize;
            }

            .fqn-sticky-handle.top-right {
                top: 0;
                right: 0;
                cursor: nesw-resize;
            }

            .fqn-sticky-handle.bottom-left {
                bottom: 0;
                left: 0;
                cursor: nesw-resize;
            }

            .fqn-sticky-handle.bottom-right {
                bottom: 0;
                right: 0;
                cursor: nwse-resize;
            }

            body.fqn-note-interacting,
            body.fqn-note-interacting * {
                user-select: none !important;
            }

            body.fqn-dragging-note,
            body.fqn-dragging-note * {
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
                    resizing: false,
                    resizeDirection: '',
                    pointerOffsetX: 0,
                    pointerOffsetY: 0,
                    pointerStartX: 0,
                    pointerStartY: 0,
                    startWidth: 0,
                    startHeight: 0,
                    startLeft: 0,
                    startTop: 0,
                    saveTimer: null,
                    viewportPadding: 12,

                    storageKey() {
                        return `filament-quick-notes:sticky:${window.location.host}:${this.noteId}`;
                    },

                    init() {
                        this.restoreLayout();

                        this.$nextTick(() => {
                            this.width = this.normalizeWidth(this.width || this.$el.offsetWidth || 300);
                            this.height = this.normalizeHeight(this.height || this.$el.offsetHeight || 230);
                            this.clampToViewport();
                            this.rememberLayout();

                            window.addEventListener('resize', () => {
                                this.clampToViewport();
                                this.rememberLayout();
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
                        document.body.classList.add('fqn-note-interacting', 'fqn-dragging-note');
                        event.currentTarget.setPointerCapture?.(event.pointerId);
                        event.preventDefault();
                        event.stopPropagation();
                    },

                    startResize(direction, event) {
                        clearTimeout(this.saveTimer);
                        this.resizing = true;
                        this.resizeDirection = direction;
                        this.$el.classList.add('is-dragging');
                        this.pointerStartX = event.clientX;
                        this.pointerStartY = event.clientY;
                        this.startWidth = this.width;
                        this.startHeight = this.height;
                        this.startLeft = this.x;
                        this.startTop = this.y;
                        document.body.classList.add('fqn-note-interacting');
                        event.currentTarget.setPointerCapture?.(event.pointerId);
                        event.preventDefault();
                        event.stopPropagation();
                    },

                    move(event) {
                        if (! this.dragging && ! this.resizing) {
                            return;
                        }

                        if (this.dragging) {
                            this.x = event.clientX - this.pointerOffsetX;
                            this.y = event.clientY - this.pointerOffsetY;
                            this.clampToViewport();
                            return;
                        }

                        this.resize(event);
                    },

                    resize(event) {
                        const dx = event.clientX - this.pointerStartX;
                        const dy = event.clientY - this.pointerStartY;

                        let nextWidth = this.startWidth;
                        let nextHeight = this.startHeight;
                        let nextLeft = this.startLeft;
                        let nextTop = this.startTop;

                        if (this.resizeDirection.includes('right')) {
                            nextWidth = this.normalizeWidth(this.startWidth + dx);
                        }

                        if (this.resizeDirection.includes('left')) {
                            nextWidth = this.normalizeWidth(this.startWidth - dx);
                            nextLeft = this.startLeft + (this.startWidth - nextWidth);
                        }

                        if (this.resizeDirection.includes('bottom')) {
                            nextHeight = this.normalizeHeight(this.startHeight + dy);
                        }

                        if (this.resizeDirection.includes('top')) {
                            nextHeight = this.normalizeHeight(this.startHeight - dy);
                            nextTop = this.startTop + (this.startHeight - nextHeight);
                        }

                        this.width = nextWidth;
                        this.height = nextHeight;
                        this.x = nextLeft;
                        this.y = nextTop;
                        this.clampToViewport();
                    },

                    stop() {
                        if (! this.dragging && ! this.resizing) {
                            return;
                        }

                        this.dragging = false;
                        this.resizing = false;
                        this.resizeDirection = '';
                        this.$el.classList.remove('is-dragging');
                        document.body.classList.remove('fqn-note-interacting', 'fqn-dragging-note');
                        this.rememberLayout();
                        this.persist();
                    },

                    queuePersist() {
                        clearTimeout(this.saveTimer);
                        this.saveTimer = setTimeout(() => this.persist(), 240);
                    },

                    persist() {
                        this.rememberLayout();
                        this.$wire.updatePinnedLayout(
                            this.noteId,
                            Math.round(this.x),
                            Math.round(this.y),
                            Math.round(this.width),
                            Math.round(this.height),
                        );
                    },

                    normalizeWidth(value) {
                        const maxWidth = Math.max(240, window.innerWidth - (this.viewportPadding * 2));

                        return Math.min(Math.max(value, 240), maxWidth);
                    },

                    normalizeHeight(value) {
                        const maxHeight = Math.max(180, window.innerHeight - (this.viewportPadding * 2));

                        return Math.min(Math.max(value, 180), maxHeight);
                    },

                    clampToViewport() {
                        this.width = this.normalizeWidth(this.width);
                        this.height = this.normalizeHeight(this.height);

                        const maxX = Math.max(this.viewportPadding, window.innerWidth - this.width - this.viewportPadding);
                        const maxY = Math.max(this.viewportPadding, window.innerHeight - this.height - this.viewportPadding);

                        this.x = Math.min(Math.max(this.x, this.viewportPadding), maxX);
                        this.y = Math.min(Math.max(this.y, this.viewportPadding), maxY);
                    },

                    restoreLayout() {
                        try {
                            const stored = window.localStorage.getItem(this.storageKey());

                            if (! stored) {
                                return;
                            }

                            const layout = JSON.parse(stored);

                            if (Number.isFinite(layout.x)) {
                                this.x = Number(layout.x);
                            }

                            if (Number.isFinite(layout.y)) {
                                this.y = Number(layout.y);
                            }

                            if (Number.isFinite(layout.width)) {
                                this.width = Number(layout.width);
                            }

                            if (Number.isFinite(layout.height)) {
                                this.height = Number(layout.height);
                            }
                        } catch (_) {
                        }
                    },

                    rememberLayout() {
                        try {
                            window.localStorage.setItem(this.storageKey(), JSON.stringify({
                                x: Math.round(this.x),
                                y: Math.round(this.y),
                                width: Math.round(this.width),
                                height: Math.round(this.height),
                            }));
                        } catch (_) {
                        }
                    },

                    style() {
                        return {
                            left: `${Math.round(this.x)}px`,
                            top: `${Math.round(this.y)}px`,
                            width: `${Math.round(this.width)}px`,
                            height: `${Math.round(this.height)}px`,
                        };
                    },
                };
            };
        </script>
    @endonce

    <div x-init="initDockLayout({{ count($dockedNotes) > 0 ? 'true' : 'false' }})" x-effect="toggleDockLayout({{ count($dockedNotes) > 0 ? 'true' : 'false' }})"></div>

    @if (count($dockedNotes))
        <aside class="fqn-dock-rail" aria-label="{{ __('filament-quick-notes::translations.docked_notes') }}">
            <div class="fqn-dock-header">
                <span class="fqn-dock-title">{{ __('filament-quick-notes::translations.docked_notes') }}</span>
                <span class="fqn-dock-count">{{ count($dockedNotes) }}</span>
            </div>

            <div class="fqn-dock-list">
                @foreach (collect($dockedNotes)->sortBy('dock_order') as $note)
                    @php
                        $noteColor = collect($this->colors())->firstWhere('id', $note['color']) ?? $this->colors()[0];
                        $noteIdJs = is_int($note['id']) ? $note['id'] : "'" . $note['id'] . "'";
                    @endphp
                    <section
                        class="fqn-dock-card"
                        wire:key="dock-note-{{ $note['id'] }}"
                        style="background: {{ $noteColor['hex'] }}; color: {{ $noteColor['text'] }};"
                    >
                        <div class="fqn-dock-card-head">
                            <span class="fqn-dock-card-title">{{ $note['title'] ?: __('filament-quick-notes::translations.untitled') }}</span>
                            <span class="fqn-sticky-badge">{{ __('filament-quick-notes::translations.docked') }}</span>
                        </div>

                        <div class="fqn-dock-card-body">{{ filled($note['content']) ? $note['content'] : __('filament-quick-notes::translations.empty_note') }}</div>

                        <div class="fqn-dock-card-actions">
                            <div class="fqn-dock-order">
                                <button type="button" class="fqn-icon-btn fqn-up-btn" wire:click="moveDockedNote({{ $noteIdJs }}, 'up')" title="{{ __('filament-quick-notes::translations.move_up') }}">
                                    <x-heroicon-m-chevron-up class="fqn-icon-xs" aria-hidden="true" />
                                </button>
                                <button type="button" class="fqn-icon-btn fqn-down-btn" wire:click="moveDockedNote({{ $noteIdJs }}, 'down')" title="{{ __('filament-quick-notes::translations.move_down') }}">
                                    <x-heroicon-m-chevron-down class="fqn-icon-xs" aria-hidden="true" />
                                </button>
                            </div>

                            <div class="fqn-sticky-actions">
                                <button
                                    type="button"
                                    class="fqn-sticky-btn"
                                    x-on:click="$wire.selectNote({{ $noteIdJs }}); $wire.openModal()"
                                    title="{{ __('filament-quick-notes::translations.open_in_editor') }}"
                                >
                                    <x-heroicon-o-pencil-square class="fqn-icon-xs" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                class="fqn-sticky-btn"
                                wire:click="undockNote({{ $noteIdJs }})"
                                title="{{ __('filament-quick-notes::translations.undock_note') }}"
                            >
                                <x-heroicon-o-arrow-left class="fqn-icon-xs" aria-hidden="true" />
                            </button>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </aside>
    @endif

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
                    <button type="button" class="fqn-sticky-handle top" x-on:pointerdown="startResize('top', $event)"></button>
                    <button type="button" class="fqn-sticky-handle right" x-on:pointerdown="startResize('right', $event)"></button>
                    <button type="button" class="fqn-sticky-handle bottom" x-on:pointerdown="startResize('bottom', $event)"></button>
                    <button type="button" class="fqn-sticky-handle left" x-on:pointerdown="startResize('left', $event)"></button>
                    <button type="button" class="fqn-sticky-handle top-left" x-on:pointerdown="startResize('top-left', $event)"></button>
                    <button type="button" class="fqn-sticky-handle top-right" x-on:pointerdown="startResize('top-right', $event)"></button>
                    <button type="button" class="fqn-sticky-handle bottom-left" x-on:pointerdown="startResize('bottom-left', $event)"></button>
                    <button type="button" class="fqn-sticky-handle bottom-right" x-on:pointerdown="startResize('bottom-right', $event)"></button>
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
                                wire:click="dockNote({{ $noteIdJs }})"
                                title="{{ __('filament-quick-notes::translations.dock_note') }}"
                            >
                                <x-heroicon-o-view-columns class="fqn-icon-xs" aria-hidden="true" />
                            </button>
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
                                        <div class="fqn-sticky-actions">
                                            <button
                                                type="button"
                                                class="fqn-mini-pill {{ $isActivePinned ? 'active' : '' }}"
                                                wire:click="{{ $isActivePinned ? 'unpinNote' : 'pinNote' }}({{ $activeId }})"
                                            >
                                                {{ $isActivePinned ? __('filament-quick-notes::translations.unpin_note') : __('filament-quick-notes::translations.pin_note') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="fqn-mini-pill {{ (bool) ($activeNote['is_docked'] ?? false) ? 'active' : '' }}"
                                                wire:click="{{ (bool) ($activeNote['is_docked'] ?? false) ? 'undockNote' : 'dockNote' }}({{ $activeId }})"
                                            >
                                                {{ (bool) ($activeNote['is_docked'] ?? false) ? __('filament-quick-notes::translations.undock_note') : __('filament-quick-notes::translations.dock_note') }}
                                            </button>
                                        </div>
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
                                        <button
                                            type="button"
                                            class="fqn-mini-pill {{ ($note['is_docked'] ?? false) ? 'active' : '' }}"
                                            wire:click="{{ ($note['is_docked'] ?? false) ? 'undockNote' : 'dockNote' }}({{ $noteIdJs }})"
                                        >
                                            {{ ($note['is_docked'] ?? false) ? __('filament-quick-notes::translations.undock_note') : __('filament-quick-notes::translations.dock_note') }}
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
