<?php namespace Orchestra\Asset\TestCase;

use Mockery as m;
use Orchestra\Asset\Dispatcher;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Test Orchesta\Asset\Dispatcher::run() method.
     *
     * @test
     */
    public function testRunMethod()
    {
        $files = m::mock('\Illuminate\Filesystem\Filesystem');
        $html = m::mock('\Illuminate\Html\HtmlBuilder');
        $resolver = m::mock('\Orchestra\Asset\DependencyResolver');
        $path = '/var/public';

        $script = array(
            'jquery' => array(
                'source'       => '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js',
                'dependencies' => array(),
                'attributes'   => array(),
            ),
            'foo' => array(
                'source'       => 'foo.js',
                'dependencies' => array(),
                'attributes'   => array(),
            ),
            'foobar' => null,
        );

        $assets = array(
            'script' => $script,
            'style'  => array(),
        );

        $files->shouldReceive('lastModified')->once()->andReturn('');
        $html->shouldReceive('script')->twice()
                ->with('foo.js', m::any())
                ->andReturn('foo')
            ->shouldReceive('script')->twice()
                ->with('//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js', m::any())
                ->andReturn('jquery');
        $resolver->shouldReceive('arrange')->twice()->with($script)->andReturn($script);

        $stub = new Dispatcher($files, $html, $resolver, $path);

        $stub->addVersioning();

        $this->assertEquals('jqueryfoo', $stub->run('script', $assets));
        $this->assertEquals('', $stub->run('style', $assets));

        $stub->removeVersioning();

        $this->assertEquals('jqueryfoo', $stub->run('script', $assets));
    }

    /**
     * Test Orchesta\Asset\Dispatcher::run() method using remote path.
     *
     * @test
     */
    public function testRunMethodUsingRemotePath()
    {
        $files = m::mock('\Illuminate\Filesystem\Filesystem');
        $html = m::mock('\Illuminate\Html\HtmlBuilder');
        $resolver = m::mock('\Orchestra\Asset\DependencyResolver');
        $path = '//cdn.foobar.com';

        $script = array(
            'jquery' => array(
                'source'       => '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js',
                'dependencies' => array(),
                'attributes'   => array(),
            ),
            'foo' => array(
                'source'       => 'foo.js',
                'dependencies' => array(),
                'attributes'   => array(),
            ),
            'foobar' => null,
        );

        $assets = array(
            'script' => $script,
            'style'  => array(),
        );

        $html->shouldReceive('script')->twice()
                ->with('//cdn.foobar.com/foo.js', m::any())
                ->andReturn('foo')
            ->shouldReceive('script')->twice()
                ->with('//cdn.foobar.com/ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js', m::any())
                ->andReturn('jquery');
        $resolver->shouldReceive('arrange')->twice()->with($script)->andReturn($script);

        $stub = new Dispatcher($files, $html, $resolver, $path);

        $stub->addVersioning();

        $this->assertEquals('jqueryfoo', $stub->run('script', $assets));
        $this->assertEquals('', $stub->run('style', $assets));

        $stub->removeVersioning();

        $this->assertEquals('jqueryfoo', $stub->run('script', $assets));
    }
}
