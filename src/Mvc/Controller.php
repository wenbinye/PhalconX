<?php
namespace PhalconX\Mvc;

use Phalcon\Text;

/**
 * 组件依赖如下：
 *  - 用户登录依赖 auth 组件
 *  - 用户权限判断依赖 roleManager 组件
 *  - csrf 验证依赖 security 组件
 */
class Controller extends \Phalcon\Mvc\Controller
{
    protected $pathParamOffset = 2;
    protected $viewPrefix = '';

    private $pathParameters;
    
    /**
     * 渲染页面
     * @param $view 页面名 可以是 'controller/action' 的形式或 'action'形式
     * @param $vars 页面参数
     * @param $return 是否返回渲染结果
     * @return null|string 如果 $return 为 true，返回渲染结果
     */
    public function render($view = null, $vars = null, $return = false)
    {
        if (is_array($view)) {
            $vars = $view;
            $view = null;
        }
        if (isset($view)) {
            $parts = explode('/', $view, 2);
            if (count($parts) == 2) {
                list($controllerName, $actionName) = $parts;
            } else {
                $controllerName = $this->dispatcher->getControllerName();
                $actionName = $parts[0];
            }
        } else {
            $controllerName = $this->dispatcher->getControllerName();
            $actionName = $this->dispatcher->getActionName();
        }
        $view = $this->view;
        if (isset($vars)) {
            $view->setVars($vars);
        }
        $view->pick($this->pickView($controllerName, $actionName));
        $view->start()->render(null, null, $vars);
        $view->finish();
        if ($return) {
            return $view->getContent();
        } else {
            $this->response->setContent($view->getContent());
        }
    }

    /**
     * 跳转到其它 action
     * @param $action
     */
    public function forward($action)
    {
        if (is_string($action)) {
            $parts = explode('/', $action);
            if (count($parts) == 1) {
                array_unshift($parts, $this->dispatcher->getControllerName());
            }
            $action = array('controller' => $parts[0], 'action' => $parts[1]);
        }
        return $this->dispatcher->forward($action);
    }
    
    /**
     * 读取参数信息
     */
    public function getParam($name, $filters = null, $defaultValue = null)
    {
        $value = $this->dispatcher->getParam($name);
        if (!isset($value)) {
            if (!isset($this->pathParameters)) {
                // 解析路径参数
                $this->pathParameters = array();
                $uri = $this->router->getRewriteUri();
                $parts = explode('/', trim($uri, '/'));
                for ($i=$this->pathParamOffset, $len=count($parts); $i<$len; $i+=2) {
                    $this->pathParameters[$parts[$i]] = isset($parts[$i+1]) ? $parts[$i+1] : null;
                }
            }
            $value = isset($this->pathParameters[$name]) ? $this->pathParameters[$name] : null;
        }
        if (isset($value)) {
            return isset($filters) ? $this->filter->sanitize($value, $filters) : $value;
        } else {
            return $defaultValue;
        }
    }

    private function pickView($controllerName, $actionName)
    {
        return $this->viewPrefix . '/' . Text::uncamelize($controllerName)
            . '/' . Text::uncamelize($actionName);
    }
}
