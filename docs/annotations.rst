Annotations 实现
==============================

.. code-block:: php

$annotations = new Annotations;
$collection = $annotations->get($class);
$collection->onClass();
$collection->onMethod($method);
$collection->onProperty($property);
$collection->onProperties();
$collection->onMethods();
$collection->onClassOrMethods();
$collection->onClassOrProperties();
$collection->is(MyAnnotation::class);

