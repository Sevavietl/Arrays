<?php

namespace ThoroughPHP\Arrays;

class CompositeKeyArray extends BaseArray
{
    protected $offsets;

    private $value;

    public function offsetExists($offset)
    {
        $this->setOffsets($offset);

        return $this->walkThroughOffsets(
            $this->container,
            function ($array, $offset) {
                return isset($array[$offset]);
            },
            function () {
                return false;
            }
        );
    }

    public function offsetGet($offset)
    {
        $this->setOffsets($offset);

        return $this->walkThroughOffsets(
            $this->container,
            function ($array, $offset) {
                return $array[$offset];
            },
            $this->undefinedOffsetAction
        );
    }

    public function offsetSet($offset, $value)
    {
        $this->value = $value;

        $this->setOffsets($offset);

        $baseCaseAction = function (&$array, $offset) {
            $array[$offset] = $this->value;
        };

        $offsetNotExistsAction = function (&$array, $offset) use (
            $baseCaseAction,
            &$offsetNotExistsAction
        ) {
            $value = empty($this->offsets) ? $this->value : [];

            if ($offset !== []) {
                $array[$offset] =& $value;
            } else {
                $array[] =& $value;
            }

            if (empty($this->offsets)) {
                return;
            }

            return $this->walkThroughOffsets(
                $value,
                $baseCaseAction,
                $offsetNotExistsAction
            );
        };

        return $this->walkThroughOffsets(
            $this->container,
            $baseCaseAction,
            $offsetNotExistsAction
        );
    }

    public function offsetUnset($offset)
    {
        $this->setOffsets($offset);

        return $this->walkThroughOffsets(
            $this->container,
            function (&$array, $offset) {
                unset($array[$offset]);
            },
            $this->undefinedOffsetAction
        );
    }

    protected function setOffsets($offsets)
    {
        $this->offsets = is_array($offsets) ? $offsets : [$offsets];
    }

    protected function walkThroughOffsets(
        &$array,
        Callable $baseCaseAction,
        Callable $offsetNotExistsAction
    ) {
        $offset = array_shift($this->offsets);

        if (is_scalar($offset) && isset($array[$offset])) {
            if (empty($this->offsets)) {
                return $baseCaseAction($array, $offset);
            }

            return $this->walkThroughOffsets(
                $array[$offset],
                $baseCaseAction,
                $offsetNotExistsAction
            );
        }

        return $offsetNotExistsAction($array, $offset);
    }
}