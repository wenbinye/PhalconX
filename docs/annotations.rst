Annotations 实现
==============================

.. code-block:: php

    use PhalconX\Annotation\Annotations;

    $annotations = new Annotations();
    $all = $annotations->get($class);
    $it = $annotations->filter($all);

    $it = $annotations->iterate($class)
        ->onClass()
        ->onMethod($method)
        ->onProperty($property)
        ->onProperties()
        ->onMethods()
        ->onClassOrMethods()
        ->onClassOrProperties()
        ->is(MyAnnotation::class);
    
    foreach ($it as $annotation) {
    }
    // to array
    $all = iterator_to_array($it);
