<?php

namespace AlenaDashko\DropdownCheckboxList\Components;

use AlenaDashko\DropdownCheckboxList\Concerns\HasDropdownSearch;
use Filament\Forms\Components\CheckboxList;

class DropdownCheckboxList extends CheckboxList
{
    protected string $view = 'dropdown-checkbox-list::components.dropdown-checkbox-list';

    protected ?\Closure $searchCallback = null;
    protected ?\Closure $selectedLabelsCallback = null;
    protected int|\Closure $optionsLimit = 50;

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

    public function optionsLimit(int|\Closure $limit): static
    {
        $this->optionsLimit = $limit;
        return $this;
    }

    public function getOptionsLimit(): int
    {
        return $this->evaluate($this->optionsLimit);
    }

    public function getSearchValue(): string
    {
        $livewire = $this->getLivewire();

        if (in_array(HasDropdownSearch::class, class_uses_recursive($livewire))) {
            return data_get($livewire->filterSearches, $this->getStatePath(), '');
        }

        return data_get($livewire, $this->getStatePath() . '_search', '');
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

        $state = $this->getState();
        if (is_array($state) && !empty($state) && $this->selectedLabelsCallback) {
            $selectedLabels = $this->evaluate($this->selectedLabelsCallback, [
                'values' => $state,
            ]) ?? [];

            if (is_array($selectedLabels)) {
                foreach ($selectedLabels as $val => $lbl) {
                    if (!isset($options[$val])) {
                        $options[$val] = $lbl;
                    }
                }
            }
        }

        return $options;
    }
}