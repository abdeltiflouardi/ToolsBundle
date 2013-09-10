<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OS\ToolsBundle\EntityManager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Description of AbstractEntityManager
 *
 * @author ouardisoft
 */
abstract class AbstractEntityManager
{

    /**
     *
     * @var EntityManager 
     */
    protected $em;

    /**
     *
     * @var EntityRepository
     */
    protected $repository;

    /**
     *
     * @var
     */
    protected $class;

    /**
     * 
     */
    public function __construct(EntityManager $em, $class)
    {
        $this->em = $em;
        $this->repository = $em->getRepository($class);
        $this->class = $class;
    }

    /**
     * 
     * @param object $entity
     */
    public function save($entity, $flush = true)
    {
        $this->em->persist($entity);

        if ($flush) {
            $this->em->flush();
        }
    }
}
