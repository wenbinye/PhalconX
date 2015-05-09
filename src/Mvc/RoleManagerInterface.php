<?php
namespace PhalconX\Mvc;

interface RoleManagerInterface
{
    /**
     * 是否是超级用户
     * 
     * @param string $user_id
     * @param boolean
     */
    function isRoot($user_id);
    
    /**
     * 检查用户是否包含指定的所有权限
     * 
     * @param string $user_id
     * @param string $roles | 表示或，&表示与
     * @return boolean
     */
    function checkAccess($user_id, $roles);

    /**
     * 获取用户所有权限
     *
     * @param string $user_id
     * @return array 如果是root用户，返回 null，其它用户返回权限数组
     */
    function getRoles($user_id);

    /**
     * 给用户添加权限
     * @param string $user_id
     * @param array 权限名
     */
    function addRoles($user_id, $roles);

    /**
     * 删除用户权限
     * @param string $user_id
     * @param array 权限名
     */
    function removeRoles($user_id, $roles);
}
