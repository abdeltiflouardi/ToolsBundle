<?php

namespace OS\ToolsBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author ouardisoft
 */
class OSHelper extends Helper
{

    /**
     *
     * @var ContainerInterface 
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function param($key, $default = null)
    {
        if (false == $this->container->hasParameter($key)) {
            return $default;
        }

        return $this->container->getParameter($key);
    }

    public function rtrans($word, $domaine = 'messages', $locale = null)
    {
        if (!$locale) {
            $locale = $this->container->get('request')->getLocale();
        }

        $translator = $this->container->get('translator');
        $translator->trans($word, array(), $domaine, $locale);

        $ref = new \ReflectionProperty($translator, 'catalogues');
        $ref->setAccessible(true);

        $domaines  = $ref->getValue($translator);
        $catalogue = $domaines[$locale];
        $messages  = $catalogue->all();

        return array_search($word, $messages[$domaine]);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array($name, $arguments);
    }

    public function getName()
    {
        return 'os_helper';
    }

}
