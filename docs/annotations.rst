Annotations 实现
==============================

.. code-block:: php

$annotations = new Annotations;
$classAnnotations = $annotations->get($class);

$filterd = $annotations->filter($classAnnotations)
->onClass();
->onMethod($method);
->onProperty($property);
->onProperties();
->onMethods();
->onClassOrMethods();
->onClassOrProperties();
->is(MyAnnotation::class);

foreach ($filterd as $annotation) {
}
