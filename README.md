# Filament Dropdown Checkbox List

A searchable dropdown CheckboxList component for Filament v3.

Wraps a standard `CheckboxList` inside a dropdown trigger with badge-style selected value display, server-side search support, and persistent selection state.

## Features

- Dropdown trigger showing selected values as badges (up to 3), then a counter
- Server-side search via a callback
- Client-side search (default, no callback needed)
- Selected options always visible in the list regardless of search query
- Bulk toggle (select all / deselect all) scoped to visible options
- Options limit to prevent rendering thousands of items
- Full dark mode support
- Translatable strings (English and Polish included)

## Requirements

- PHP 8.1+
- Filament 3.x

## Installation

```bash
composer require alenadashko/filament-dropdown-checkbox-list
```

## Usage

### Client-side search (simple)

Options are loaded once, filtering happens in the browser:

```php
use AlenaDashko\DropdownCheckboxList\Components\DropdownCheckboxList;

DropdownCheckboxList::make('tags')
    ->options(Tag::pluck('name', 'id'))
```

### Server-side search

Options are loaded from a callback on each search query. Use this for large datasets:

```php
DropdownCheckboxList::make('products')
    ->searchUsing(fn (string $search) => Product::where('name', 'like', "%{$search}%")
        ->limit(50)
        ->pluck('name', 'id')
        ->toArray()
    )
    ->selectedOptionLabelsUsing(fn (array $values) => Product::whereIn('id', $values)
        ->pluck('name', 'id')
        ->toArray()
    )
```

`selectedOptionLabelsUsing` is required when using `searchUsing` — it fetches labels
for already-selected values so they remain visible when the search query changes.

### Options limit

Prevents rendering too many options at once (default: 50):

```php
DropdownCheckboxList::make('categories')
    ->options(Category::pluck('name', 'id'))
    ->optionsLimit(100)
```

## Isolated search state (optional)

By default the component stores search state in a Livewire property named
`{statePath}_search`. If your Livewire component uses table filters and you want
to prevent the search from interfering with them, add the `HasDropdownSearch` trait:

```php
use AlenaDashko\DropdownCheckboxList\Concerns\HasDropdownSearch;

class MyListResource extends Component
{
    use HasDropdownSearch;
}
```

This stores search state in a dedicated `filterSearches` array instead.

## Publishing assets

Publish views to customise the template:

```bash
php artisan vendor:publish --tag=dropdown-checkbox-list-views
```

Publish translations:

```bash
php artisan vendor:publish --tag=dropdown-checkbox-list-lang
```

## Adding translations

After publishing, add a new language file at:

```
lang/vendor/dropdown-checkbox-list/{locale}/messages.php
```

```php
return [
    'placeholder'    => 'Select...',
    'selected_count' => 'selected',
    'no_results'     => 'No results found',
    'select_all'     => 'Select all',
    'deselect_all'   => 'Deselect all',
];
```

## License

MIT