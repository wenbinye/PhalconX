<?php
namespace PhalconX\Mvc;

use Phalcon\Db\RawValue;
use Phalcon\Cache;

/**
 * Role manager
 */
class RoleManager implements RoleManagerInterface
{
    /**
     * @const default root user id
     */
    const ROOT_ID = 1;

    /**
     * @const role delimiter
     */
    const DELIMITER = ' ';

    /**
     * @var Cache\BackendInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $model;

    /**
     * @var array memory cache
     */
    private $localCache = [];

    /**
     * Constructor.
     *
     * @param Cache\BackendInterface $cache
     * @param string $mode user model class
     */
    public function __construct(Cache\BackendInterface $cache, $model)
    {
        $this->cache = $cache;
        $this->model = $model;
    }
    
    public function isRoot($user_id)
    {
        return $user_id == self::ROOT_ID;
    }

    public function getRoles($user_id)
    {
        $roles = $this->getRolesMap($user_id);
        return $roles === null ? null : array_keys($roles);
    }

    private function getRolesMap($user_id)
    {
        if ($this->isRoot($user_id)) {
            return null;
        }
        if (isset($this->localCache[$user_id])) {
            return $this->localCache[$user_id];
        }
        $cacheKey = $this->cachePrefix . $user_id;
        $roles = $this->cache->get($cacheKey);
        if ($roles === null) {
            $user = $this->getModel($user_id);
            if ($user == null) {
                $roles = array();
            } else {
                $roles = array_flip($user->getRoles());
            }
            
            $this->cache->save($cacheKey, $roles);
            $this->localCache[$user_id] = $roles;
        }
        return $roles;
    }
    
    private function getModel($user_id)
    {
        return call_user_func([$this->model, 'findFirst'], $user_id);
    }
    
    private function saveRoles($user_id, $roles)
    {
        $user = $this->getModel($user_id);
        if ($user === null) {
            throw new \UnexpectedValueException("user '$user_id' does not exists");
        }
        $this->localCache[$user_id] = $roles;
        $this->cache->save($this->cachePrefix . $user_id, $roles);
        $str = implode(self::DELIMITER, array_keys($roles));
        if (empty($str)) {
            $str = new RawValue("''");
        }
        $user->roles = $str;
        if ($user->save()) {
            return $this;
        } else {
            $messages = array();
            foreach ($user->getMessages() as $message) {
                $messages[] = $message;
            }
            throw new \RuntimeException(implode(', ', $messages));
        }
    }
    
    private function hasRoles($user_id, $roles)
    {
        if ($this->isRoot($user_id)) {
            return true;
        }
        
        $user_roles = $this->getRolesMap($user_id);
        foreach ($roles as $role) {
            if (!isset($user_roles[$role])) {
                return false;
            }
        }
        return true;
    }

    private function hasAnyRole($user_id, $roles)
    {
        if ($this->isRoot($user_id)) {
            return true;
        }
        
        $user_roles = $this->getRolesMap($user_id);
        foreach ($roles as $role) {
            if (isset($user_roles[$role])) {
                return true;
            }
        }
        return false;
    }

    public function clearCache($user_id)
    {
        $this->cache->delete($this->cachePrefix . $user_id);
        unset($this->localCache[$user_id]);
    }
    
    public function setRoles($user_id, $roles)
    {
        return $this->saveRoles($user_id, array_flip($roles));
    }
    
    public function addRoles($user_id, $roles)
    {
        if ($this->isRoot($user_id)) {
            return $this;
        }
        
        $user_roles = $this->getRolesMap($user_id);
        foreach ($roles as $role) {
            $user_roles[$role] = 1;
        }
        return $this->saveRoles($user_id, $user_roles);
    }

    public function removeRoles($user_id, $roles)
    {
        if ($this->isRoot($user_id)) {
            return $this;
        }

        $user_roles = $this->getRolesMap($user_id);
        foreach ($roles as $role) {
            unset($user_roles[$role]);
        }
        return $this->saveRoles($user_id, $user_roles);
    }

    /**
     * 检查用户是否包含指定的所有权限
     * @param string $user_id
     * @param string|array $roles | 表示或，&表示与
     * @return bool
     */
    public function checkAccess($user_id, $roles)
    {
        if ($this->isRoot($user_id)) {
            return true;
        }
        if (is_array($roles)) {
            $this->hasRoles($user_id, $roles);
        } elseif (strpos($roles, '|') !== false) {
            return $this->hasAnyRole($user_id, explode('|', $roles));
        } else {
            return $this->hasRoles($user_id, explode('&', $roles));
        }
    }
}
