<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace OS\ToolsBundle\FormManager;

use OS\ToolsBundle\Util\Object;

/**
 * Description of AbstractFormModel
 *
 * @author ouardisoft
 */
abstract class AbstractFormModel
{
    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var object
     */
    protected $entity;

    public function __construct($name, $entityName)
    {
        $this->name = $name;
        $this->entity = new $entityName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 
     * @return string
     */
    public function getEntityName()
    {
        return get_class($this->getEntity());
    }

    /**
     * 
     * @return object
     */
    public function getEntity()
    {
        $o = new Object();
        $o->setFrom($this);
        $o->setTo($this->entity);
        $o->copy();

        return $this->entity;
    }

    /**
     * 
     * @param object $entity
     * @return \OS\ToolsBundle\FormManager\AbstractFormModel
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * 
     * @return \AbstractFormModel
     */
    public function createInstance()
    {
        return new static($this->getName(), $this->getEntityName());
    }

    /**
     * 
     * @param object $entity
     * @return object
     */
    public function modelFromEntity($entity)
    {
        $o = new Object();
        $o->setFrom($entity);
        $o->setTo($this);
        $o->copy();

        return $this;
    }
}
