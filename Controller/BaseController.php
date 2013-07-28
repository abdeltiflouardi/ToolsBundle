<?php

namespace OS\ToolsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use OS\ToolsBundle\Exception\UnexpectedTypeException;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Doctrine\ORM\Query;
use OS\ToolsBundle\Util\String;

/**
 * Controller is a simple implementation of a Controller.
 *
 * It provides methods to common features needed in controllers.
 *
 * @author Fabien Potencier <fabien@symfony.com>, ouardisoft
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
     * Generates a URL from the given parameters.
     *
     * @param string         $route         The name of the route
     * @param mixed          $parameters    An array of parameters
     * @param Boolean|string $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string $controller The controller name (a string like BlogBundle:Post:index)
     * @param array  $path       An array of path parameters
     * @param array  $query      An array of query parameters
     *
     * @return Response A Response instance
     */
    public function forward($controller, array $path = array(), array $query = array())
    {
        $path['_controller'] = $controller;
        $subRequest = $this->container->get('request')->duplicate($query, null, $path);

        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param string  $url    The URL to redirect to
     * @param integer $status The status code to use for the Response
     *
     * @return RedirectResponse
     */
    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a rendered view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     *
     * @return string The rendered view
     */
    public function renderView($view, array $parameters = array())
    {
        return $this->container->get('templating')->render($view, $parameters);
    }

    /**
     * @return Response The renderer view
     */
    public function renderText($text)
    {
        return new Response($text);
    }

    /**
     * @return Response The renderer view
     */
    public function renderJson($text)
    {
        return new JsonResponse($text);
    }

    /**
     * Renders a view.
     *
     * @param string   $view       The view name
     * @param array    $parameters An array of parameters to pass to the view
     * @param Response $response   A response instance
     *
     * @return Response A Response instance
     */
    public function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->container->get('templating')->renderResponse($view, $parameters, $response);
    }

    /**
     * @return Response A Response instance
     */
    public function renderResponse($view = null, Response $response = null)
    {
        if (null === $view) {
            $view = String::getTemplateNameFromClass($this->getRequest()->attributes->get('_controller'));
        }

        return $this->container->get('templating')->renderResponse($view, $this->getViewData(), $response);
    }

    /**
     * @return Response A Response instance
     */
    public function renderResponseCache($view = null, $lastModified = null)
    {
        $r = $this->getRequest();

        if ($etag = $this->getViewData('_etag')) {
            $prepareEtag = $etag;
        } else {
            $prepareEtag = sprintf(
                '%s %s %s %s %s %s',
                $r->getMethod(),
                $r->getRequestUri(),
                $r->server->get('SERVER_PROTOCOL'),
                $this->getParameter('version'),
                $this->getParameter('kernel.environment'),
                $lastModified instanceof \DateTime ? $lastModified->format('YmdHis') : ''
            );
        }

        $response = new Response();
        $response->setETag(md5(serialize($prepareEtag)));
        $response->setPublic(); // make sure the response is public/cacheable
        $response->setMaxAge(1800);
        $response->setSharedMaxAge(1800);

        //$lastModified = new \DateTime('2013-07-20 16:54:30');
        if ($lastModified instanceof \DateTime) {
            $response->setLastModified($lastModified);
        }

        if ($response->isNotModified($r)) {
            return $response;
        }

        return $this->renderResponse($view, $response);
    }

    /**
     * Streams a view.
     *
     * @param string           $view       The view name
     * @param array            $parameters An array of parameters to pass to the view
     * @param StreamedResponse $response   A response instance
     *
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
     * Returns a NotFoundHttpException.
     *
     * This will result in a 404 response code. Usage example:
     *
     *     throw $this->createNotFoundException('Page not found!');
     *
     * @param string    $message  A message
     * @param \Exception $previous The previous exception
     *
     * @return NotFoundHttpException
     */
    public function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($message, $previous);
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string|FormTypeInterface $type    The built type of the form
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     *
     * @return Form
     */
    public function createForm($type, $data = null, array $options = array())
    {
        return $this->container->get('form.factory')->create($type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    public function createFormBuilder($data = null, array $options = array())
    {
        return $this->container->get('form.factory')->createBuilder('form', $data, $options);
    }

    /**
     * Shortcut to return the request service.
     *
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
        if (!is_string($entityName)) {
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
        if (!is_string($name)) {
            throw new UnexpectedTypeException($name, 'string');
        }

        return $this->container->getParameter($name);
    }

    /**
     * @return Pagerfanta $pagerfanta
     */
    public function pager($entity, $options = null)
    {
        // Get Query from controller
        if ($entity instanceof Query) {
            $query = $entity;
        } else {
            $query = $em->createQuery($options['query']);
        }

        // ODER BY
        $dql       = $query->getDQL();
        $sort      = $this->getRequest()->query->get('sort');
        $direction = $this->getRequest()->query->get('direction');

        if ($sort) {
            if (strpos($dql, 'ORDER BY') > 0) {
                $dql .= sprintf(" ,%s %s", $sort, $direction);
            } else {
                $dql .= sprintf(" ORDER BY %s %s", $sort, $direction);
            }
        } elseif (isset($options['order_by'])) {
            if (strpos($dql, 'ORDER BY') > 0) {
                $dql .= " , " . $options['order_by'];
            } else {
                $dql .= " ORDER BY  " . $options['order_by'];
            }
        }
        $query->setDQL($dql);

        // Bind query to Pagerfanta
        $adapter    = new DoctrineORMAdapter($query);
        $pagerfanta = new Pagerfanta($adapter);

        // current page
        $pagerfanta->setCurrentPage($this->getRequest()->query->get('page', 1), true);

        // Get items per page
        if ($perPage = $this->getRequest()->query->get('per-page')) {
            $itemsPerPage = $perPage;
        } elseif (isset($options['itemsPerPage'])) {
            $itemsPerPage = $options['itemsPerPage'];
        } elseif ($this->container->hasParameter('items_per_page')) {
            $itemsPerPage = $this->container->getParameter('items_per_page');
        } else {
            $itemsPerPage = 20;
        }

        $pagerfanta->setMaxPerPage($itemsPerPage);

        // return pagerfanta object
        return $pagerfanta;
    }

    /**
     *
     * @param string $route
     * @return RedirectResponse $object
     */
    public function redirectTo($route, array $params = array(), $absolute = false)
    {
        $code = isset($params['code']) ? $params['code'] : 302;
        unset($params['code']);

        if (is_array($route) && isset($route['url'])) {
            $url = $route['url'];
        } else {
            $url = $this->generateUrl($route, $params, $absolute);
        }

        return new RedirectResponse($url, $code);
    }

    /**
     *
     * @return string url
     */
    public function referer()
    {
        return $this->getRequest()->headers->get('Referer');
    }

    /**
     * @return RedirectResponse
     */
    public function redirectToReferer($router = '_post', $status = 302)
    {
        $referer = $this->referer();

        $url = is_null($referer)
                ? !is_null($router) ? $this->generateUrl($router) : $this->generateUrl('_post')
                : $referer;

        return $this->redirect($url, $status);
    }

    /**
     * 
     * @return Boolean
     */
    public function isGranted($attributes, $object = null)
    {
        return $this->get('security.context')->isGranted($attributes, $object);
    }

    /**
     *
     * @param string $key
     * @return string 
     */
    public function trans($key, $locale = null, $domain = 'messages')
    {
        return $this->get('translator')->trans($key, array(), $domain, $locale);
    }

    /**
     *
     * @param string $message
     * @param string $type 
     */
    public function flash($message, $type = 'success')
    {
        $this->getSession()->getFlashBag()->add($type, $this->trans($message));
    }

    /**
     *
     * @param string $name
     * @param mixed $val 
     */
    public function setSession($name, $val)
    {
        $session = $this->getSession();

        // store an attribute for reuse during a later user request
        $session->set($name, $val);
    }

    /**
     *
     * @param string $name
     * @return mixed 
     */
    public function getSession($name = null)
    {
        $session = $this->getRequest()->getSession();

        // read session
        return !empty($name) ? $session->get($name) : $session;
    }
}
