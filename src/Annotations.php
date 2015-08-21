<?php
namespace PhalconX;

use Phalcon\Text;
use Phalcon\Di\Injectable;
use Phalcon\Annotations\Reader;
use Phalcon\Annotations\Annotation as PhalconAnnotation;
use PhalconX\Exception;
use PhalconX\Util;
use PhalconX\Annotations\Context;
use PhalconX\Annotations\ContextType;
use PhalconX\Annotations\Annotation;
use PhalconX\Annotations\Collection;

use PhalconX\Annotations\Validator\Valid;
use PhalconX\Annotations\Validator\IsA;

use PhalconX\Annotations\Cli\Task;
use PhalconX\Annotations\Cli\TaskGroup;
use PhalconX\Annotations\Cli\Option;
use PhalconX\Annotations\Cli\Argument;

use PhalconX\Annotations\Mvc\Router\Route;
use PhalconX\Annotations\Mvc\Router\Get;
use PhalconX\Annotations\Mvc\Router\Post;
use PhalconX\Annotations\Mvc\Router\Put;
use PhalconX\Annotations\Mvc\Router\Delete;
use PhalconX\Annotations\Mvc\Router\RoutePrefix;

use PhalconX\Annotations\Mvc\Filter\Acl;
use PhalconX\Annotations\Mvc\Filter\CsrfToken;
use PhalconX\Annotations\Mvc\Filter\DeleteOnly;
use PhalconX\Annotations\Mvc\Filter\DisableView;
use PhalconX\Annotations\Mvc\Filter\Json;
use PhalconX\Annotations\Mvc\Filter\LoginOnly;
use PhalconX\Annotations\Mvc\Filter\PostOnly;
use PhalconX\Annotations\Mvc\Filter\PutOnly;
use PhalconX\Annotations\Mvc\Filter\RequestMethod;

use PhalconX\Annotations\Forms\Check;
use PhalconX\Annotations\Forms\Date;
use PhalconX\Annotations\Forms\Email;
use PhalconX\Annotations\Forms\File;
use PhalconX\Annotations\Forms\Hidden;
use PhalconX\Annotations\Forms\Numeric;
use PhalconX\Annotations\Forms\Password;
use PhalconX\Annotations\Forms\Radio;
use PhalconX\Annotations\Forms\Select;
use PhalconX\Annotations\Forms\Submit;
use PhalconX\Annotations\Forms\Text as TextElement;
use PhalconX\Annotations\Forms\TextArea;
use PhalconX\Annotations\Forms\Input;

class Annotations
{
    private $extension = ".php";
    private $aliases = [
        // validator
        'Valid'       => Valid::CLASS,
        'IsA'         => IsA::CLASS,

        // cli
        'Task'        => Task::CLASS,
        'TaskGroup'   => TaskGroup::CLASS,
        'Option'      => Option::CLASS,
        'Argument'    => Argument::CLASS,

        // router
        'Route'       => Route::CLASS,
        'Get'         => Get::CLASS,
        'Post'        => Post::CLASS,
        'Put'         => Put::CLASS,
        'Delete'      => Delete::CLASS,
        'RoutePrefix' => RoutePrefix::CLASS,

        // filters
        'Acl'           => Acl::CLASS,
        'CsrfToken'     => CsrfToken::CLASS,
        'DeleteOnly'    => DeleteOnly::CLASS,
        'DisableView'   => DisableView::CLASS,
        'Json'          => Json::CLASS,
        'LoginOnly'     => LoginOnly::CLASS,
        'PostOnly'      => PostOnly::CLASS,
        'PutOnly'       => PutOnly::CLASS,
        'RequestMethod' => RequestMethod::CLASS,
        
        // form
        'Input'       => Input::CLASS,
        'Check'       => Check::CLASS,
        'Date'        => Date::CLASS,
        'Email'       => Email::CLASS,
        'File'        => File::CLASS,
        'Hidden'      => Hidden::CLASS,
        'Numeric'     => Numeric::CLASS,
        'Password'    => Password::CLASS,
        'Radio'       => Radio::CLASS,
        'Select'      => Select::CLASS,
        'Submit'      => Submit::CLASS,
        'Text'        => TextElement::CLASS,
        'TextArea'    => TextArea::CLASS,
    ];

