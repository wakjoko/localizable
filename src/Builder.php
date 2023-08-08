<?php

namespace Wakjoko\Localizable;

use Illuminate\Support\Facades\DB;

class Builder extends \Illuminate\Database\Eloquent\Builder
{
    protected $attributes = null;
    protected $locales = null;
    protected $values = null;

    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();

        if (count($models = $builder->getModels($columns)) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        foreach ($models as $model) {
            $model->formatLocalizable($this->attributes, $this->locales);
        }

        return $builder->getModel()->newCollection($models);
    }

    /**
     * shorthand for whereHasLocalizable() + loadLocalizableData()
     */
    public function withLocalizable($attribute = null, $locale = null, $value = null)
    {
        $this->remember($attribute, $locale, $value);

        $this
            ->whereHasLocalizable()
            ->loadLocalizableData();

        return $this;
    }

    /**
     * query to see if this model has any localized data requested
     */
    public function whereHasLocalizable($attribute = null, $locale = null, $value = null)
    {
        $this->remember($attribute, $locale, $value);

        $this->whereHas('multilingual', fn ($query) =>
            $query
                ->select(DB::raw(1))
                ->whereCriteria($this->attributes, $this->locales, $this->values)
        );

        return $this;
    }

    /**
     * preload any localized data requested
     */
    public function loadLocalizableData($attribute = null, $locale = null)
    {
        $this->modelsShouldPreventLazyLoading = true;

        $this->remember($attribute, $locale);

        /** 
         * common logic:
         * should include all locale values when any of locale value matching is found.
         * so adding filter by value in this query is not humanly logical
        */

        $this->with('multilingual', fn ($query) =>
            $query
                ->select('model_id', 'attribute', 'locale', 'value')
                ->whereCriteria($this->attributes, $this->locales)
        );

        return $this;
    }

    private function remember($attribute = null, $locale = null, $value = null)
    {
        if ($attribute) {
            $this->attributes = $this->removeNulls($attribute);
        }

        if ($locale) {
            $this->locales = $this->removeNulls($locale);
        }

        if ($value) {
            $this->values = $this->removeNulls($value);
        }
    }

    private function removeNulls($array = null)
    {
        return array_filter((array) $array, fn ($value) => !is_null($value));
    }
}