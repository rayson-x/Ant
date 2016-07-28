<?php
namespace Ant\Database;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $this->convertToArray($items);
    }

    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * �Ƿ�Ϊ��
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    public function toArray()
    {
        return array_map(function ($value) {
            return ($value instanceof Model || $value instanceof self) ? $value->toArray() : $value;
        }, $this->items);
    }

    public function all()
    {
        return $this->items;
    }

    /**
     * �ϲ�����
     *
     * @param  mixed $items
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->convertToArray($items)));
    }

    /**
     * �Ƚ����飬���ز
     *
     * @param  mixed $items
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->convertToArray($items)));
    }


    /**
     * ���������еļ���ֵ
     *
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * �Ƚ����飬���ؽ���
     *
     * @param  mixed $items
     * @return static
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->convertToArray($items)));
    }

    /**
     * �������������еļ���
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * ɾ����������һ��Ԫ�أ���ջ��
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }


    /**
     * ͨ��ʹ���û��Զ��庯�������ַ�����������
     *
     * @param  callable $callback
     * @param  mixed    $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * ���෴��˳�򷵻����顣
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items));
    }

    /**
     * ɾ���������׸�Ԫ�أ������ر�ɾ��Ԫ�ص�ֵ
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * ��һ������ָ�Ϊ�µ������.
     *
     * @param  int  $size
     * @param  bool $preserveKeys
     * @return static
     */
    public function chunk($size, $preserveKeys = false)
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, $preserveKeys) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * �����鿪ͷ����һ��Ԫ��
     * @param mixed $value
     * @param null  $key
     * @return int
     */
    public function unshift($value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($this->items, $value);
        } else {
            $this->items = [$key => $value] + $this->items;
        }
    }

    /**
     * ��ÿ��Ԫ��ִ�и��ص�
     *
     * @param  callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }


    /**
     * �ûص��������������е�Ԫ��
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    /**
     * ����������ָ����һ��
     * @param      $column_key
     * @param null $index_key
     * @return array
     */
    public function column($column_key, $index_key = null)
    {
        if (function_exists('array_column')) {
            return array_column($this->items, $column_key, $index_key);
        }

        $result = [];
        foreach ($this->items as $row) {
            $key    = $value = null;
            $keySet = $valueSet = false;
            if ($index_key !== null && array_key_exists($index_key, $row)) {
                $keySet = true;
                $key    = (string)$row[$index_key];
            }
            if ($column_key === null) {
                $valueSet = true;
                $value    = $row;
            } elseif (is_array($row) && array_key_exists($column_key, $row)) {
                $valueSet = true;
                $value    = $row[$column_key];
            }
            if ($valueSet) {
                if ($keySet) {
                    $result[$key] = $value;
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }


    /**
     * ����������
     *
     * @param  callable|null $callback
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;

        $callback ? uasort($items, $callback) : uasort($items, function ($a, $b) {

            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return new static($items);
    }


    /**
     * ���������
     *
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    /**
     * ��ȡ����
     *
     * @param  int  $offset
     * @param  int  $length
     * @param  bool $preserveKeys
     * @return static
     */
    public function slice($offset, $length = null, $preserveKeys = false)
    {
        return new static(array_slice($this->items, $offset, $length, $preserveKeys));
    }

    // ArrayAccess
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    //Countable
    public function count()
    {
        return count($this->items);
    }

    //IteratorAggregate
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    //JsonSerializable
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * ת����ǰ���ݼ�ΪJSON�ַ���
     * @access public
     * @param integer $options json����
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * ת��������
     *
     * @param  mixed $items
     * @return array
     */
    protected function convertToArray($items)
    {
        if ($items instanceof self) {
            return $items->all();
        }
        return (array)$items;
    }
}