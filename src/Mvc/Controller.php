<?php
namespace PhalconX\Mvc;

use PhalconX\Errors;

/**
 * 组件依赖如下：
 *  - 用户登录依赖 auth 组件
 *  - 用户权限判断依赖 roleManager 组件
 *  - csrf 验证依赖 security 组件
 */
class Controller extends \Phalcon\Mvc\Controller
{
    protected $isAjax;

    protected $defaultActions = array(
        'login' => 'user/login',
        'access_denied' => 'user/access_denied',
        'error' => 'index/error'
    );

    protected $pathParamOffset = 2;

    private $pathParameters;

    protected function initialize()
    {
    }
    
    /**
     * 渲染页面
     *
     * render($vars)
     * render($view, $vars)
     * render($view, $vars, $return)
     * 
     * @param $view 页面名 可以是 'controller/action' 的形式或 'action'形式
     * @param $vars 页面参数
     * @param $return 是否返回渲染结果
     * @return null|string 如果 $return 为 true，返回渲染结果
     */
    public function render($view=null, $vars=null, $return=false)
    {
        if ( is_array($view) ) {
            $vars = $view;
            $view = null;
        }
        if ( isset($view) ) {
            $parts = explode('/', $view, 2);
            if ( count($parts) == 2 ) {
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
        if ( isset($vars) ) {
            $view->setVars($vars);
        }
        $view->start()->render($controllerName, $actionName, $vars);
        $view->finish();
        if ( $return ) {
            return $view->getContent();
        } else {
            echo $view->getContent();
        }
    }

    /**
     * 跳转到其它 action
     * @param $action 
     */
    public function forward($action)
    {
        if ( is_string($action) ) {
            $parts = explode('/', $action);
            if ( count($parts) == 1 ) {
                array_unshift($parts, $this->dispatcher->getControllerName());
            }
            $action = array('controller' => $parts[0], 'action' => $parts[1]);
        }
        return $this->dispatcher->forward($action);
    }

    /**
     * 设置错误处理函数; 处理 filters
     */
    public function beforeExecuteRoute($dispatcher)
    {
        $this->initialize();
        $this->setErrorHandlers();
        $filters = $this->filters();
        if ( isset($filters) ) {
            $filters = $this->parseRules($dispatcher, $filters);
            foreach ( $filters as $filter ) {
                $filterMethod = 'filter' . $filter;
                if ( !$this->$filterMethod($dispatcher) ) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 设置错误处理函数
     */
    protected function setErrorHandlers()
    {
        set_error_handler(array($this, 'handleFatalError'), error_reporting());
        set_exception_handler(array($this, 'handleException'));
    }

    public function handleFatalError($code, $message, $file, $line)
    {
        Errors::handleError($code, $message, $file, $line);
        $this->handleError(array(
            'code' => $code,
            'message' => $message,
            'file' => $file,
            'line' => $line
        ));
    }
    
    public function handleException($exception)
    {
        Errors::handleException($exception);
        $this->handleError(array(
            'exception' => $exception,
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ));
    }

    protected function handleError($error)
    {
        $this->response->setStatusCode(500, "Internal Server Error");       
        if ( $this->isAjax ) {
            echo Errors::toJson(Errors::ERROR_SERVICE_UNAVAILABLE);
            exit;
        } else {
            $this->forward($this->defaultActions['error']);
        }
    }

    /**
     * 过滤器配置
     *
     * 子类重写此方法。过滤器配置项为过滤器规则构成的数组，每个规则可以是以下情况：
     * <ul>
     *  <li> rule_name 表示所有 action 都要使用此规则
     *  <li> rule_name - action1 action2 .. 表示除 action1, action2 之外所有 action 都要使用此规则
     *  <li> rule_name + action1 action2 .. 表示只有 action1, action2 要使用此规则
     * </ul>
     *
     * @return array 过滤器配置
     */
    protected function filters()
    {
    }

    /**
     * 用户权限配置
     */
    protected function accessRules()
    {
    }

    /**
     * 过滤未登录用户
     *
     * 如果用户未登录，判断 isAjax:
     *  1. isAjax 为 false，跳转到 defaultActions 中配置的 login action
     *  2. isAjax 为 true，输出 json 错误信息
     */
    public function filterLoginOnly($dispatcher)
    {
        $isNeedLogin = $this->isAjax ? $this->auth->isGuest() : $this->auth->isNeedLogin();
        if ( $isNeedLogin ) {
            $this->handleFilterError(Errors::ERROR_LOGIN_REQUIRED);
            return false;
        }
        return true;
    }

    /**
     * 过滤非 POST 请求
     *
     * 请求不是 POST 请求，输出 json 错误信息
     */
    public function filterPostOnly($dispatcher)
    {
        if ( !$this->request->isPost() ) {
            $this->handleFilterError(Errors::ERROR_HTTP_METHOD_INVALID);
            return false;
        }
        return true;
    }

    /**
     * 过滤非 PUT 请求
     *
     * 请求不是 PUT 请求，输出 json 错误信息
     */
    public function filterPutOnly($dispatcher)
    {
        if ( $this->request->getMethod() != 'PUT' ) {
            $this->handleFilterError(Errors::ERROR_HTTP_METHOD_INVALID);
            return false;
        }
        return true;
    }

    /**
     * 过滤非 DELETE 请求
     *
     * 请求不是 DELETE 请求，输出 json 错误信息
     */
    public function filterDeleteOnly($dispatcher)
    {
        if ( $this->request->getMethod() != 'DELETE' ) {
            $this->handleFilterError(Errors::ERROR_HTTP_METHOD_INVALID);
            return false;
        }
        return true;
    }

    /**
     * 过滤用户权限
     *
     * 如果用户权限不匹配，判断 isAjax
     *  1. isAjax 为 false，跳转到 defaultActions 中配置的 access_denied action
     *  2. isAjax 为 true，输出 json 错误信息
     */
    public function filterAccessControl($dispatcher)
    {
        if ( !$this->filterLoginOnly($dispatcher) ) {
            return false;
        }
        $rules = $this->accessRules();
        if ( !empty($rules) ) {
            $roles = $this->parseRules($dispatcher, $rules);
            foreach ( $roles as $role ) {
                if ( !$this->roleManager->checkAccess($this->auth->user_id, $role) ) {
                    $this->handleFilterError(Errors::ERROR_ACCESS_DENIED);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 标志请求为 json ajax 请求
     */
    public function filterJsonContentType()
    {
        $this->isAjax = true;
        header("Content-Type: application/json");
        return true;
    }

    /**
     * crsf 验证
     */
    public function filterCsrfToken()
    {
        if ( !$this->request->isPost() ) {
            $this->handleFilterError(Errors::ERROR_HTTP_METHOD_INVALID);
            return false;
        }
        if ( !$this->security->checkToken() ) {
            $this->handleFilterError(Errors::ERROR_CSRF_TOKEN_INVALID);
            return false;
        }
        return true;
    }

    protected function handleFilterError($errorCode)
    {
        if ( $this->isAjax ) {
            if ( $errorCode == Errors::ERROR_LOGIN_REQUIRED ) {
                $this->response->setStatusCode(401, "Unauthorized");
            } elseif ( $errorCode == Errors::ERROR_ACCESS_DENIED ) {
                $this->response->setStatusCode(403, "Access Denied");
            } else {
                $this->response->setStatusCode(400, 'Bad Request');
            }
            echo Errors::toJson($errorCode);
        } else {
            if ( $errorCode == Errors::ERROR_LOGIN_REQUIRED ) {
                $this->forward($this->defaultActions['login']);
            } elseif ( $errorCode == Errors::ERROR_ACCESS_DENIED ) {
                $this->forward($this->defaultActions['access_denied']);
            } else {
                $this->logger->error(Errors::toJson($errorCode));
                $this->forward($this->defaultActions['error']);
            }
        }
    }

    /**
     * @param Phalcon\Mvc\Dispatcher $dispatcher
     * @param array $rules 
     * @return array 当前 action 需要使用的规则名
     */
    protected function parseRules($dispatcher, $rules)
    {
        $rule_names = array();
        $action = strtolower($dispatcher->getActionName());
        foreach ( $rules as $rule ) {
            $parts = preg_split('/\s+([-+])\s+/', trim($rule), 2, \PREG_SPLIT_DELIM_CAPTURE);
            if ( count($parts) == 1 ) {
                $rule_names[] = $parts[0];
            } else {
                list($rule_name, $type, $actions) = $parts;
                $actions = preg_split('/\s*[, ]\s*/', $actions);
                if ( $type == '-' ) {
                    $match = !in_array($action, $actions);
                } else {
                    $match = in_array($action, $actions);
                }
                if ( $match ) {
                    $rule_names[] = $rule_name;
                }
            }
        }
        return array_unique($rule_names);
    }

    /**
     * 读取参数信息
     */
    public function getParam($name, $filters=null, $defaultValue=null)
    {
        $value = $this->dispatcher->getParam($name);
        if ( !isset($value) ) {
            if ( !isset($this->pathParameters) ) {
                // 解析路径参数 
                $this->pathParameters = array();
                $uri = $this->router->getRewriteUri();
                $parts = explode('/', trim($uri, '/'));
                for ( $i=$this->pathParamOffset, $len=count($parts); $i<$len; $i+=2 ) {
                    $this->pathParameters[$parts[$i]] = isset($parts[$i+1]) ? $parts[$i+1] : null;
                }
            }
            $value = isset($this->pathParameters[$name]) ? $this->pathParameters[$name] : null;
        }
        if ( isset($value) ) {
            return isset($filters) ? $this->filter->sanitize($value, $filters) : $value;
        } else {
            return $defaultValue;
        }
    }
}
