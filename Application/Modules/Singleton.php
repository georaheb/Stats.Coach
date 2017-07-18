<?php

namespace Modules;

require_once SERVER_ROOT . 'Application/Modules/Helpers/Serialized.php';

trait Singleton
{
    public $storage;               // A Temporary variable for 'quick data'
    protected $methods = array();   // Anonymous Function Declaration
    private static $instance;        // Instance of the Container
    

    public static function __callStatic($methodName, $arguments = array())
    {
        return self::getInstance()->Skeleton( $methodName, $arguments );
    }

    public static function newInstance(...$args)
    {   // Start a new instance of the class and pass any arguments
        self::clearInstance();
        $class = new \ReflectionClass( get_called_class() );
        self::$instance = $class->newInstanceArgs( $args );
        return self::$instance;
    }

    public static function getInstance(...$args)
    {   // see if the class has already been called this run
        if (!empty(self::$instance))
            return self::$instance;
        $calledClass = get_called_class();
        // check if the object has been sterilized in the session
        // This will invoke the __wake up operator
        if (isset( $_SESSION[$calledClass] ) && is_serialized( $_SESSION[$calledClass], self::$instance))
            return self::$instance;
        // Start a new instance of the class and pass any arguments
        $class = new \ReflectionClass( get_called_class() );
        self::$instance = $class->newInstanceArgs($args);
        return self::$instance;
    }

    /**
     * @param null $object
     * @return object|null
     */
    public static function clearInstance($object = null)
    {
        self::$instance = is_object( $object ) ? $object : null;
        unset($_SESSION[__CLASS__]);
        return self::$instance;
    }
    
    public function __call($methodName, $arguments = array())
    {
        return $this->Skeleton( $methodName, $arguments );
    }

    private function Skeleton($methodName, $arguments = array())
    {
        // Have we used addMethod() to override an existing method
        if (key_exists( $methodName, $this->methods ))
            return (null === ($result = call_user_func_array( $this->methods[$methodName], $arguments )) ? $this : $result);
        // Is the method in the current scope ( public, protected, private ).
        // Note declaring the method as private is the only way to ensure single instancing
        if (method_exists( $this, $methodName )) {
            return (null === ($result = call_user_func_array( array($this, $methodName), $arguments )) ? $this : $result);
        }
        if (key_exists( 'closures', $GLOBALS ) && key_exists( $methodName, $GLOBALS['closures'] )) {
            $function = $GLOBALS['closures'][$methodName];
            $this->addMethod( $methodName, $function );
            return (null === ($result = call_user_func_array( $this->methods[$methodName], $arguments )) ? $this : $result);
        } throw new \Exception( "There is valid method or closure with the given name '$methodName' to call" );
    }

    private function addMethod($name, $closure)
    {
        if (is_callable( $closure )):
            $this->methods[$name] = \Closure::bind( $closure, $this, get_called_class() );
        else: // Nested to ensure Singleton returns the correct value of self
            throw new \Exception( "New Method Must Be A Valid Closure" );
        endif;
    }

    public function __wakeup()
    {
        if (method_exists( $this, '__construct' )) self::__construct();
        $object = get_object_vars( $this );
        foreach ($object as $item => $value) is_serialized($value, $this->$item);  // TODO - base64_decode(
    }

    // for auto class serialization add: const Singleton = true; to calling class
    public function __sleep()
    {
        if (!defined( 'self::Singleton' ) || !self::Singleton) return null;
        foreach (get_object_vars( $this ) as $key => &$value) {
            if (empty($value)) continue;    // The object could be null from serialization?
            if (is_object( $value )) {
                try { $this->$key = (@serialize( $value ));                      // TODO - base64_encode(
                } catch (\Exception $e){ continue; }                // Database object we need to catch the error thrown.
            } $onlyKeys[] = $key;
        } return (isset($onlyKeys) ? $onlyKeys : []);
    }

    public function __destruct()
    {   // We require a sleep function to be set manually for singleton to manage utilization
        if (!defined( 'self::Singleton' ) || !self::Singleton) return null;
        try { $_SESSION[__CLASS__] = @serialize( $this );                               // TODO - base64_encode(
        } catch (\Exception $e){ unset($_SESSION[__CLASS__]); return null; };
    }

    // The rest of the methods are for the sake of methods
    public function &__get($variable)
    {
        return $GLOBALS[$variable];
    }

    public function __set($variable, $value)
    {
        $GLOBALS[$variable] = $value;
    }

    public function __isset($variable)
    {
        return array_key_exists( $variable, $GLOBALS );
    }

    public function __unset($variable)
    {
        unset($GLOBALS[$variable]);
    }

    public function __invoke()
    {
        return $this->storage;
    }

    public function set(...$argv)
    {
        $this->storage = $argv;
        return $this;
    }

    public function get($variable = null)
    {
        return ($variable == null ?
            $this->storage :
            $this->{$variable});
    }

    public function has($variable)
    {
        return isset($this->$variable);
    }
}
