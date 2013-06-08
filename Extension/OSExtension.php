<?php

namespace OS\ToolsBundle\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use OS\ToolsBundle\Helper\OSHelper;

/**
 * @author ouardisoft
 */
class OSExtension extends \Twig_Extension
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var OSHelper 
     */
    private $os;

    /**
     * @var \Twig_Environment
     */
    protected $environment;
    private $request;
    private $templating;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->os = $this->container->get('os.templating.helper');
        $this->request = $container->get('request');
        $this->templating = $container->get('twig');
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $environment->addGlobal('os', $this->os);

        $this->environment = $environment;
    }

    public function getFilters()
    {
        parent::getFilters();

        return array(
            'rtrans' => new \Twig_Filter_Method($this, 'rtrans'),
            'param' => new \Twig_Filter_Method($this, 'param'),
        );
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            'param'        => new \Twig_Function_Method($this, 'param'),
            'pager_pagination'     => new \Twig_Function_Method($this, 'pagerPagination'),
         );
    }

    public function pagerPagination($page, $template, $options = array())
    {
        return $this->os->pagerPagination($page, $template, $options);
    }

    public function rtrans($word, $domaine = 'messages', $locale = null)
    {
        return $this->os->rtrans($word, $domaine, $locale);
    }

    public function param($name, $default = null)
    {
        return $this->os->param($name, $default);
    }

    public function getName()
    {
        return 'os_extension';
    }
}
