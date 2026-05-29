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
                width="full"
        >
            <x-slot name="trigger">
                <button
                        type="button"
                        @class([
                            'fi-input-wrapper flex w-full items-center justify-between mx-0 rounded-lg bg-white px-3 py-1.5 shadow-sm ring-1 ring-inset transition duration-75 focus-within:ring-2 dark:bg-white/5',
                            'ring-gray-950/10 focus-within:ring-primary-600 dark:ring-white/20 dark:focus-within:ring-primary-500' => ! $errors->has($statePath),
                            'ring-danger-600 focus-within:ring-danger-600 dark:ring-danger-500 dark:focus-within:ring-danger-500' => $errors->has($statePath),
                            'opacity-70 bg-gray-50 dark:bg-transparent dark:opacity-70 cursor-not-allowed' => $isDisabled,
                        ])
                        @if ($isDisabled) disabled @endif
                >
                    <div class="flex items-center gap-1 flex-nowrap overflow-hidden h-6" :class="{ 'text-gray-950 dark:text-white': state?.length, 'text-gray-500 dark:text-gray-400': !state?.length }">
                        <span x-show="!state || state.length === 0" class="text-sm sm:text-base leading-6 truncate">
                            {{ __('dropdown-checkbox-list::messages.placeholder') }}
                        </span>

                        <template x-if="state && state.length > 0 && state.length <= 3">
                            <template x-for="item in state" :key="item">
                                <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-sm font-medium ring-1 ring-inset px-2 min-w-[max-content] py-0.5 bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">
                                    <span x-text="optionsLabels[String(item)] || item" class="truncate"></span>
                                    <button
                                            type="button"
                                            x-on:click.stop.prevent="removeItem(item)"
                                            class="-mr-1 flex items-center justify-center p-0.5 text-primary-600/80 hover:text-primary-700 dark:text-primary-400/80 dark:hover:text-primary-300 rounded-md hover:bg-primary-600/10 dark:hover:bg-primary-400/20 transition"
                                    >
                                        <x-filament::icon
                                                icon="heroicon-m-x-mark"
                                                class="h-3.5 w-3.5"
                                        />
                                        <span class="sr-only">Odznacz</span>
                                    </button>
                                </span>
                            </template>
                        </template>

                        <span x-show="state && state.length > 3" class="text-sm sm:text-base truncate" x-text="(state ? state.length : 0) + '/{{ count($getOptions()) }} {{ __("dropdown-checkbox-list::messages.selected_count") }}'"></span>
                    </div>
                    <x-filament::icon
                            icon="heroicon-m-chevron-up-down"
                            class="h-5 w-5 text-gray-400 dark:text-gray-500"
                    />
                </button>
            </x-slot>

            <div class="p-4" x-on:click.stop>
                @if (! $isDisabled)
                    @if ($isSearchable)
                        <x-filament::input.wrapper
                                inline-prefix
                                prefix-icon="heroicon-m-magnifying-glass"
                                prefix-icon-alias="forms:components.checkbox-list.search-field"
                                class="mb-4"
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

                @php
                    $getGridClass = function ($breakpoint, $columns) use ($gridDirection) {
                        if (! $columns) return null;
                        $prefix = $breakpoint === 'default' ? '' : "{$breakpoint}:";
                        return $gridDirection === 'column' ? "{$prefix}columns-{$columns}" : "{$prefix}grid-cols-{$columns}";
                    };
                @endphp
                <div
                        @if ($isSearchable) x-show="visibleCheckboxListOptions.length" @endif
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
                                :style="(state || []).map(String).includes('{{ addslashes((string)$value) }}') ? 'order: -1;' : 'order: 0;'"
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
                            <label class="fi-fo-checkbox-list-option-label flex w-full items-center gap-x-3 cursor-pointer">
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
