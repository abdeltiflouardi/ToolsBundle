<?php

namespace OS\ToolsBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Request;
use OS\ToolsBundle\Exception\UnexpectedTypeException;

/**
 * Controller is a simple implementation of a Controller.
 *
 * It provides methods to common features needed in controllers.
 *
 * @author ouardisoft
 */
class BaseController extends ContainerAware
{

    protected $viewData = array();

    /**
     * 
     * @param string|array $name
     * @param mixed $value
     * 
     * @return \OS\ToolsBundle\Controller\BaseController
     * 
     * @throws UnexpectedTypeException
     */
    public function set($name, $value = "") 
    {
        if (!is_string($name) && !is_array($name)) {
            throw new UnexpectedTypeException($name, 'string or array');
        }

        if (is_array($name)) {
            $this->viewData += $name;
        } else {
            $this->viewData[$name] = $value;
        }

        return $this;
    }

    /**
     * 
     * @param string $route
     * @param array $parameters
     * @param boolean $absolute
     * @return string
     */
    public function generateUrl($route, $parameters = array(), $absolute = false)
    {
        return $this->container->get('router')->generate($route, $parameters, $absolute);
    }

    /**
     * @param string like BlogBundle:Post:index
     * @return Response
     */
    public function forward($controller, array $path = array(), array $query = array())
    {
        return $this->container->get('http_kernel')->forward($controller, $path, $query);
    }

    /**
     * @return RedirectResponse
     */
    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * @return string The renderer view
     */
    public function renderView($view, array $parameters = array())
    {
        return $this->container->get('templating')->render($view, $parameters);
    }

    /**
     * @return Response A Response instance
     */
    public function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->container->get('templating')->renderResponse($view, $parameters, $response);
    }

    /**
     * @return Response A Response instance
     */
    public function view($view)
    {
        return $this->container->get('templating')->renderResponse($view, $this->getViewData());
    }

    /**
     * @return StreamedResponse A StreamedResponse instance
     */
    public function stream($view, array $parameters = array(), StreamedResponse $response = null)
    {
        $templating = $this->container->get('templating');

        $callback = function () use ($templating, $view, $parameters) {
            $templating->stream($view, $parameters);
        };

        if (null === $response) {
            return new StreamedResponse($callback);
        }

        $response->setCallback($callback);

        return $response;
    }

    /**
     * @return NotFoundHttpException
     */
    public function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }

    /**
     * @return Form
     */
    public function createForm($type, $data = null, array $options = array())
    {
        return $this->container->get('form.factory')->create($type, $data, $options);
    }

    /**
     * @return FormBuilder
     */
    public function createFormBuilder($data = null, array $options = array())
    {
        return $this->container->get('form.factory')->createBuilder('form', $data, $options);
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return Registry
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    public function getDoctrine()
    {
        if (!$this->container->has('doctrine')) {
            throw new \LogicException('The DoctrineBundle is not registered in your application.');
        }

        return $this->container->get('doctrine');
    }

    /**
     * Get a user from the Security Context
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getUser()
     */
    public function getUser()
    {
        if (!$this->container->has('security.context')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.context')->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * Returns true if the service id is defined.
     *
     * @param string $id The service id
     *
     * @return Boolean true if the service id is defined, false otherwise
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * Gets a service by id.
     *
     * @param string $id The service id
     *
     * @return object The service
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * 
     */
    public function getViewData($name = null)
    {
        if (null === $name) {
            return $this->viewData;
        }

        if (!is_string($name)) {
            throw new UnexpectedTypeException($name, 'string');
        }

        if (array_key_exists($name, $this->getViewData())) {
            return $this->viewData[$name];
        }

        return null;
    }

    /**
     * 
     * @param string $entity
     */
    public function getRepository($entityName = null, $useDefault = true)
    {
        if (!is_string($entityName))  {
            throw new UnexpectedTypeException($entityName, 'string');
        }

        if ($useDefault) {
            $entityBundle = $this->getParameter('os_tools.entity_bundle');
            $entityPrefix = $this->getParameter('os_tools.entity_prefix');

            $entityName = sprintf('%s:%s%s', $entityBundle, $entityPrefix, $entityName);
        }

        return $this->getDoctrine()->getRepository($entityName);
    }

    /**
     * 
     */
    public function getParameter($name)
    {
        if (!is_string($name))  {
            throw new UnexpectedTypeException($name, 'string');
        }

        return $this->container->getParameter($name);
    }
}