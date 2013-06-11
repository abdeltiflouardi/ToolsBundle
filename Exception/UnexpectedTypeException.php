<?php

namespace OS\ToolsBundle\Exception;

class UnexpectedTypeException extends \RuntimeException
{

    public function __construct($value, $expectedType)
    {
        parent::__construct(sprintf('Expected argument of type %s, %s given', $expectedType, gettype($value)));
    }
}
