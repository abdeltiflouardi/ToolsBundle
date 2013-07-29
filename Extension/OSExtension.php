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
            'time_ago' => new \Twig_Filter_Method($this, 'getTimeAgo'),
            'strip_html_tags' => new \Twig_Filter_Method($this, 'stripHtmlTags'),
        );
    }

    /**
     * 
     * @param \DateTime $date
     * @param boolean $displayInterval
     */
    public function getTimeAgo($date, $displayInterval = true)
    {
        return $this->os->getTimeAgo($date, $displayInterval);
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            'param' => new \Twig_Function_Method($this, 'param'),
            'is_route' => new \Twig_Function_Method($this, 'isRoute'),
            'pager_pagination' => new \Twig_Function_Method($this, 'pagerPagination'),
         );
    }

    public function pagerPagination($page, $template, $options = array())
    {
        return $this->os->pagerPagination($page, $template, $options);
    }

    public function isRoute($route, $isStartWith = false)
    {
        return $this->os->isRoute($route, $isStartWith);
    }

    public function rtrans($word, $domaine = 'messages', $locale = null)
    {
        return $this->os->rtrans($word, $domaine, $locale);
    }

    public function stripHtmlTags($text, array $tags = array(), $useDefaults = false)
    {
        return $this->os->stripHtmlTags($text, $tags, $useDefaults);
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
