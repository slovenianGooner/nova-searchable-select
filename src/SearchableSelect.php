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
            "label" => "title",
            "valueField" => "id",
            "isMultiple" => false
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
        
        return $this->withMeta([
            "searchableResource" => $name
        ]);
    }

    public function label($label)
    {
        return $this->withMeta([
            "label" => $label
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
}
