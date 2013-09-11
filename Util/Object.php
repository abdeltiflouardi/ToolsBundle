<?php

namespace OS\ToolsBundle\Util;

/**
 * @author ouardisoft
 */
class Object
{

    private $from;
    private $to;

    public function setTo($to)
    {
        $this->to = $to;
    }

    public function setFrom($from)
    {
        $this->from = $from;
    }

    public function copy(array $mapper = array(), array $except = array())
    {
        $reflect = new \ReflectionClass($this->from);
        $props   = $reflect->getProperties();

        foreach ($props as $prop) {
            $name = $prop->getName();
            if (array_key_exists($name, $except)) {
                continue;
            }

            $prop->setAccessible(true);
            $value = $prop->getValue($this->from);

            if (array_key_exists($name, $mapper)) {
                $name = $mapper[$name];
            }

            $this->setPropertyValue($this->to, $name, $value);
        }
    }

    public function setPropertyValue($obj, $propertyName, $propertyValue)
    {
        $reflect = new \ReflectionClass($obj);
        if ($reflect->hasProperty($propertyName)) {
            $prop = $reflect->getProperty($propertyName);

            $prop->setAccessible(true);
            $prop->setValue($obj, $propertyValue);
        }
    }
}

