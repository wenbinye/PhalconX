<?php
namespace PhalconX\Di\Definition\Resolver;

class MultipleDefinitionResolver implements DefinitionResolverInterface
{
    private $resolvers;
    
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @inheritDoc
     */
    public function isResolvable($name)
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isResolvable($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function resolve($name)
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isResolvable($name)) {
                return $resolver->resolve($name);
            }
        }
    }
}
