<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace OS\ToolsBundle\FormManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use OS\ToolsBundle\EntityManager\AbstractEntityManager;
use OS\ToolsBundle\Provider\AbstractProvider;

/**
 * Description of AbstractFormHandler
 *
 * @author ouardisoft
 */
abstract class AbstractFormHandler
{
    /**
     *
     * @var Request 
     */
    protected $request;

    /**
     *
     * @var AbstractEntityManager
     */
    protected $manager;

    /**
     *
     * @var AbstractProvider
     */
    protected $provider;

    public function __construct(Request $request, AbstractEntityManager $manager, AbstractProvider $provider)
    {
        $this->request = $request;
        $this->manager = $manager;
        $this->provider = $provider;
    }

    /**
     * 
     * @param \OS\CommonBundle\FormManager\Form $form
     * @return boolean|object
     */
    public function process(Form $form, array $options = array(), $data = null)
    {
        if (!$this->request->isMethod('POST')) {
            if ($data) {
                $model = $form->getData();
                $model->modelFromEntity($data);
                $form->setData($model);
            }

            return false;
        }

        $form->bind($this->request);

        if ($form->isValid()) {
            $model = $form->getData();

            if ($data) {
                $model->setEntity($data);
            }

            $entity = $model->getEntity();

            return $this->processValidForm($form, $options, $entity);
        }

        return false;
    }

    /**
     * 
     * @param \Symfony\Component\Form\Form $form
     * @param array $options
     */
    abstract protected function processValidForm(Form $form, array $options, $data);
}
