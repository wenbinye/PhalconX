扩展 Phalcon\Di
==============================

.. code-block:: php

    use PhalconX\Di\FactoryDefault;
    $di = new FactoryDefault;
    $di->autoload(["fs"], MyServiceProvider::class);
    $di->autoload(["finder"], MyServiceProvider::class, false);
    $di->load(MyServiceProvider::class, ["finder"]);

.. code-block:: php

    use PhalconX\Di\ServiceProvider;
    use Symfony\Component\Filesystem\Filesystem;
    use Symfony\Component\Finder\Finder;
    
    class MyServiceProvider extends ServiceProvider
    {
         protected $services = [
             'fs' => Filesystem::class,
         ];
    
         public function providerFinder($di)
         {
             return new Finder();
         }
    }
