router
==============================

.. code-block:: php

   use PhalconX\Mvc\Annotations\RoutePrefix;
   use PhalconX\Mvc\Annotations\Get;

   /**
    * @RoutePrefix("/app")
    */
    class AppController extends Controller
    {
        /**
         * @Get
         */
        public function indexAction()
        {
        }
    }
   
