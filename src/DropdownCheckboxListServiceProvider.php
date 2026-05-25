<?php

namespace AlenaDashko\DropdownCheckboxList;

use Illuminate\Support\ServiceProvider;

class DropdownCheckboxListServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'dropdown-checkbox-list'
        );

        $this->loadTranslationsFrom(
            __DIR__ . '/../resources/lang',
            'dropdown-checkbox-list'
        );

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/dropdown-checkbox-list'),
        ], 'dropdown-checkbox-list-views');

        $this->publishes([
            __DIR__ . '/../resources/lang' => lang_path('vendor/dropdown-checkbox-list'),
        ], 'dropdown-checkbox-list-lang');
    }
}