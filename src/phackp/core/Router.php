<?php
namespace yuxblank\phackp\core;
    /*
     * Copyright (C) 2015 yuri.blanc
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/>.
     */

/**
 * This class provides routing methods for index.php. Some methods can be used also externally for inverse routing and url
 * retrive echoing the output.
 * @author yuri.blanc
 * @copyright (c) 2015, Yuri Blanc
 * @since 0.1
 */

class Router
{

    const WILDCARD_REGEXP = '({[aA-zZ0-9]+})';

    /**
     * Get a link url without checking if the route is really defined.
     * To set params, specify the ordinal position in the link as {param}, the array must preserve params ordinal position.
     * This is the faster way to get a real link.
     * @param string $link
     * @param array $params
     * @return string
     */
    public static function link($link, $params =null) {
        if ($params!==null){
            $url = self::fastParamBind($link,$params);
            return Application::getAppUrl().'/'.implode('/', $url);
        } else {
            return $link !== '/' ? Application::getAppUrl().'/'.$link : Application::getAppUrl().$link;
        }
    }


    /**
     * Search the url of a given alias. When passing the method it's faster.
     * @param string $value
     * @param String $type
     * @param string|null $method
     * @return mixed
     */
    private static function searchThroughRoutes($value,$type,$method = null)
    {

        if ($method !== null) {
            foreach (Application::getRoutes()[$method] as $key => $route) {
                if (array_key_exists($type, $route) && $route[$type] === $value) {
                    return $route['url'];
                }
            }
        } else {
            foreach (Application::getRoutes() as $key => $rest) {
                foreach ($rest as $innerKey => $innerRoute) {
                    if (array_key_exists($type, $innerRoute) && $innerRoute[$type] === $value) {
                        return $innerRoute['url'];
                    }
                }


            }
        }
    }


    /**
     * Get the link by a given action. This way to get links is slower but allow the developer to change urls without changing code,
     * referencing to urls with the action instead of a link.
     * Passing the HTTP method will make it faster.
     * For dynamic urls, just pass the array of parameters in ordinal position.
     * If not found (or not defined in routes) return 404 page url.
     * @param string $action
     * @param String|null $method
     * @param array|null $params
     * @return string
     */
    public static function action($action, $method=null, $params =null){
        $link = self::searchThroughRoutes($action, 'action', $method);
        if ($link === null) {
            $link = Application::getErrorRoute(404)['url'];
        }

        if ($params!==null){
            $url = self::fastParamBind($link, $params);
            return Application::getAppUrl().'/'.implode('/', $url);
        } else {
            return $link !== '/' ? Application::getAppUrl().'/'.$link : Application::getAppUrl().$link;
        }
    }

    /**
     * Get the link by a given alias. This way to get links is slower but allow the developer to change urls without changing code,
     * referencing to urls with an alias instead of a link.
     * Passing the HTTP method will make it faster.
     * For dynamic urls, just pass the array of parameters in ordinal position.
     * If not found (or not defined in routes) return 404 page url.
     * @param string $alias
     * @param String|null $method
     * @param array|null $params
     * @return string
     */

    public static function alias ($alias, $method=null, array $params =null) {

        $link = self::searchThroughRoutes($alias, 'alias', $method);
        if ($link === null) {
            $link = Application::getErrorRoute(404)['url'];
        }

        if ($params!==null){
            $url = $url = self::fastParamBind($link, $params);
            return Application::getAppUrl().'/'.implode('/', $url);
        } else {
            return $link !== '/' ? Application::getAppUrl().'/'.$link : Application::getAppUrl().$link;
        }
    }


    /**
     * Process the route URI and return the url with given parameters.
     * Use positions of Wildcards and the ordinal replace them with params
     * @param $routeUrl
     * @param $params
     * @return array
     */

    private static function fastParamBind($routeUrl, $params) {
        $url = explode('/', $routeUrl);
        $wildcards = preg_grep(self::WILDCARD_REGEXP,$url);
        $i=0;
        foreach ($wildcards as $key => $wildcard) {
            $url[$key] = $params[$i];
            $i++;
        }
        return $url;
    }

    /**
     * Redirect (302) to another action from an action.
     * @param string $url
     * @param array|null $params
     */
    public static function switchAction($url, $params = null)
    {
        $r = Router::link($url, $params);
        header("location:$r", true, 302);
    }


    /**
     * External url redirect
     * @param $url
     */

    public static function redirect($url)
    {
        header("location:$url", true, 302);
    }


