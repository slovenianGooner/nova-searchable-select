<?php

namespace Sloveniangooner\SearchableSelect;

use Laravel\Nova\Fields\Select;
use Laravel\Nova\Nova;

class SearchableSelect extends Select
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'searchable-select';

    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);
        $this->withMeta([
            "label" => null,
            "labelPrefix" => null,
            "valueField" => "id",
            "isMultiple" => false,
            "max" => 20,
            "searchable" => false
        ]);
    }

    public function max($max)
    {
        return $this->withMeta([
            "max" => $max
        ]);
    }

    public function multiple()
    {
        return $this->withMeta([
            "isMultiple" => true
        ]);
    }

    public function resource($name)
    {
        // Find searchable resource based on name if it's a class
        if (class_exists($name)) {
            $name = $name::uriKey();
        }

        $meta = [
            "searchableResource" => $name
        ];

        if (!isset($this->meta["label"])) {
            $resource = Nova::resourceForKey($name);
            $meta["label"] = $resource::$title;
        }

        return $this->withMeta($meta);
    }

    public function label($label)
    {
        return $this->withMeta([
            "label" => $label
        ]);
    }

    public function labelPrefix($labelPrefix)
    {
        return $this->withMeta([
            "labelPrefix" => $labelPrefix
        ]);
    }

    public function value($value)
    {
        return $this->withMeta([
            "valueField" => $value
        ]);
    }

    public function displayUsingLabels()
    {
        return $this->displayUsing(function ($value) {
            $model = Nova::modelInstanceForKey($this->meta["searchableResource"]);
            if (!$model) {
                return $value;
            }

            $labelValue = $model->where($this->meta["valueField"], $value)->value($this->meta["label"]);

            if (!$labelValue) {
                return $value;
            }

            return $labelValue;
        });
    }

    public function loadResourcesOnNew()
    {
        return $this->withMeta([
            "loadResourcesOnNew" => true
        ]);
    }
    
    public function useBaseSearch($useBaseSearch = true)
    {
        return $this->withMeta([
            "searchable" => $useBaseSearch
        ]);
    }
}
