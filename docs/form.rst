表单验证
==============================

.. code-block:: php

   use PhalconX\Validation\Validation;
   use PhalconX\Validation\ValidatorFactory;

   $validation = new Validation();

   my $foo = new Foo($input);
   $validation->validate($foo); // throw ValidationException if data is invalid
   $validation->validate($input, [
       'field1' => [
           'required' => '',
       ],
   ]);

   $myform = $validation->createForm(MyForm::class); // create Phalcon\Forms\Form object