    /**
     * Find the action from httpKernel and set routed parameters if any.
     * if the route has been found return the route.
     * @param HttpKernel $httpKernel
     * @return null|array
     */
    public static function findAction(HttpKernel $httpKernel)
    {

        foreach (Application::getRoutes()[$httpKernel->getMethod()] as $key => $route) {
            // case without params

            // if options, check if the content-type is the same
            if (array_key_exists('options', $route) && !self::isSameContentType($route,$httpKernel)){
                continue;
            }

            // if the url is the same static route, just return!
            if ($route['url'] === $httpKernel->getUrl()) {
                return $route;

            } else {
                // find wildcard
                if (preg_match(self::WILDCARD_REGEXP, $route['url'])) {
                    $routeArray = preg_split('@/@',$route['url'], NULL, PREG_SPLIT_NO_EMPTY);
                    $queryArray = preg_split('@/@', $httpKernel->getUrl(), NULL, PREG_SPLIT_NO_EMPTY);
                    $url = self::compareRoutes($routeArray, $queryArray);
                    // if compare routes matched and the url has been recreated, return this route
                    if ($url !== null) {
                        $route['params'] = array();
                        $route['params'] = self::getWildCardParams($routeArray, $queryArray);
                        return $route;
                    } else {
                        // search again
                        continue;
                    }
                }
            }
        }
        return null;
    }


    /**
     * Check if the content-type of the request is the same of the 'options'=>'accept' value of the given route.
     * 'option' is an optional array to be used when you want to restrict a particular content-type submission.
     * set HTTP_HEADER to 415 when false.
     * @param array $route
     * @param HttpKernel $httpKernel
     * @return bool
     */
    private static function isSameContentType($route, HttpKernel $httpKernel){
        return $route['options']['accept'] === $httpKernel->getContentType();

    }


    /**
     * Read the url and the route and watch if it matches, Replacing the wildcards {val} until the url match then return the url
     * Return null if the given url does not match the route.
     * @param $routeParams
     * @param $realParams
     * @return null|string
     */
    private static function compareRoutes($routeParams,$realParams)
    {

        // try checking if wildcards static params are less than the difference with real
        $staticParams = preg_grep(self::WILDCARD_REGEXP,$routeParams,PREG_GREP_INVERT);
        if (count(array_diff($staticParams,$realParams))>0) {
            return null;
        }
        // if the count of real and the count of route does not match, the route does not match
        $count = count($realParams);
        if ($count !== count($routeParams)) {
            return null;
        }

        // now loops and replace wicards will params, check, rerun until a difference has been spot
        for ($i = 0; $i < $count; $i++) {
            // faster, if a static param match continue!
            if ($realParams[$i] === $routeParams[$i]) {
                continue;
            } else {
                if (preg_match(self::WILDCARD_REGEXP, $routeParams[$i])) {
                    // replace {value} wildcard with the same url parameter e.g {value} -> value
                    $replaceParam = preg_replace(self::WILDCARD_REGEXP, $realParams[$i], $routeParams[$i]);
                    $routeParams[$i] = $replaceParam;
                    // if match, continue
                    if ($realParams[$i] === $routeParams[$i]) {
                        continue;
                        // not the same route
                    } else {
                        return null;
                    }
                    // the route is not the same or doesn't have the wildcard {}
                } else {
                    return null;
                }
            }

        }
        // if loop has ended, all params matched. return the url string
        return implode('/', $routeParams);
    }

    /**
     * Read the route wildCards {name} and return an associative array paired on {name} => value.
     * The value is taken from the current request parameter.
     * @param $routeParams
     * @param $queryArray
     * @return array
     */
    private static function getWildCardParams($routeParams, $queryArray)
    {
        $params = preg_grep(self::WILDCARD_REGEXP, $routeParams);
        $getParams = array();
        foreach ($params as $key => $param) {
            $index = str_replace(array('{', '}'), '', $routeParams[$key]);
            $getParams[$index] = $queryArray[$key];
        }
        return $getParams;
    }


    /**
     * Performs a inverse route returning returning an array with [0 => 'Controller', 1 => 'action']
     * Recreate the current application CONTROLLER namespace using the application configuration.
     * @param string $action
     * @return mixed[]
     */
    public static function getController($action)
    {
        $namespace = Application::getNameSpace()['CONTROLLER'];
        $array = explode('@', $action);
        $array[0] = $namespace.$array[0];
        return $array;
    }


    /**
     * Performs a 404 not found
     * @static
     * @param string $action
     * @param string $method
     */

    public static function notFound()
    {
        header('location:'.Application::getAppUrl() . '/' . Application::getErrorRoute(404)['url'], true);
        exit(0);
    }


    public static function methodNotAllowed()
    {

    }


}