    private $modelsMetadata;
    private $reflection;
    private $logger;
    private $reader;

    public function __construct($options = null)
    {
        if (isset($options['aliases'])) {
            $this->setAliases($options['aliases']);
        }
        if (isset($options['extension'])) {
            $this->extension = $options['extension'];
        }
        $this->modelsMetadata = Util::service('modelsMetadata', $options, false);
        $this->reflection = Util::service('reflection', $options);
        $this->logger = Util::service('logger', $options, false);
    }

    /**
     * @return Collection
     */
    public function get($clz, $annotationClass = null, $contextType = null)
    {
        $contextType = $this->filterContextType($contextType);
        $annotations = $this->parse($clz, $contextType);
        if ($annotationClass) {
            $ret = [];
            foreach ($annotations as $annotation) {
                if (is_a($annotation, $annotationClass)) {
                    $ret[] = $annotation;
                }
            }
            return new Collection($ret);
        } else {
            return new Collection($annotations);
        }
    }

    /**
     * @return Collection
     */
    public function getAnnotations($clz, $annotationClass = null, $contextType = null)
    {
        return $this->get($clz, $annotationClass, $contextType);
    }
    
    public function getClassAnnotations($clz)
    {
        return $this->get($clz, null, [ContextType::T_CLASS]);
    }

    public function getMethodAnnotations($clz, $method = null)
    {
        $annotations = $this->get($clz, null, [ContextType::T_METHOD]);
        if (isset($method)) {
            return $annotations->method($method);
        } else {
            return $annotations;
        }
    }

    public function getPropertiesAnnotations($clz, $property = null)
    {
        $annotations = $this->parse($clz, [ContextType::T_PROPERTY]);
        if (isset($property)) {
            return $annotations->property($property);
        } else {
            return $annotations;
        }
    }
    
    public function scan($dir, $annotationClass = null, $contextType = null)
    {
        $self = $this;
        $annotations = [];
        Util::walkdir($dir, function ($file) use ($self, $annotationClass, $contextType, &$annotations) {
            $annotations = array_merge(
                $annotations,
                $self->parseFile($file, $contextType)
            );
        });
        $collection = new Collection($annotations);
        if ($annotationClass) {
            return $collection->isa($annotationClass);
        } else {
            return $collection;
        }
    }
    
    public function scanFile($file, $annotationClass = null, $contextType = null)
    {
        $collection = new Collection($this->parseFile($file, $contextType));
        if ($annotationClass) {
            return $collection->isa($annotationClass);
        } else {
            $collection;
        }
    }

    public function resolveImport($name, $declaringClass)
    {
        if (empty($declaringClass)) {
            throw new Exception("Cannot resolve class '$name' without declaring class");
        }
        return $this->reflection->resolveImport($name, $declaringClass);
    }

    public function resolveAnnotation($annotation, $context)
    {
        $name = $annotation->getName();
        if (isset($this->aliases[$name])) {
            $clz = $this->aliases[$name];
        } else {
            if (!$context->declaringClass) {
                throw new Exception("Cannot resolve annotation '$name' without declaring class");
            }
            $clz = $this->resolveImport($name, $context->declaringClass);
        }
        if ($clz && is_subclass_of($clz, Annotation::CLASS)) {
            $obj = new $clz($annotation->getArguments());
            $obj->setContext($context);
            return $obj;
        }
    }

