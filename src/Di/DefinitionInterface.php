<?php
namespace PhalconX\Di;

use Phalcon\Di\ServiceInterface;

interface DefinitionInterface extends ServiceInterface
{
    /**
     * Gets scope
     *
     * @return string $scope
     */
    public function getScope();

    /**
     * Sets scope
     *
     * @param  string $scope
     * @return static
     */
    public function setScope($scope);
}
