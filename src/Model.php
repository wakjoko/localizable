<?php

namespace Wakjoko\Localizable;

class Model extends \Illuminate\Database\Eloquent\Model
{
    public const uniqueKey = [
        'model_type',
        'model_id',
        'attribute',
        'locale'
    ];

    public $fillable = [
        /**
         * attribute should be listed in related model's $localizable array
         */
        'attribute',

        /**
         * locale could be either:
         * two-letter language code string -> https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
         * or
         * three-letter country code string -> https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
         */
        'locale',

        /**
         * the localized value of an attribute
         */
        'value'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        $this->setConnection(config('localizable.db'));
        $this->setTable(config('localizable.table'));
    }

    public function scopeWhereCriteria($query, $attributes = null, $locales = null, $values = null)
    {
        $query->where(fn ($query) =>
            $query
                ->when($attributes, fn ($query) =>
                    $query->where(function ($query) use ($attributes) {
                        foreach ((array) $attributes as $attribute) {
                            $query->orWhere('attribute', $attribute);
                        }
                    })
                )
                ->when($locales, fn ($query) =>
                    $query->where(function ($query) use ($locales) {
                        foreach ((array) $locales as $locale) {
                            $query->orWhere('locale', $locale);
                        }
                    })
                )
                ->when($values, fn ($query) =>
                    $query->where(function ($query) use ($values) {
                        foreach ((array) $values as $value) {
                            $query->orWhere('value', 'like', "%{$value}%");
                        }
                    })
                )
        );
    }
}