    private function parseFile($file, $contextType)
    {
        if (!Text::endsWith($file, $this->extension)) {
            return [];
        }
        $contextType = $this->filterContextType($contextType);
        $annotations = [];
        foreach ($this->reflection->getClasses($file) as $clz) {
            $annotations = array_merge(
                $annotations,
                $this->parse($clz, $contextType)
            );
        }
        return $annotations;
    }
    
    private function parse($clz, $contextType)
    {
        if ($this->modelsMetadata) {
            $annotations = $this->modelsMetadata->read($clz.'.annotations');
        }
        if (!isset($annotations)) {
            $annotations = $this->read($clz);
            $this->modelsMetadata->write($clz.'.annotations', $annotations);
        }
        $ret = [];
        foreach ($annotations as $annotation) {
            if (in_array($annotation->getContextType(), $contextType)) {
                $annotation->setAnnotations($this);
                $ret[] = $annotation;
            }
        }
        return $ret;
    }

    private function read($clz)
    {
        $annotations = [];
        $parsed = $this->getReader()->parse($clz);
        if (!is_array($parsed)) {
            return $annotations;
        }
        $refl = new \ReflectionClass($clz);
        $context = [
            'class' => $clz,
            'declaringClass' => $clz,
            'type' => 'class',
        ];
        if (!empty($parsed['class'])) {
            foreach ($parsed['class'] as $annotation) {
                $this->add($annotations, $annotation, $context);
            }
        }
        if (!empty($parsed['methods'])) {
            $context['type'] = 'method';
            foreach ($parsed['methods'] as $method => $methodAnnotations) {
                $context['declaringClass'] = $refl->getMethod($method)->getDeclaringClass()->getName();
                $context['method'] = $method;
                foreach ($methodAnnotations as $annotation) {
                    $this->add($annotations, $annotation, $context);
                }
            }
        }
        if (!empty($parsed['properties'])) {
            $context['type'] = 'property';
            foreach ($parsed['properties'] as $property => $propertyAnnotations) {
                $context['declaringClass'] = $refl->getProperty($property)->getDeclaringClass()->getName();
                $context['property'] = $property;
                foreach ($propertyAnnotations as $annotation) {
                    $this->add($annotations, $annotation, $context);
                }
            }
        }
        return $annotations;
    }

    protected function add(&$annotations, $annotation, $context)
    {
        if (!$this->isValid($annotation['name'])) {
            return;
        }
        $obj = $this->resolveAnnotation(
            new PhalconAnnotation($annotation),
            new Context($context)
        );
        if ($obj) {
            $annotations[] = $obj;
        }
    }

    private function filterContextType($contextType)
    {
        if ($contextType) {
            if (!is_array($contextType)) {
                if (!ContextType::hasValue($contextType)) {
                    throw new Exception(
                        "Unknown context type '$contextType'",
                        Exception::ERROR_INVALID_ARGUMENT
                    );
                }
                $contextType = [$contextType];
            }
        } else {
            $contextType = ContextType::values();
        }
        return $contextType;
    }
    
    /**
     * Annotation name start with uppercase lettter
     */
    protected function isValid($name)
    {
        return ctype_upper($name[0]);
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function setExtension($extension)
    {
        $this->extension = $extension;
        return $this;
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
        return $this;
    }
    
    public function setAlias($name, $annotationClass)
    {
        $this->aliases[$name] = $annotationClass;
        return $this;
    }

    public function getModelsMetadata()
    {
        return $this->modelsMetadata;
    }

    public function setModelsMetadata($modelsMetadata)
    {
        $this->modelsMetadata = $modelsMetadata;
        return $this;
    }

    public function getReflection()
    {
        return $this->reflection;
    }

    public function setReflection($reflection)
    {
        $this->reflection = $reflection;
        return $this;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getReader()
    {
        if (!$this->reader) {
            $this->reader = new Reader();
        }
        return $this->reader;
    }

    public function setReader($reader)
    {
        $this->reader = $reader;
        return $this;
    }
}
