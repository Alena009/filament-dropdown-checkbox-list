@php
    $groupLeaves = $flattenLeaves($groupChildren);
    $groupValues = array_values(array_map(fn ($v) => (string) $v, array_keys($groupLeaves)));
    $groupValuesJs = '[' . implode(',', array_map(fn ($v) => "'" . addslashes($v) . "'", $groupValues)) . ']';
    $groupLabelText = strip_tags((string) $groupLabel);
    $currentPathLabel = trim(($ancestorLabel ?? '') . ' ' . $groupLabelText);
@endphp

<div
        @if ($usesServerSideGroupSearch)
            wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.group.{{ md5($groupKey . '|' . $groupLabelText) }}"
        @endif
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
        @foreach ($groupChildren as $childKey => $childValue)
            @if (is_array($childValue))
                @include('dropdown-checkbox-list::components.dropdown-checkbox-list-group', [
                    'groupLabel' => $childKey,
                    'groupChildren' => $childValue,
                    'groupKey' => $groupKey . '|' . $loop->index,
                    'ancestorLabel' => $currentPathLabel,
                ])
            @else
                @php
                    $value = $childKey;
                    $label = $childValue;
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
                        @if ($usesServerSideGroupSearch)
                            wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.option.{{ $value }}"
                        @endif
                        data-group-label="{{ $currentPathLabel }}"
                        @if ($isSearchable && ! $usesServerSideGroupSearch)
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
            @endif
        @endforeach
    </div>
</div>
