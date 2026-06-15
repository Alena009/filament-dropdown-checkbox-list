@php
    $gridDirection = $getGridDirection() ?? 'column';
    $isBulkToggleable = $isBulkToggleable();
    $isDisabled = $isDisabled();
    $isSearchable = $isSearchable();
    $statePath = $getStatePath();
    $usesServerSideSearch = $hasSearchCallback();

    $initialCollapsedGroups = [];
    if ($hasGroupedOptions()) {
        $collapseGroupsByDefault = $shouldCollapseGroupsByDefault();
        $groupIndex = 0;
        foreach ($getGroupedOptions() as $ignoredGroupChildren) {
            $initialCollapsedGroups['g' . $groupIndex] = $collapseGroupsByDefault;
            $groupIndex++;
        }
    }
@endphp

<style>
    .dropdown-checkbox-list-search-container {
        margin-top: -1rem !important;
        margin-left: -1rem !important;
        margin-right: -1rem !important;
        margin-bottom: 0.5rem !important;
        border-bottom: 1px solid #e5e7eb !important;
    }
    .dark .dropdown-checkbox-list-search-container {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .dropdown-checkbox-list-search-input {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
    }
    .dropdown-checkbox-list-group + .dropdown-checkbox-list-group {
        margin-top: 0.75rem;
    }
    .dropdown-checkbox-list-group-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 0.5rem;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .dark .dropdown-checkbox-list-group-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .dropdown-checkbox-list-group-title {
        display: flex;
        flex: 1 1 auto;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        cursor: pointer;
        user-select: none;
        min-width: 0;
    }
    .dropdown-checkbox-list-group-chevron {
        flex-shrink: 0;
        transition: transform 0.15s ease;
    }
    .dropdown-checkbox-list-group-children {
        display: grid;
        gap: 0.5rem;
        padding-left: 1.75rem;
    }
</style>

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.container">
        <x-filament::dropdown
                wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.dropdown"
                max-height="400px"
                placement="bottom-start"
        >
            <x-slot name="trigger">
                <div
                        id="{{ $this->getId() }}-{{ $getStatePath() }}-trigger"
                        style="width: 100%;"
                        x-data="{
                            state: $wire.$entangle('{{ $statePath }}'),
                            optionsLabels: @js(collect($getOptions())->mapWithKeys(fn ($label, $value) => [(string)$value => strip_tags((string)$label)])),
                            removeItem(itemToRemove) {
                                this.state = (this.state ?? []).filter((item) => String(item) !== String(itemToRemove))
                            },
                        }"
                >
                <x-filament::input.wrapper :disabled="$isDisabled" :valid="! $errors->has($statePath)" class="cursor-pointer" style="width: 100%;">
                    <div
                            class="outline-none"
                            style="display: flex; width: 100%; align-items: center; justify-content: space-between; padding: 0.375rem 0.75rem; min-height: 2.25rem;"
                            @if ($isDisabled) disabled @endif
                    >
                        <div style="display: flex; align-items: center; gap: 0.25rem; overflow: hidden; white-space: nowrap; height: 1.5rem;" :class="{ 'text-gray-950 dark:text-white': state?.length, 'text-gray-500 dark:text-gray-400': !state?.length }">
                        <span x-show="!state || state.length === 0" class="text-sm sm:text-base leading-6 truncate">
                            {{ __('dropdown-checkbox-list::messages.placeholder') }}
                        </span>

                        <template x-if="state && state.length > 0 && state.length <= {{ $getMaxItemsShown() }}">
                            <template x-for="item in state" :key="item">
                                <x-filament::badge :color="$getColor() ?? 'primary'" class="!text-primary-200 !bg-primary-600/10 !ring-primary-600/30 dark:!text-primary-200 dark:!bg-primary-600/10 dark:!ring-primary-600/30">
                                    <x-slot name="deleteButton" x-on:mousedown.stop="" x-on:click.stop.prevent="removeItem(item)" class="!text-primary-200"></x-slot>
                                    <span x-text="optionsLabels[String(item)] || item" style="max-width: 15rem; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: bottom;"></span>
                                </x-filament::badge>
                            </template>
                        </template>

                        <span x-show="state && state.length > {{ $getMaxItemsShown() }}" class="text-sm sm:text-base truncate" x-text="(state ? state.length : 0) + '/{{ count($getOptions()) }} {{ __("dropdown-checkbox-list::messages.selected_count") }}'"></span>
                    </div>
                    <x-filament::icon
                            icon="heroicon-m-chevron-up-down"
                            class="h-5 w-5 text-gray-400 dark:text-gray-500"
                    />
                    </div>
                </x-filament::input.wrapper>
                </div>
            </x-slot>

            <div
                wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.panel"
                class="p-4"
                style="padding: 1rem;"
                @if (! $usesServerSideSearch) wire:ignore @endif
                @if (! $usesServerSideSearch)
                x-data="{
                    areAllCheckboxesChecked: false,
                    checkboxListOptions: [],
                    search: '',
                    visibleCheckboxListOptions: [],
                    collapsedGroups: @js($initialCollapsedGroups),
                    state: $wire.$entangle('{{ $statePath }}'),

                    init() {
                        this.refreshCheckboxListOptions()

                        this.$watch('search', () => {
                            this.updateVisibleCheckboxListOptions()
                            this.checkIfAllCheckboxesAreChecked()
                        })

                        this.$watch('state', () => {
                            this.updateVisibleCheckboxListOptions()
                            this.checkIfAllCheckboxesAreChecked()
                        })
                    },

                    optionWrapperStyle(value, el) {
                        let style = (this.state ?? []).map(String).includes(String(value))
                            ? 'order: -1; margin-bottom: 0.5rem;'
                            : 'order: 0; margin-bottom: 0.5rem;'

                        if (! this.matchesSearch(el)) {
                            style += ' display: none;'
                        }

                        return style
                    },

                    refreshCheckboxListOptions() {
                        this.checkboxListOptions = Array.from(this.$root.querySelectorAll('.fi-fo-checkbox-list-option-wrapper'))
                        this.updateVisibleCheckboxListOptions()
                        this.$nextTick(() => { this.checkIfAllCheckboxesAreChecked() })
                    },

                    checkIfAllCheckboxesAreChecked() {
                        let enabledCheckboxes = []

                        this.checkboxListOptions.forEach((wrapper) => {
                            if (! this.isOptionVisible(wrapper)) {
                                return
                            }

                            let cb = wrapper.querySelector('input[type=checkbox]')

                            if (cb && ! cb.disabled) {
                                enabledCheckboxes.push(cb)
                            }
                        })

                        this.areAllCheckboxesChecked =
                            enabledCheckboxes.length > 0 &&
                            enabledCheckboxes.every(cb => cb.checked)
                    },

                    isOptionVisible(wrapper) {
                        return this.matchesSearch(wrapper)
                    },

                    toggleAllCheckboxes() {
                        let checkState = ! this.areAllCheckboxesChecked
                        let current = [...(this.state ?? [])]

                        this.checkboxListOptions.forEach((wrapper) => {
                            if (! this.isOptionVisible(wrapper)) {
                                return
                            }

                            let checkbox = wrapper.querySelector('input[type=checkbox]')

                            if (! checkbox || checkbox.disabled) {
                                return
                            }

                            let value = checkbox.value
                            let hasValue = current.map(String).includes(String(value))

                            if (checkState && ! hasValue) {
                                current.push(value)
                            }

                            if (! checkState && hasValue) {
                                current = current.filter((item) => String(item) !== String(value))
                            }
                        })

                        this.state = current
                        this.areAllCheckboxesChecked = checkState
                    },

                    toggleOption(value, checked) {
                        let current = [...(this.state ?? [])]

                        if (checked) {
                            if (! current.map(String).includes(String(value))) {
                                current.push(value)
                            }
                        } else {
                            current = current.filter((item) => String(item) !== String(value))
                        }

                        this.state = current
                        this.checkIfAllCheckboxesAreChecked()
                    },

                    groupChecked(values) {
                        let selected = (this.state ?? []).map(String)

                        return values.length > 0 && values.every((v) => selected.includes(String(v)))
                    },

                    groupIndeterminate(values) {
                        let selected = (this.state ?? []).map(String)
                        let selectedInGroup = values.filter((v) => selected.includes(String(v)))

                        return selectedInGroup.length > 0 && selectedInGroup.length < values.length
                    },

                    toggleGroup(values, checked) {
                        let current = [...(this.state ?? [])]
                        let groupValues = values.map(String)

                        if (checked) {
                            groupValues.forEach((v) => {
                                if (! current.map(String).includes(v)) {
                                    current.push(v)
                                }
                            })
                        } else {
                            current = current.filter((item) => ! groupValues.includes(String(item)))
                        }

                        this.state = current
                        this.checkIfAllCheckboxesAreChecked()
                    },

                    groupHasVisibleChildren(values) {
                        void this.search

                        let groupValues = values.map(String)

                        return this.checkboxListOptions.some((wrapper) => {
                            let checkbox = wrapper.querySelector('input[type=checkbox]')

                            if (! checkbox || ! groupValues.includes(String(checkbox.value))) {
                                return false
                            }

                            return this.matchesSearch(wrapper)
                        })
                    },

                    isGroupCollapsed(key) {
                        return ! this.search && !! this.collapsedGroups[key]
                    },

                    toggleGroupCollapse(key) {
                        this.collapsedGroups[key] = ! this.collapsedGroups[key]
                    },

                    matchesSearch(checkboxListItem) {
                        let checkbox = checkboxListItem.querySelector('input[type=checkbox]')
                        let value = checkbox?.value

                        if (value !== undefined && (this.state ?? []).map(String).includes(String(value))) {
                            return true
                        }

                        if (! this.search) {
                            return true
                        }

                        let term = this.search.toLowerCase()

                        let groupLabel = checkboxListItem.dataset.groupLabel

                        if (groupLabel && groupLabel.toLowerCase().includes(term)) {
                            return true
                        }

                        return (
                            checkboxListItem.querySelector('.fi-fo-checkbox-list-option-label')?.innerText.toLowerCase().includes(term) ||
                            checkboxListItem.querySelector('.fi-fo-checkbox-list-option-description')?.innerText.toLowerCase().includes(term)
                        )
                    },

                    updateVisibleCheckboxListOptions() {
                        this.visibleCheckboxListOptions = this.checkboxListOptions.filter(
                            (checkboxListItem) => this.matchesSearch(checkboxListItem),
                        )
                    },
                }"
                @else
                x-data="{
                    areAllCheckboxesChecked: false,
                    checkboxListOptions: [],
                    visibleCheckboxListOptions: [],
                    state: $wire.$entangle('{{ $statePath }}'),

                    init() {
                        const rootEl = $root

                        this.refreshCheckboxListOptions()

                        Livewire.hook('commit', ({ component, commit, succeed, fail, respond }) => {
                            succeed(({ snapshot, effect }) => {
                                this.$nextTick(() => {
                                    if (component.el && ! component.el.contains(rootEl)) return
                                    this.refreshCheckboxListOptions()
                                })
                            })
                        })
                    },

                    refreshCheckboxListOptions() {
                        this.checkboxListOptions = Array.from(this.$root.querySelectorAll('.fi-fo-checkbox-list-option-wrapper'))
                        this.visibleCheckboxListOptions = this.checkboxListOptions
                        this.$nextTick(() => { this.checkIfAllCheckboxesAreChecked() })
                    },

                    checkIfAllCheckboxesAreChecked() {
                        let enabledCheckboxes = []

                        this.checkboxListOptions.forEach((wrapper) => {
                            let cb = wrapper.querySelector('input[type=checkbox]')

                            if (cb && ! cb.disabled) {
                                enabledCheckboxes.push(cb)
                            }
                        })

                        this.areAllCheckboxesChecked =
                            enabledCheckboxes.length > 0 &&
                            enabledCheckboxes.every(cb => cb.checked)
                    },

                    toggleAllCheckboxes() {
                        let checkState = ! this.areAllCheckboxesChecked

                        this.checkboxListOptions.forEach((checkboxLabel) => {
                            let checkbox = checkboxLabel.querySelector('input[type=checkbox]')

                            if (checkbox && ! checkbox.disabled && checkbox.checked !== checkState) {
                                checkbox.checked = checkState
                                checkbox.dispatchEvent(new Event('change', { bubbles: true }))
                            }
                        })

                        this.areAllCheckboxesChecked = checkState
                    },
                }"
                @endif
                x-on:click.stop
                x-on:keydown.escape.window="
                    @if (! $usesServerSideSearch)
                        search = '';
                    @else
                        $wire.set('{{ $hasDropdownSearch() ? 'filterSearches.' . $getStatePath() : $getStatePath() . '_search' }}', '');
                    @endif
                "
                x-init="
                    const updateWidth = () => {
                        const trigger = document.getElementById('{{ $this->getId() }}-{{ $getStatePath() }}-trigger');
                        const panel = $el.closest('.fi-dropdown-panel') || $el.closest('div[x-ref=\'panel\']');
                        if (trigger && panel) {
                            panel.style.setProperty('width', trigger.offsetWidth + 'px', 'important');
                            panel.style.setProperty('min-width', trigger.offsetWidth + 'px', 'important');
                            panel.style.setProperty('max-width', 'none', 'important');
                        }
                    };
                    const triggerEl = document.getElementById('{{ $this->getId() }}-{{ $getStatePath() }}-trigger');
                    if (triggerEl) {
                        new ResizeObserver(updateWidth).observe(triggerEl);
                    }
                    setTimeout(updateWidth, 10);
                "
            >
                @if (! $isDisabled)
                    @if ($isSearchable)
                        <div class="dropdown-checkbox-list-search-container">
                            @if ($usesServerSideSearch)
                                <x-filament::input
                                        :placeholder="$getSearchPrompt()"
                                        type="search"
                                        wire:model.live.debounce.500ms="{{ $hasDropdownSearch() ? 'filterSearches.' . $getStatePath() : $getStatePath() . '_search' }}"
                                        class="dropdown-checkbox-list-search-input"
                                />
                            @else
                                <x-filament::input
                                        :placeholder="$getSearchPrompt()"
                                        type="search"
                                        :attributes="
                                        \Filament\Support\prepare_inherited_attributes(
                                            new \Illuminate\View\ComponentAttributeBag([
                                                'x-model.debounce.' . $getSearchDebounce() => 'search',
                                            ])
                                        )
                                    "
                                        class="dropdown-checkbox-list-search-input"
                                />
                            @endif
                        </div>
                    @endif

                    @if ($isBulkToggleable && count($getOptions()))
                        <div
                                x-cloak
                                class="mb-4 flex gap-x-4 text-sm font-medium text-primary-600 dark:text-primary-400"
                                style="display: flex; gap: 1rem; margin-bottom: 1rem;"
                        >
                            <span
                                    class="cursor-pointer hover:underline"
                                    x-show="! areAllCheckboxesChecked"
                                    x-on:click="toggleAllCheckboxes()"
                            >
                                {{ $getAction('selectAll')?->getLabel() ?? __('dropdown-checkbox-list::messages.select_all') }}
                            </span>

                            <span
                                    class="cursor-pointer hover:underline"
                                    x-show="areAllCheckboxesChecked"
                                    x-on:click="toggleAllCheckboxes()"
                            >
                                {{ $getAction('deselectAll')?->getLabel() ?? __('dropdown-checkbox-list::messages.deselect_all') }}
                            </span>
                        </div>
                    @endif
                @endif

                <!--
                    Tailwind JIT Safelist:
                    columns-1 columns-2 columns-3 columns-4 columns-5 columns-6
                    sm:columns-1 sm:columns-2 sm:columns-3 sm:columns-4 sm:columns-5 sm:columns-6
                    md:columns-1 md:columns-2 md:columns-3 md:columns-4 md:columns-5 md:columns-6
                    lg:columns-1 lg:columns-2 lg:columns-3 lg:columns-4 lg:columns-5 lg:columns-6
                    xl:columns-1 xl:columns-2 xl:columns-3 xl:columns-4 xl:columns-5 xl:columns-6
                    2xl:columns-1 2xl:columns-2 2xl:columns-3 2xl:columns-4 2xl:columns-5 2xl:columns-6

                    grid-cols-1 grid-cols-2 grid-cols-3 grid-cols-4 grid-cols-5 grid-cols-6
                    sm:grid-cols-1 sm:grid-cols-2 sm:grid-cols-3 sm:grid-cols-4 sm:grid-cols-5 sm:grid-cols-6
                    md:grid-cols-1 md:grid-cols-2 md:grid-cols-3 md:grid-cols-4 md:grid-cols-5 md:grid-cols-6
                    lg:grid-cols-1 lg:grid-cols-2 lg:grid-cols-3 lg:grid-cols-4 lg:grid-cols-5 lg:grid-cols-6
                    xl:grid-cols-1 xl:grid-cols-2 xl:grid-cols-3 xl:grid-cols-4 xl:grid-cols-5 xl:grid-cols-6
                    2xl:grid-cols-1 2xl:grid-cols-2 2xl:grid-cols-3 2xl:grid-cols-4 2xl:grid-cols-5 2xl:grid-cols-6
                -->
                @php
                    $getGridClass = function ($breakpoint, $columns) use ($gridDirection) {
                        if (! $columns) return null;
                        $prefix = $breakpoint === 'default' ? '' : "{$breakpoint}:";
                        return $gridDirection === 'column' ? "{$prefix}columns-{$columns}" : "{$prefix}grid-cols-{$columns}";
                    };
                @endphp
                @if ($hasGroupedOptions())
                    <div
                            @if ($isSearchable) x-show="visibleCheckboxListOptions.length" @endif
                            class="fi-fo-checkbox-list"
                            style="display: flex; flex-direction: column;"
                    >
                        @foreach ($getGroupedOptions() as $groupLabel => $groupChildren)
                            @php
                                $groupValues = array_values(array_map(fn ($v) => (string) $v, array_keys($groupChildren)));
                                $groupLabelText = strip_tags((string) $groupLabel);
                                $groupValuesJs = '[' . implode(',', array_map(fn ($v) => "'" . addslashes($v) . "'", $groupValues)) . ']';
                                $groupKey = 'g' . $loop->index;
                            @endphp
                            <div
                                    class="dropdown-checkbox-list-group"
                                    @if ($isSearchable) x-show="groupHasVisibleChildren({{ $groupValuesJs }})" @endif
                            >
                                <div class="dropdown-checkbox-list-group-header">
                                    <label class="dropdown-checkbox-list-group-checkbox" style="display: flex; align-items: center; cursor: pointer;">
                                        <x-filament::input.checkbox
                                                :valid="! $errors->has($statePath)"
                                                :attributes="
                                                \Filament\Support\prepare_inherited_attributes(
                                                    new \Illuminate\View\ComponentAttributeBag([])
                                                )
                                                    ->merge([
                                                        'disabled' => $isDisabled,
                                                        'x-bind:checked' => 'groupChecked(' . $groupValuesJs . ')',
                                                        'x-effect' => '$el.indeterminate = groupIndeterminate(' . $groupValuesJs . ')',
                                                        'x-on:change' => 'toggleGroup(' . $groupValuesJs . ', $event.target.checked)',
                                                    ], escape: false)
                                                    ->class(['mt-0'])"
                                        />
                                    </label>

                                    <div
                                            class="dropdown-checkbox-list-group-title"
                                            x-on:click="toggleGroupCollapse('{{ $groupKey }}')"
                                    >
                                        <span class="block w-full overflow-hidden break-words font-semibold text-gray-950 dark:text-white">
                                            @if ($isHtmlAllowed())
                                                {!! $groupLabel !!}
                                            @else
                                                {{ $groupLabel }}
                                            @endif
                                        </span>

                                        <x-filament::icon
                                                icon="heroicon-m-chevron-down"
                                                class="dropdown-checkbox-list-group-chevron h-5 w-5 text-gray-400 dark:text-gray-500"
                                                x-bind:style="isGroupCollapsed('{{ $groupKey }}') ? 'transform: rotate(-90deg);' : 'transform: rotate(0deg);'"
                                        />
                                    </div>
                                </div>

                                <div
                                        class="dropdown-checkbox-list-group-children"
                                        x-show="! isGroupCollapsed('{{ $groupKey }}')"
                                >
                                    @foreach ($groupChildren as $value => $label)
                                        @php
                                            $stringValue = addslashes((string) $value);

                                            $childCheckboxAttributes = [
                                                'disabled' => $isDisabled || $isOptionDisabled($value, $label),
                                                'value' => $value,
                                                'wire:loading.attr' => 'disabled',
                                                'x-bind:checked' => "(state ?? []).map(String).includes('{$stringValue}')",
                                                'x-on:change' => "toggleOption('{$stringValue}', \$event.target.checked)",
                                            ];
                                        @endphp
                                        <div
                                                data-group-label="{{ $groupLabelText }}"
                                                @if ($isSearchable)
                                                    :style="matchesSearch($el) ? 'margin-bottom: 0;' : 'margin-bottom: 0; display: none;'"
                                                @endif
                                                class="fi-fo-checkbox-list-option-wrapper"
                                        >
                                            <label class="fi-fo-checkbox-list-option-label flex w-full items-center gap-x-3 cursor-pointer" style="display: flex; align-items: center; width: 100%; gap: 0.75rem; cursor: pointer;">
                                                <x-filament::input.checkbox
                                                        :valid="! $errors->has($statePath)"
                                                        :attributes="
                                                        \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                                                            ->merge($childCheckboxAttributes, escape: false)
                                                            ->class(['mt-0'])
                                                    "
                                                />

                                                <div class="grid flex-1 w-full text-sm leading-6">
                                                    <span class="fi-fo-checkbox-list-option-label block w-full overflow-hidden break-words font-medium text-gray-950 dark:text-white">
                                                        @if ($isHtmlAllowed())
                                                            {!! $label !!}
                                                        @else
                                                            {{ $label }}
                                                        @endif
                                                    </span>

                                                    @if ($hasDescription($value))
                                                        <p class="fi-fo-checkbox-list-option-description text-gray-500 dark:text-gray-400">
                                                            {{ $getDescription($value) }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                <div
                        @if ($isSearchable) x-show="visibleCheckboxListOptions.length" @endif
                        style="display: grid; gap: 0.5rem;"
                        {{
                            \Filament\Support\prepare_inherited_attributes($attributes)
                                ->merge($getExtraAttributes(), escape: false)
                                ->class([
                                    'fi-fo-checkbox-list gap-4',
                                    'grid' => $gridDirection === 'row',
                                    '-mt-4' => $gridDirection === 'column',
                                    $getGridClass('default', $getColumns('default')),
                                    $getGridClass('sm', $getColumns('sm')),
                                    $getGridClass('md', $getColumns('md')),
                                    $getGridClass('lg', $getColumns('lg')),
                                    $getGridClass('xl', $getColumns('xl')),
                                    $getGridClass('2xl', $getColumns('2xl')),
                                ])
                        }}
                >
                    @forelse ($getOptions() as $value => $label)
                        @php
                            $stringValue = addslashes((string) $value);

                            $checkboxAttributes = [
                                'disabled' => $isDisabled || $isOptionDisabled($value, $label),
                                'value' => $value,
                                'wire:loading.attr' => 'disabled',
                            ];

                            if ($usesServerSideSearch) {
                                $checkboxAttributes[$applyStateBindingModifiers('wire:model')] = $statePath;
                                $checkboxAttributes['x-on:change'] = $isBulkToggleable ? 'checkIfAllCheckboxesAreChecked()' : null;
                            } else {
                                $checkboxAttributes['x-bind:checked'] = "(state ?? []).map(String).includes('{$stringValue}')";
                                $checkboxAttributes['x-on:change'] = "toggleOption('{$stringValue}', \$event.target.checked)";
                            }
                        @endphp
                        <div
                                @if ($usesServerSideSearch)
                                    wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.options.{{ $value }}"
                                @endif
                                @if ($usesServerSideSearch)
                                    :style="(state ?? []).map(String).includes('{{ $stringValue }}') ? 'order: -1; margin-bottom: 0.5rem;' : 'order: 0; margin-bottom: 0.5rem;'"
                                @else
                                    :style="optionWrapperStyle('{{ $stringValue }}', $el)"
                                @endif
                                @class([
                                    'break-inside-avoid pt-4' => $gridDirection === 'column',
                                    'fi-fo-checkbox-list-option-wrapper',
                                ])
                        >
                            <label class="fi-fo-checkbox-list-option-label flex w-full items-center gap-x-3 cursor-pointer" style="display: flex; align-items: center; width: 100%; gap: 0.75rem; cursor: pointer;">
                                <x-filament::input.checkbox
                                        :valid="! $errors->has($statePath)"
                                        :attributes="
                                        \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                                            ->merge($checkboxAttributes, escape: false)
                                            ->class(['mt-0'])
                                    "
                                />

                                <div class="grid flex-1 w-full text-sm leading-6">
                                    <span
                                            class="fi-fo-checkbox-list-option-label block w-full overflow-hidden break-words font-medium text-gray-950 dark:text-white"
                                    >
                                        @if ($isHtmlAllowed())
                                            {!! $label !!}
                                        @else
                                            {{ $label }}
                                        @endif
                                    </span>

                                    @if ($hasDescription($value))
                                        <p class="fi-fo-checkbox-list-option-description text-gray-500 dark:text-gray-400">
                                            {{ $getDescription($value) }}
                                        </p>
                                    @endif
                                </div>
                            </label>
                        </div>
                    @empty
                        @if ($usesServerSideSearch)
                            <div wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.empty"></div>
                        @endif
                    @endforelse
                </div>
                @endif

                @if ($isSearchable)
                    <div
                            x-cloak
                            @if ($usesServerSideSearch)
                                x-show="! visibleCheckboxListOptions.length && @js(property_exists($getLivewire(), 'filterSearches') ? data_get($getLivewire()->filterSearches ?? [], $getStatePath(), '') : data_get($getLivewire(), $getStatePath() . '_search', '')) !== ''"
                            @else
                                x-show="search && ! visibleCheckboxListOptions.length"
                            @endif
                            class="fi-fo-checkbox-list-no-search-results-message p-4 text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{ $getNoSearchResultsMessage() ?? __('dropdown-checkbox-list::messages.no_results') }}
                    </div>
                @endif
            </div>
        </x-filament::dropdown>
    </div>
</x-dynamic-component>
