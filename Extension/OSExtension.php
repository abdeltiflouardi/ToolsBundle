<?php

namespace OS\ToolsBundle\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use OS\CoreBundle\Helper\OSHelper;

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

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->os = $this->container->get('os.templating.helper');
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
        );
    }

    public function rtrans($word, $domaine = 'messages', $locale = null)
    {
        return $this->os->rtrans($word, $domaine, $locale);
    }

    public function getName()
    {
        return 'os_extension';
    }

}
