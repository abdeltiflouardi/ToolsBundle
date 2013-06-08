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
    private $request;
    private $templating;
    private $translator;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request = $container->get('request');
        $this->templating = $container->get('twig');
        $this->translator = $container->get('translator');
    }

    /**
     * Time ago
     */
    public function getTimeAgo($date, $displayInterval = true)
    {
        if (!$date instanceof \DateTime) {
            return;
        }

        $str = "";
        $now = new \DateTime();
        $interval = $now->diff($date);

        $invert = $interval->format('%R');
        if ($interval->y >= 1) {
            $str = static::pluralize($interval->y, $this->translator->trans('Year'));
        } elseif ($interval->m >= 1) {
            $str = static::pluralize($interval->m, $this->translator->trans('Month'));
        } elseif ($interval->d >= 1) {
            $str = static::pluralize($interval->d, $this->translator->trans('Day'));
        } elseif ($interval->h >= 1) {
            $str = static::pluralize($interval->h, $this->translator->trans('Hour'));
        } elseif ($interval->i >= 1) {
            $str = static::pluralize($interval->i, $this->translator->trans('Minute'));
        } elseif ($interval->s >= 1) {
            $str = static::pluralize($interval->s, $this->translator->trans('Second'));
        }

        if ($displayInterval) {
            $str = $invert . $str;
        }

        return $str;
    }

    /**
     *
     * @param int $count
     * @param string $text
     * @return string 
     */
    public static function pluralize($count, $text)
    {
        $str = (substr($text, -1) == 's' || $count == 1) ? $text : "${text}s";

        return sprintf("%s %s", $count, $str);
    }

    /**
     * return links of pagination
     */
    public function pagerPagination($pager, $template, $options = array())
    {
        $path = $this->getCurrentUrl(array('page'));

        $range_number = $this->param('pager_range');

        $separator = strpos($path, '?') ? '&' : '?';
        $path .= $separator;

        $start = ($pager->getCurrentPage() > $range_number) ? $pager->getCurrentPage() - $range_number : 1;
        $end   = ($pager->getCurrentPage() + $range_number > $pager->getNbPages())
                ? $pager->getNbPages()
                : $pager->getCurrentPage() + $range_number;

        $range = range($start, $end);

        if (isset($options['nbResults'])) {
            $options += array('nbResults' => $options['nbResults'], 'pager' => $pager, 'path' => $path, 'range' => $range);
        } else {
            $options += array('pager' => $pager, 'path'  => $path, 'range' => $range);
        }

        return $this->templating->render($template, $options);
    }

    /**
     * return current url
     */
    public function getCurrentUrl($exclusion = array(), $merge = array())
    {
        // Get current quert string
        $query_string = $this->request->getQueryString();

        // Remove current per_page from query string
        parse_str($query_string, $output);

        foreach ($exclusion as $key) {
            unset($output[$key]);
        }

        $query_string = http_build_query(array_merge($output, $merge));

        // Bind query string with path
        $path = $this->request->getBaseUrl() . $this->request->getPathInfo();

        if (!empty($query_string)) {
            $path .= "?" . $query_string;
        }

        return $path;
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
