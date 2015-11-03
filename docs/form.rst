表单验证
==============================

.. code-block:: php

   use PhalconX\Validation\Form;
   use PhalconX\Validation\ValidatorFactory;

   $form = new Form();

   my $formObject = new MyForm($input);
   $form->validate($formObject);    // throw ValidationException if data is invalid
   $form->validate($formData, [
       'field1' => ValidatorFactory::create([
           'required' => '',
       ]),
   ]);

   $myform = $form->createForm(MyForm::class); // create PhalconX\Forms\Form object
