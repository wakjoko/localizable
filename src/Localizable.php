<?php

namespace Wakjoko\Localizable;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Localizable
{
    protected ?string $locale = null;

    /**
     * localized attributes storage
     * format is: [$key => [$locale => $value]]
     */
    protected $localized = [];

    /**
     * get all localized values or specify one.
     * usage example:
     * 
     * $model->getLocalized('name', 'ms');
     * $model->getLocalized('title', 'en');
     * $model->getLocalized('description', 'ch');
     */
    public function getLocalized(string $key = null, string $locale = null)
    {
        if ($key && $locale) {
            return $this->getAttribute($key, $locale);
        }

        return $this->localized;
    }

    /**
     * set localized value with specified language.
     * usage example:
     * 
     * $model->setLocalized('name', '迈克尔', 'ch');
     * $model->setLocalized('title', 'Mr', 'en');
     * $model->setLocalized('description', 'Saya orang Cina', 'ms');
     */
    public function setLocalized(string $key, string $value, string $locale)
    {
        return $this->setAttribute($key, [$locale => $value]);
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * move and transform data loaded from relation into respective localizable attribute names.
     */
    public function formatLocalizable($attributes = null, $locales = null)
    {
        if (!$this->relationLoaded('multilingual')) {
            return $this;
        }

        $attributes = !$attributes ? $this->getLocalizableAttributes() : (array) $attributes;

        foreach ($attributes as $key) {
            $value = null;

            if (!$locales || count($locales) > 1) {
                $value = $this->multilingual
                    ->where('attribute', $key)
                    ->pluck('value', 'locale')
                    ->toArray();
            }
            else {
                $localized = $this->multilingual->where('attribute', $key)->first();
                $value = $localized ? [$locales[0] => $localized->value] : null;
            }

            if (!$value) {
                continue;
            }

            $this->setAttribute($key, $value);
        }

        $this->unsetRelation('multilingual');

        return $this;
    }

    /**
     * get localized value with automated language guessing.
     * usage example:
     * 
     * optionally set the language:
     * $model->setLocale('en'); // model specific
     * app()->setLocale('nl');  // or app wide globally
     * 
     * then get the localized value by any localizable attribute name:
     * 
     * $model->name;
     * $model->title;
     * $model->description;
     */
    public function getAttribute($key, string $locale = null)
    {
        if ($this->isLocalizableAttribute($key)) {
            return $this->getLocalizedValue($key, $locale ?? $this->getLocale());
        }
        
        return parent::getAttribute($key);
    }

    /**
     * set localized value with automated language guessing.
     * $person->name = ['ch' => '迈克尔'];
     */
    public function setAttribute($key, $value)
    {
        if ($this->isLocalizableAttribute($key)) {
            return $this->setLocalizedValue($key, $value);
        }
        
        return parent::setAttribute($key, $value);
    }

    /**
     * create or update for multiple languages:
     * $person->name = [
     *  'en' => 'Michael',
     *  'ms' => 'Mikail',
     *  'ch' => '迈克尔',
     * ];
     * 
     * $person->save();
     * 
     * or
     * 
     * Person::create([
     *  'title' => 'Mr.',
     *  'name' => [
     *      'en' => 'Michael',
     *      'ms' => 'Mikail',
     *      'ch' => '迈克尔',
     *  ]
     * ]);
     */

    public function fill(array $attributes)
    {
        $attributes = $this->fillLocalizable($attributes);

        return parent::fill($attributes);
    }

    protected function fillLocalizable(array $attributes)
    {
        $localizable = $this->getLocalizableAttributes();

        foreach ($attributes as $key => $value) {
            if (!in_array($key, $localizable)) {
                continue;
            }

            $this->setLocalizedValue($key, $value);

            /**
             * important:
             * exclude localizable attributes from default eloquent storage
             */
            unset($attributes[$key]);
        }

        return $attributes;
    }

    protected function finishSave(array $options)
    {
        $this->savingLocalizable();
        parent::finishSave($options);
    }

    protected function savingLocalizable(): bool
    {
        $data = null;

        foreach ($this->localized as $key => $value) {
            $localizable = $this->transformToLocalizable($key, $value);
            $data = array_merge($data ?? [], $localizable);
        }

        return $data && $this->saveLocalizable($data) > 0;
    }

    /**
     * permanently delete all localized values relates to this model
     */
    public function delete()
    {
        return parent::delete() + $this->deleteLocalizable();
    }

    protected function getLocalizedValue(string $key, string $locale)
    {
        $value = null;

        if (array_key_exists($key, $this->localized)) {
            $localized = $this->localized[$key];

            if (array_key_exists($locale, $localized)) {
                $value = $localized[$locale];
            }
        }
        
        if (!$value && $this->exists) {
            $value = $this->queryLocalized($key, $locale);
            $this->setLocalizedValue($key, [$locale => $value]);
        }

        return $value;
    }

    protected function setLocalizedValue(string $key, $value)
    {
        if (!$value) {
            return $this;
        }
        /**
         * convert string $value to array format
         */
        if (!Arr::isAssoc((array) $value)) {
            $locale = $this->getLocale();
            $value = [$locale => $value];
        }

        /**
         * always overwrite existing storage
         */
        if (array_key_exists($key, $this->localized)) {
            $value = array_merge($this->localized[$key], $value);
        }

        $this->localized[$key] = $value;

        return $this;
    }

    /**
     * get the localized value from database
     */
    protected function queryLocalized(string $attribute, string $locale)
    {
        $localized = $this->multilingual()
            ->select('value')
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->toBase()
            ->first();

        return $localized->value ?? null;
    }

    /**
     * relationship to get all localizable attributes along with all locales
    */
    public function multilingual(): MorphMany
    {
        return $this->morphMany(Model::class, 'model');
    }

    protected function saveLocalizable(array $data): int
    {
        return $this->multilingual()->upsert($data, Model::uniqueKey);
    }

    protected function deleteLocalizable()
    {
        return $this->multilingual()->delete();
    }

    /**
     * transform the $attribute's value into storable data structure
     */
    protected function transformToLocalizable(string $attribute, array $locales): array
    {
        $data = [];

        foreach ($locales as $locale => $value) {
            $data[] = [
                'model_type' => $this->getMorphClass(),
                'model_id' => $this->id,
                'attribute' => $attribute,
                'locale' => $locale,
                'value' => $value,
            ];
        }

        return $data;
    }

    public function isLocalizableAttribute(string $key): bool
    {
        return in_array($key, $this->getLocalizableAttributes());
    }

    public function getLocalizableAttributes(): array
    {
        return property_exists($this, 'localizable') && is_array($this->localizable)
            ? $this->localizable
            : [];
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale()
    {
        return $this->locale ?: config('app.locale');
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), $this->localized);
    }

    protected function mergeAttributesFromLocalized()
    {
        $this->attributes = array_merge($this->attributes, $this->localized);
    }

    /**
     * make localizable data visible in tinker
     */
    public function getAttributes()
    {
        parent::getAttributes();
        
        $this->mergeAttributesFromLocalized();

        return $this->attributes;
    }

    public function getDirty()
    {
        $dirty = parent::getDirty();

        $localizable = $this->getLocalizableAttributes();

        foreach (array_keys($dirty) as $key) {
            /**
             * important:
             * exclude localizable attributes as dirty
             */
            if (in_array($key, $localizable)) {
                unset($dirty[$key]);
            }
        }

        return $dirty;
    }

    /**
     * get localized attribute
     * $model->name('en')
     * $model->title('ch')
     * $model->name()       // or use default locale
     * 
     * set localized attribute
     * $model->name('en', 'oh my dear')
     */
    public function __call($method, $parameters)
    {
        if ($this->isLocalizableAttribute($method)) {
            return count($parameters) > 1 ?
                $this->setLocalizedValue($method, [$parameters[0] => $parameters[1]]) :
                $this->getLocalizedValue($method, $parameters[0] ?? $this->getLocale());
        }

        return parent::__call($method, $parameters);
    }
}