<?php

namespace Nbz4live\LaravelBackpackHelpers\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

trait FiltersTrait
{
    /**
     * Adds an soft delete filter
     */
    protected function addSoftDeleteFilter()
    {
        $this->crud->addFilter([
            'type' => 'simple',
            'name' => 'trashed' ,
            'label'=> 'Trashed'
        ], false, function () {
            $this->crud->query = $this->crud->query->onlyTrashed();
        });
    }

    /**
     * Adds an advanced select 2 filter with custom callbacks, etc.
     * @param $name
     * @param callable $optionsCallback
     * @param null $label
     * @param null $column
     * @param bool $multiple
     * @param callable|null $valueCallback
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addAdvancedSelect2Filter(
        $name,
        callable $optionsCallback,
        $label = null,
        $column = null,
        $multiple = false,
        callable $valueCallback = null
    ) {
        $label = $label ?: \ucfirst($name);
        $column = $column ?: $name;

        $this->crud->addFilter(
            [
                'type' => 'select2' . ($multiple ? '_multiple' : ''),
                'name' => $name,
                'label'=> $label
            ],
            $optionsCallback,
            function ($values) use ($name, $column, $valueCallback) {
                $values = \json_decode($values);

                if (\is_array($values) && !empty($values)) {
                    $where = [];

                    foreach ($values as $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $where[] = (int) $value;
                    }

                    if (!empty($where)) {
                        if (\is_callable($valueCallback)) {
                            $where = $valueCallback($where, $column, $values);
                        }

                        $this->crud->addClause('whereIn', $column, $where);
                    }
                }
            }
        );
    }

    /**
     * Adds a text filter
     * @param $name
     * @param null $label
     * @param null $column
     * @param callable|null $valueCallback
     * @param string $operator
     */
    protected function addTextFilter(
        $name,
        $label = null,
        $column = null,
        callable $valueCallback = null,
        $operator = '='
    ) {
        $label = $label ?: \ucfirst($name);
        $column = $column ?: $name;

        $this->crud->addFilter(
            [
                'type' => 'text',
                'name' => $name,
                'label' => $label
            ],
            null,
            function ($value) use ($name, $column, $valueCallback, $operator) {
                if (\is_callable($valueCallback)) {
                    $value = $valueCallback($value, $column);
                }

                if (!empty($value)) {
                    $this->crud->addClause('where', $column, $operator, $value);
                }
            }
        );
    }

    protected function addStartsWithFilter($name, $label = null, $column = null)
    {
        $this->addTextFilter($name, $label, $column, function ($value) {
            return $value . '%';
        }, 'LIKE');
    }

    protected function addIntegerFilter($name, $label = null, $column = null)
    {
        $this->addTextFilter($name, $label, $column, function ($value) {
            return (int) $value;
        });
    }

    /**
     * @param string $name html form element Name
     * @param string $label html Label
     * @param string $field DB field name
     * @param bool $unixTimestamp
     * @param bool $milliseconds
     */
    protected function addDateRangeFilter(
        $name,
        $label = null,
        $field = null,
        $unixTimestamp = true,
        $milliseconds = false
    ) {
        if (!$field) {
            $field = $name;
        }
        $this->crud->addFilter([
            'name' => $name,
            'type' => 'date_range',
            'label' => $label ?: \ucfirst($name),
            'date_range_options' => [
                'timePicker' => true,
                'timePicker24Hour' => true,
                'showDropdowns' => true,
                'locale' => ['format' => 'DD/MM/YYYY HH:mm']
            ],
        ], false, function ($value) use ($field, $unixTimestamp, $milliseconds) {
            $dates = \json_decode($value);

            $from = Carbon::parse($dates->from)->startOfDay();
            $to = Carbon::parse($dates->to)->endOfDay();

            if ($unixTimestamp) {
                $from = $from->timestamp;
                $to = $to->timestamp;

                if ($milliseconds) {
                    $from *= 1000;
                    $to *= 1000;
                }
            }

            $this->crud->addClause('where', $field, '>=', $from);
            $this->crud->addClause('where', $field, '<=', $to);
        });
    }

    /**
     * Adds an advanced select 2 filter which searches for model values.
     * @param $name
     * @param null $label
     * @param null $column
     */
    protected function addModelFilter(Model $model, $displayProperty, $name, $label = null, $column = null)
    {
        $column = $column ?? $name;

        $this->crud->addFilter(
            [
                'type' => 'select2_multiple',
                'name' => $name,
                'label'=> $label ?: \ucfirst($name)
            ],
            function () use ($model, $displayProperty) {
                return $model::all()->keyBy(with(new $model)->getKeyName())
                    ->transform(function ($item) use ($displayProperty) {
                        return $item[$displayProperty];
                    })->toArray();
            },
            function ($values) use ($column) {
                $values = \json_decode($values);


                if (\is_array($values) && !empty($values)) {
                    $where = [];

                    foreach ($values as $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $where[] = (int) $value;
                    }

                    if (!empty($where)) {
                        $this->crud->addClause('whereIn', $column ?: $name, $where);
                    }
                }
            }
        );
    }
}
