<?php

namespace OS\ToolsBundle\Util;

/**
 * @author ouardisoft
 */
class String
{
    /**
     * 
     */
    public static function getTemplateNameFromClass($className)
    {
        return preg_filter(
            '/(.*)\\\(.*)\\\Controller\\\(.*)Controller::(.*)Action/',
            '$1$2:$3:$4.html.twig',
            $className
        );
    }

    /**
     * Truncate
     */
    public static function truncate($value, $length = 30, $preserve = false, $separator = '...')
    {
        if (strlen($value) > $length) {
            if ($preserve) {
                if (false !== ($breakpoint = strpos($value, ' ', $length))) {
                    $length = $breakpoint;
                }
            }

            return rtrim(substr($value, 0, $length)) . $separator;
        }

        return $value;
    }
}
