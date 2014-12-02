<?php namespace Orchestra\Asset;

class Container
{
    /**
     * Asset Dispatcher instance.
     *
     * @var \Orchestra\Asset\Dispatcher
     */
    protected $dispatcher;

    /**
     * The asset container name.
     *
     * @var string
     */
    protected $name;

    /**
     * The asset container path prefix.
     *
     * @var string
     */
    protected $path = null;

    /**
     * All of the registered assets.
     *
     * @var array
     */
    protected $assets = array();

    /**
     * All of the registered assets.
     *
     * @var array
     */
    protected $override = array();

    /**
     * All of the removed assets.
     *
     * @var array
     */
    protected $removable = array();

    /**
     * Create a new asset container instance.
     *
     * @param  string                       $name
     * @param  \Orchestra\Asset\Dispatcher  $dispatcher
     */
    public function __construct($name, Dispatcher $dispatcher)
    {
        $this->name = $name;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Enable asset versioning.
     *
     * @return Container
     */
    public function addVersioning()
    {
        $this->dispatcher->addVersioning();

        return $this;
    }

    /**
     * Disable asset versioning.
     *
     * @return Container
     */
    public function removeVersioning()
    {
        $this->dispatcher->removeVersioning();

        return $this;
    }

    /**
     * Set the asset container path prefix.
     *
     * @param  string|null $path
     * @return Container
     */
    public function prefix($path = null)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Add an asset to the container.
     *
     * The extension of the asset source will be used to determine the type
     * of asset being registered (CSS or JavaScript). When using a non-standard
     * extension, the style/script methods may be used to register assets.
     *
     * <code>
     *     // Add an asset to the container
     *     Orchestra\Asset::container()->add('jquery', 'js/jquery.js');
     *
     *     // Add an asset that has dependencies on other assets
     *     Orchestra\Asset::add('jquery', 'js/jquery.js', 'jquery-ui');
     *
     *     // Add an asset that should have attributes applied to its tags
     *     Orchestra\Asset::add('jquery', 'js/jquery.js', null, array('defer'));
     * </code>
     *
     * @param  string  $name
     * @param  string  $source
     * @param  array   $dependencies
     * @param  array   $attributes
     * @return \Orchestra\Asset\Container
     */
    public function add($name, $source, $dependencies = array(), $attributes = array())
    {
        $type = (pathinfo($source, PATHINFO_EXTENSION) == 'css') ? 'style' : 'script';

        return $this->$type($name, $source, $dependencies, $attributes);
    }

    /**
     * Add a CSS file to the registered assets.
     *
     * @param  string   $name
     * @param  string   $source
     * @param  array    $dependencies
     * @param  array    $attributes
     * @return \Orchestra\Asset\Container
     */
    public function style($name, $source, $dependencies = array(), $attributes = array())
    {
        if (! array_key_exists('media', $attributes)) {
            $attributes['media'] = 'all';
        }

        $this->register('style', $name, $source, $dependencies, $attributes);

        return $this;
    }
    /**
     * Remove style or scripts from Asset
     * @param  string $type type (style or scripts)
     * @param  string $name element name
     * @return \Orchestra\Asset\Container
     */
    public function remove($type, $name){
        if(!in_array($type, array('script','style'))) {
            return null;
        }
        $this->unregister($type, $name);

        return $this;
    }

    /**
     * Add a JavaScript file to the registered assets.
     *
     * @param  string   $name
     * @param  string   $source
     * @param  array    $dependencies
     * @param  array    $attributes
     * @return Container
     */
    public function script($name, $source, $dependencies = array(), $attributes = array())
    {
        $this->register('script', $name, $source, $dependencies, $attributes);

        return $this;
    }

    /**
     * Add an asset to the array of registered assets.
     *
     * @param  string   $type
     * @param  string   $name
     * @param  string   $source
     * @param  array    $dependencies
     * @param  array    $attributes
     * @return void
     */
    protected function register($type, $name, $source, $dependencies, $attributes)
    {
        $dependencies = (array) $dependencies;
        $attributes   = (array) $attributes;

        if(starts_with($name, '!')){
            $name = substr($name, 1, strlen($name) -1 );
            $this->override[$type][$name] = array(
                'source'       => $source,
                'dependencies' => $dependencies,
                'attributes'   => $attributes,
            );
        }

        $this->assets[$type][$name] = array(
            'source'       => $source,
            'dependencies' => $dependencies,
            'attributes'   => $attributes,
        );
    }

    /**
     * Remove an asset of registered assets.
     *
     * @param  string   $type
     * @param  string   $name
     * @param  string   $source
     * @param  array    $dependencies
     * @param  array    $attributes
     * @return void
     */
    protected function unregister($type, $name)
    {
        $this->removable[$type][] = array(
            'type' => $type,
            'name' => $name
        );
    }

    /**
     * Get the links to all of the registered CSS assets.
     *
     * @return string
     */
    public function styles()
    {
        return $this->group('style');
    }

    /**
     * Get the links to all of the registered JavaScript assets.
     *
     * @return string
     */
    public function scripts()
    {
        return $this->group('script');
    }

    /**
     * Get the links to all the registered CSS and JavaScript assets.
     *
     * @access public
     * @return string
     */
    public function show()
    {
        return $this->group('script').$this->group('style');
    }

    /**
     * Get all of the registered assets for a given type / group.
     *
     * @param  string  $group
     * @return string
     */
    protected function group($group)
    {
        if(isset($this->override[$group])){
            foreach($this->override[$group] as $name => $over){
                $this->assets[$group][$name] = $over;
            }
            unset($this->override[$group]);
        }
        
        return $this->dispatcher->run($group, $this->assets, $this->removable, $this->path);
    }
}
