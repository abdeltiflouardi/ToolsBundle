<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace OS\ToolsBundle\FormManager;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormFactoryInterface;
use OS\ToolsBundle\FormManager\AbstractFormModel;

/**
 * Description of AbstractFormFacotry
 *
 * @author ouardisoft
 */
abstract class AbstractFormFactory
{

    /**
     * The Symfony form factory
     *
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * The message form type
     *
     * @var AbstractType
     */
    protected $formType;

    /**
     * The name of the form
     *
     * @var string
     */
    protected $formName;

    /**
     * The object model
     *
     * @var AbstractFormModel
     */
    protected $formModel;

    public function __construct(
        FormFactoryInterface $formFactory,
        AbstractType $formType,
        $formName,
        AbstractFormModel $formModel
    ) {
        $this->formFactory = $formFactory;
        $this->formType = $formType;
        $this->formName = $formName;
        $this->formModel = $formModel;
    }

    /**
     * @return Form
     */
    public function create(array $options = array())
    {
        $model = $this->formModel->createInstance();

        return $this->formFactory->createNamed($this->formName, $this->formType, $model, $options);
    }
}
