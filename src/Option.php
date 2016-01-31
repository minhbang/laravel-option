<?php
namespace Minhbang\Option;

use Request;

/**
 * Class Option
 * User session options
 * Todo: Lưu / Phục hồi options sử sụng cookie
 *
 * @package Minhbang\Option
 */
abstract class Option
{
    /**
     * @var array
     */
    protected $default = [];
    /**
     * @var string
     */
    protected $name;
    /**
     * @var array
     */
    protected $options = [];

    protected $all;

    /**
     * Định nghĩa tất cả các options
     *
     * @return array
     */
    abstract protected function all();

    /**
     * Ánh xạ giá trị trường thành field name => ngắn gọn + không cho client biết tên field thật
     * vd: sort_by = name.asc chuyển thành field 'title' sắp xếp tăng dần
     *
     * @return array
     */
    abstract protected function columns();

    /**
     * Regular Expressions kiểm tra dữ liệu input
     *
     * @return array
     */
    abstract protected function rules();

    /**
     * Giá trị mặc định của các options
     *
     * @param string $group
     *
     * @return array
     */
    abstract protected function config($group);

    /**
     * Option constructor.
     *
     * @param string $zone
     * @param string $group
     * @param array $default
     */
    public function __construct($zone, $group, $default = null)
    {
        $this->name = "{$zone}.{$group}";
        $default = $default ?: $this->config($group);
        $this->default = $default + $this->default;
        $this->load();
    }


    /**
     * Load các thiết lập
     */
    protected function load()
    {
        $rules = $this->rules();
        $default = session($this->name, []) + $this->default;
        foreach (array_keys($default) as $key) {
            $value = Request::get($key);
            $this->options[$key] = preg_match($rules[$key], $value) ? $value : $default[$key];
        }
        session([$this->name => $this->options]);
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|array
     */
    public function get($key = null, $default = null)
    {
        return array_get($this->options, $key, $default);
    }

    /**
     * @param string $key
     *
     * @param mixed $default
     *
     * @return string
     */
    public function column($key, $default = null)
    {
        list($column, $direction) = explode('.', $this->get($key), 2);
        $column = array_get($this->columns(), "{$key}.{$column}", $default);

        return [$column, $direction];
    }

    /**
     * @param string $key
     * @param string|int $value
     * @param mixed $default
     *
     * @return array
     */
    public function titles($key = null, $value = null, $default = null)
    {
        if (is_null($this->all)) {
            $this->all = $this->all();
        }
        $titles = array_get($this->all, $key, []);

        return is_null($value) ? $titles : (isset($titles[$value]) ? $titles[$value] : $default);
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function values($key)
    {
        $titles = $this->titles();

        return isset($titles[$key]) ? array_keys($titles[$key]) : [];
    }

    /**
     * Build url cho $key => $value
     *
     * @param string $key
     * @param mixed $value
     *
     * @return string
     */
    public function url($key, $value)
    {
        $query = Request::query();
        if ($key === 'page_size' && isset($query['page'])) {
            unset($query['page']);
        }

        return Request::url() . '?' . urldecode(http_build_query([$key => $value] + $query, null, '&'));
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function is($key, $value)
    {
        return $this->get($key) === $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $icon
     * @param string $tooltip
     * @param string $text
     *
     * @return string
     */
    public function link($key, $value, $icon, $tooltip = null, $text = null)
    {
        $icon = $icon ? "<i class=\"fa fa-$icon\"></i>" : '';
        $text = $icon && $text ? "$icon $text" : $icon . $text;
        $class = $this->is($key, $value) ? 'class="active"' : '';
        $tooltip_attr = $tooltip ? "data-toggle=\"tooltip\" data-title=\"$tooltip\"" : '';

        return "<a href=\"{$this->url($key, $value)}\" {$class} {$tooltip_attr}>{$text}</a>";
    }

    /**
     * @param string $key
     * @param string $label
     * @param string $tooltip
     *
     * @return string
     */
    public function select($key, $label = null, $tooltip = null)
    {
        $id = "{$this->name}-{$key}";
        $value = $this->get($key);
        $title = $this->titles($key, $value);
        $all = $this->titles($key);
        $lis = '';
        foreach ($all as $v => $t) {
            $lis .= "<li><a href=\"{$this->url($key, $v)}\">{$t}</a></li>";
        }
        $label_tag = $label ? "<label>$label</label>" : '';
        $tooltip_attr = $tooltip ? " data-toggle=\"tooltip\" data-title=\"$tooltip\"" : '';

        return <<<"SELECT"
$label_tag
<div class="dropdown"{$tooltip_attr}>
    <button id="$id" class="btn btn-xs btn-default dropdown-toggle" type="button" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
        $title <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" aria-labelledby="$id">$lis</ul>
</div>
SELECT;
    }
}