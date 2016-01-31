<?php
namespace Minhbang\Option;

use View;

/**
 * Class OptionableController
 *
 * @package Minhbang\Option
 */
trait OptionableController
{
    /**
     * @var \Minhbang\Option\Option
     */
    public $options;

    /**
     * @return array
     */
    abstract protected function optionConfig();

    protected function bootOptionableController()
    {
        if (is_null($this->options)) {
            $config = $this->optionConfig() + ['zone' => null, 'group' => null, 'class' => null];
            if (!$config['zone'] || !$config['group'] || !$config['class']) {
                throw new OptionException("OptionableController Trait: 'zone' & 'group' & 'class' not empty!");
            }
            $this->options = new $config['class']($config['zone'], $config['group']);
            View::share("{$config['group']}_options", $this->options);
        }
    }

    /**
     * @param  \Illuminate\Database\Query\Builder|\Minhbang\Kit\Extensions\Model $query
     * @param bool $position
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function optionAppliedPaginate($query, $position = false)
    {
        list($column, $direction) = $this->options->column('sort');
        if ($column) {
            $query->orderBy($column, $direction);
        } else {
            if ($position) {
                $query->orderPosition();
            }
        }

        return $query->paginate($this->options->get('page_size', 6));
    }
}