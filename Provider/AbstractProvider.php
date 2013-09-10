<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OS\ToolsBundle\Provider;

use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

abstract class AbstractProvider
{
    protected $security;

    public function __construct($security)
    {
        $this->security = $security;
    }

    public function getUser($exception = true)
    {
        if (null === $token = $this->security->getToken()) {
            if ($exception) {
                throw new AuthenticationCredentialsNotFoundException();
            } else {
                return null;
            }
        }

        if (!is_object($user = $token->getUser())) {
            if ($exception) {
                throw new AuthenticationCredentialsNotFoundException();
            } else {
                return null;
            }
        }

        return $user;
    }
}
