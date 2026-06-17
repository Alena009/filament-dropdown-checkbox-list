<?php

namespace AlenaDashko\DropdownCheckboxList\Components;

use AlenaDashko\DropdownCheckboxList\Concerns\HasDropdownSearch;
use Filament\Forms\Components\CheckboxList;
use Filament\Support\Concerns\HasColor;

class DropdownCheckboxList extends CheckboxList
{
    use HasColor;

    protected string $view = 'dropdown-checkbox-list::components.dropdown-checkbox-list';

    protected ?\Closure $searchCallback = null;
    protected ?\Closure $selectedLabelsCallback = null;
    protected int|\Closure $optionsLimit = 50;
    protected int|\Closure $maxItemsShown = 50;
    protected array|\Closure|null $groupedOptions = null;
    protected ?\Closure $groupedSearchCallback = null;
    protected bool|\Closure $collapseGroupsByDefault = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bulkToggleable();
        $this->searchable();
    }

    public function searchUsing(\Closure $callback): static
    {
        $this->searchCallback = $callback;
        return $this;
    }

    public function selectedOptionLabelsUsing(\Closure $callback): static
    {
        $this->selectedLabelsCallback = $callback;
        return $this;
    }

    public function hasSearchCallback(): bool
    {
        return $this->searchCallback !== null;
    }

    /**
     * Render options split into groups. Each group has its own checkbox that
     * toggles every child option at once.
     *
     * Groups can be nested to any depth. A node whose value is an array is a
     * (sub)group; a node whose value is a scalar is a selectable option. For
     * example, a three-level tree:
     * [
     *     'Department' => [
     *         'Subdepartment' => [
     *             'value-1' => 'Label 1',
     *             'value-2' => 'Label 2',
     *         ],
     *     ],
     *     ...
     * ]
     */
    public function groupedOptions(array|\Closure $groups): static
    {
        $this->groupedOptions = $groups;

        return $this;
    }

    /**
     * Server-side searched grouped options. The callback receives the current
     * `$search` string and must return the grouped structure:
     *
     * [
     *     'Group label' => ['value-1' => 'Label 1', ...],
     *     ...
     * ]
     *
     * Use `selectedOptionLabelsUsing()` alongside this so already-selected
     * values keep their labels while the search query changes.
     */
    public function searchGroupedOptionsUsing(\Closure $callback): static
    {
        $this->groupedSearchCallback = $callback;

        return $this;
    }

    public function hasGroupedSearchCallback(): bool
    {
        return $this->groupedSearchCallback !== null;
    }

    public function hasGroupedOptions(): bool
    {
        return $this->groupedOptions !== null || $this->groupedSearchCallback !== null;
    }

    /**
     * Render each group collapsed by default. Groups always remain collapsible
     * regardless of this setting.
     */
    public function collapseGroupsByDefault(bool|\Closure $condition = true): static
    {
        $this->collapseGroupsByDefault = $condition;

        return $this;
    }

    public function shouldCollapseGroupsByDefault(): bool
    {
        return (bool) $this->evaluate($this->collapseGroupsByDefault);
    }

    public function getGroupedOptions(): array
    {
        if ($this->groupedSearchCallback !== null) {
            $groups = $this->evaluate($this->groupedSearchCallback, [
                'search' => $this->getSearchValue(),
            ]) ?? [];
        } else {
            $groups = $this->evaluate($this->groupedOptions) ?? [];
        }

        $normalized = [];

        foreach ($groups as $groupLabel => $children) {
            if (! is_array($children)) {
                continue;
            }

            $normalized[$groupLabel] = $children;
        }

        return $normalized;
    }

    protected function getFlattenedGroupedOptions(): array
    {
        return $this->flattenGroupTree($this->getGroupedOptions());
    }

    /**
     * Recursively collect every leaf option (value => label) from a (possibly
     * nested) grouped tree. Array values are treated as subgroups, scalar
     * values as selectable options.
     *
     * @param  array<mixed>  $nodes
     * @return array<mixed>
     */
    public function flattenGroupTree(array $nodes): array
    {
        $flat = [];

        foreach ($nodes as $key => $value) {
            if (is_array($value)) {
                foreach ($this->flattenGroupTree($value) as $leafValue => $leafLabel) {
                    $flat[$leafValue] = $leafLabel;
                }

                continue;
            }

            $flat[$key] = $value;
        }

        return $flat;
    }

    public function optionsLimit(int|\Closure $limit): static
    {
        $this->optionsLimit = $limit;
        return $this;
    }

    public function maxItemsShown(int|\Closure $limit): static
    {
        $this->maxItemsShown = $limit;
        return $this;
    }

    public function getMaxItemsShown(): int
    {
        return $this->evaluate($this->maxItemsShown);
    }

    public function getOptionsLimit(): int
    {
        return $this->evaluate($this->optionsLimit);
    }

    public function getSearchValue(): string
    {
        $livewire = $this->getLivewire();

        if (in_array(HasDropdownSearch::class, class_uses_recursive($livewire))) {
            $value = data_get($livewire->filterSearches, $this->getStatePath());
        } else {
            $value = data_get($livewire, $this->getStatePath() . '_search');
        }

        return is_string($value) ? $value : '';
    }

    public function hasDropdownSearch(): bool
    {
        return in_array(
            HasDropdownSearch::class,
            class_uses_recursive($this->getLivewire())
        );
    }

    public function getOptions(): array
    {
        if ($this->hasGroupedOptions()) {
            $options = $this->getFlattenedGroupedOptions();

            return $this->mergeSelectedOptionLabels($options);
        }

        if ($this->searchCallback) {
            $options = $this->evaluate($this->searchCallback, [
                'search' => $this->getSearchValue(),
            ]) ?? [];
        } else {
            $options = parent::getOptions();
        }

        $limit = $this->getOptionsLimit();
        if (count($options) > $limit) {
            $options = array_slice($options, 0, $limit, true);
        }

        return $this->mergeSelectedOptionLabels($options);
    }

    protected function mergeSelectedOptionLabels(array $options): array
    {
        if (! $this->selectedLabelsCallback) {
            return $options;
        }

        $state = $this->getState();

        if (! is_array($state) || empty($state)) {
            return $options;
        }

        $selectedLabels = $this->evaluate($this->selectedLabelsCallback, [
            'values' => $state,
        ]) ?? [];

        if (is_array($selectedLabels)) {
            foreach ($selectedLabels as $val => $lbl) {
                if (! isset($options[$val])) {
                    $options[$val] = $lbl;
                }
            }
        }

        return $options;
    }
}