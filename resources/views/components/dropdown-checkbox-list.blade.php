@php
    $gridDirection = $getGridDirection() ?? 'column';
    $isBulkToggleable = $isBulkToggleable();
    $isDisabled = $isDisabled();
    $isSearchable = $isSearchable();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
            x-data="{
            areAllCheckboxesChecked: false,
            checkboxListOptions: [],
            search: '',
            visibleCheckboxListOptions: [],
            state: $wire.$entangle('{{ $statePath }}'),
            optionsLabels: @js(collect($getOptions())->mapWithKeys(fn ($label, $value) => [(string)$value => strip_tags((string)$label)])),

            init() {
                this.checkboxListOptions = Array.from($root.querySelectorAll('.fi-fo-checkbox-list-option-label'))
                this.updateVisibleCheckboxListOptions()
                this.$nextTick(() => { this.checkIfAllCheckboxesAreChecked() })

                Livewire.hook('commit', ({ component, commit, succeed, fail, respond }) => {
                    succeed(({ snapshot, effect }) => {
                        this.$nextTick(() => {
                            if (component.id !== @js($this->getId())) return
                            this.checkboxListOptions = Array.from($root.querySelectorAll('.fi-fo-checkbox-list-option-label'))
                            this.updateVisibleCheckboxListOptions()
                            this.checkIfAllCheckboxesAreChecked()
                        })
                    })
                })

                this.$watch('search', () => {
                    this.updateVisibleCheckboxListOptions()
                    this.checkIfAllCheckboxesAreChecked()
                })
            },

            checkIfAllCheckboxesAreChecked: function () {
                let enabledCheckboxes = []
                this.visibleCheckboxListOptions.forEach((label) => {
                    let cb = label.querySelector('input[type=checkbox]')
                    if (cb && !cb.disabled) enabledCheckboxes.push(cb)
                })

                this.areAllCheckboxesChecked =
                    enabledCheckboxes.length > 0 &&
                    enabledCheckboxes.every(cb => cb.checked)
            },

            toggleAllCheckboxes: function () {
                let checkState = ! this.areAllCheckboxesChecked

                this.visibleCheckboxListOptions.forEach((checkboxLabel) => {
                    let checkbox = checkboxLabel.querySelector('input[type=checkbox]')

                    if (checkbox && !checkbox.disabled && checkbox.checked !== checkState) {
                        checkbox.checked = checkState
                        checkbox.dispatchEvent(new Event('change'))
                    }
                })

                this.areAllCheckboxesChecked = checkState
            },

            updateVisibleCheckboxListOptions: function () {
                @if ($hasSearchCallback())
                    this.visibleCheckboxListOptions = this.checkboxListOptions;
                @else
                    this.visibleCheckboxListOptions = this.checkboxListOptions.filter(
                        (checkboxListItem) => {
                            if (checkboxListItem.querySelector('.fi-fo-checkbox-list-option-label')?.innerText.toLowerCase().includes(this.search.toLowerCase())) {
                                return true
                            }
                            return checkboxListItem.querySelector('.fi-fo-checkbox-list-option-description')?.innerText.toLowerCase().includes(this.search.toLowerCase())
                        },
                    )
                @endif
            },

            removeItem: function (itemToRemove) {
                // Find the specific checkbox inside this component
                let checkbox = null
                this.checkboxListOptions.forEach((label) => {
                    let input = label.querySelector('input[type=checkbox]')
                    if (input && input.value == itemToRemove) {
                        checkbox = input
                    }
                })

                if (checkbox && !checkbox.disabled) {
                    checkbox.checked = false
                    checkbox.dispatchEvent(new Event('change'))
                } else {
                    // Fallback
                    this.state = this.state.filter((item) => item != itemToRemove)
                }

                this.checkIfAllCheckboxesAreChecked()
            },
        }"
    >
        <x-filament::dropdown
                max-height="400px"
                placement="bottom-start"
        >
            <x-slot name="trigger">
                <div id="{{ $this->getId() }}-{{ $getStatePath() }}-trigger" style="width: 100%;">
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

            <div class="p-4" style="padding: 1rem;" x-on:click.stop x-init="
                const updateWidth = () => {
                    const trigger = document.getElementById('{{ $this->getId() }}-{{ $getStatePath() }}-trigger');
                    const panel = $el.closest('.fi-dropdown-panel') || $el.closest('div[x-ref=\'panel\']');
                    if (trigger && panel) {
                        panel.style.setProperty('width', trigger.offsetWidth + 'px', 'important');
                        panel.style.setProperty('min-width', trigger.offsetWidth + 'px', 'important');
                        panel.style.setProperty('max-width', 'none', 'important');
                    }
                };
                $watch('isOpen', (val) => { if (val) setTimeout(updateWidth, 10); });
                const triggerEl = document.getElementById('{{ $this->getId() }}-{{ $getStatePath() }}-trigger');
                if (triggerEl) {
                    new ResizeObserver(updateWidth).observe(triggerEl);
                }
            ">
                @if (! $isDisabled)
                    @if ($isSearchable)
                        <x-filament::input.wrapper
                                inline-prefix
                                prefix-icon="heroicon-m-magnifying-glass"
                                prefix-icon-alias="forms:components.checkbox-list.search-field"
                                class="mb-4"
                                style="margin-bottom: 1rem;"
                        >
                            @if ($hasSearchCallback())
                                <x-filament::input
                                        inline-prefix
                                        :placeholder="$getSearchPrompt()"
                                        type="search"
                                        wire:model.live.debounce.500ms="{{ $hasDropdownSearch() ? 'filterSearches.' . $getStatePath() : $getStatePath() . '_search' }}"
                                />
                            @else
                                <x-filament::input
                                        inline-prefix
                                        :placeholder="$getSearchPrompt()"
                                        type="search"
                                        :attributes="
                                        \Filament\Support\prepare_inherited_attributes(
                                            new \Illuminate\View\ComponentAttributeBag([
                                                'x-model.debounce.' . $getSearchDebounce() => 'search',
                                            ])
                                        )
                                    "
                                />
                            @endif
                        </x-filament::input.wrapper>
                    @endif

                    @if ($isBulkToggleable && count($getOptions()))
                        <div
                                x-cloak
                                class="mb-4 flex gap-x-4 text-sm font-medium text-primary-600 dark:text-primary-400"
                                style="display: flex; gap: 1rem; margin-bottom: 1rem;"
                                wire:key="{{ $this->getId() }}.{{ $getStatePath() }}.{{ $field::class }}.actions"
                        >
                            <span
                                    class="cursor-pointer hover:underline"
                                    x-show="! areAllCheckboxesChecked"
                                    x-on:click="toggleAllCheckboxes()"
                                    wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.actions.select-all"
                            >
                                {{ $getAction('selectAll')?->getLabel() ?? __('dropdown-checkbox-list::messages.select_all') }}
                            </span>

                            <span
                                    class="cursor-pointer hover:underline"
                                    x-show="areAllCheckboxesChecked"
                                    x-on:click="toggleAllCheckboxes()"
                                    wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.actions.deselect-all"
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
                    @php
                        $options = $getOptions();
                        $currentState = is_array($getState()) ? $getState() : [];

                        $selectedOptions = [];
                        $unselectedOptions = [];

                        foreach ($options as $val => $lbl) {
                            if (in_array((string)$val, array_map('strval', $currentState))) {
                                $selectedOptions[$val] = $lbl;
                            } else {
                                $unselectedOptions[$val] = $lbl;
                            }
                        }

                        $sortedOptions = $selectedOptions + $unselectedOptions;
                    @endphp
                    @forelse ($sortedOptions as $value => $label)
                        <div
                                wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.options.{{ $value }}"
                                :style="(state || []).map(String).includes('{{ addslashes((string)$value) }}') ? 'order: -1; margin-bottom: 0.5rem;' : 'order: 0; margin-bottom: 0.5rem;'"
                                @if ($isSearchable && !$hasSearchCallback())
                                    x-show="
                                    $el
                                        .querySelector('.fi-fo-checkbox-list-option-label')
                                        ?.innerText.toLowerCase()
                                        .includes(search.toLowerCase()) ||
                                        $el
                                            .querySelector('.fi-fo-checkbox-list-option-description')
                                            ?.innerText.toLowerCase()
                                            .includes(search.toLowerCase())
                                "
                                @endif
                                @class([
                                    'break-inside-avoid pt-4' => $gridDirection === 'column',
                                ])
                        >
                            <label class="fi-fo-checkbox-list-option-label flex w-full items-center gap-x-3 cursor-pointer" style="display: flex; align-items: center; width: 100%; gap: 0.75rem; cursor: pointer;">
                                <x-filament::input.checkbox
                                        :valid="! $errors->has($statePath)"
                                        :attributes="
                                        \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                                            ->merge([
                                                'disabled' => $isDisabled || $isOptionDisabled($value, $label),
                                                'value' => $value,
                                                'wire:loading.attr' => 'disabled',
                                                $applyStateBindingModifiers('wire:model') => $statePath,
                                                'x-on:change' => $isBulkToggleable ? 'checkIfAllCheckboxesAreChecked()' : null,
                                            ], escape: false)
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
                        <div wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.empty"></div>
                    @endforelse
                </div>

                @if ($isSearchable)
                    <div
                            x-cloak
                            @if ($hasSearchCallback())
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
