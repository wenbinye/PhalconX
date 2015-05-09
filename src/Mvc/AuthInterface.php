<?php
namespace PhalconX\Mvc;

interface AuthInterface 
{
    /**
     * 初始化操作
     */
    function initialize();
    
    /**
     * 用户登录操作
     *
     * @param $identity 用户数据
     */
    function login($identity);

    /**
     * 用户注销操作
     * @param $destroySession 是否需要清除所有 session 数据
     */
    function logout($destroySession=true);

    /**
     * 判断用户是否登录
     * @return boolean
     */
    function isGuest();

    /**
     * 判断用户是否需要重新登录
     * @return boolean
     */
    function isNeedLogin();
}
