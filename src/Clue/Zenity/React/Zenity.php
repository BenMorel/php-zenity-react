<?php

namespace Clue\Zenity\React;

use React\Promise\PromiseInterface;
use React\Promise\Deferred;

abstract class Zenity implements PromiseInterface
{
    private $deferred;
    private $result;
    protected $process;

    protected $title;
    protected $windowIcon;
    protected $timeout;
    protected $modal = false;
    protected $width;
    protected $height;
    protected $okLabel;
    protected $cancelLabel;

    public function __construct()
    {
        $this->deferred = new Deferred();
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setWindowIcon($icon)
    {
        $this->windowIcon = $icon;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = (int)$timeout;
    }

    public function setModal($modal)
    {
        $this->modal = !!$modal;
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function setOkLabel($label)
    {
        $this->okLabel = $label;
    }

    public function setCancelLabel($label)
    {
        $this->cancelLabel = $label;
    }

    public function run(Launcher $launcher)
    {
        if ($this->process !== null) {
            return $this;
        }

        $args = $this->getArgs();

        $this->process = $process = $launcher->run($args);

        $result =& $this->result;
        $process->outputStream()->on('data', function ($data) use (&$result) {
            if ($data !== '') {
                $result .= $data;
            }
        });

        $process->outputStream()->on('end', function() use ($process, &$result) {
            $code = $process->status()->exitCode();
            if ($result === null) {
                $result = ($code === 0);
            }
        });
        $process->outputStream()->on('end', array($this, 'onEnd'));

        return $this;
    }

    public function wait(Launcher $launcher)
    {
        return $launcher->wait($this);
    }

    public function getType()
    {
        return $this->decamelize(basename(str_replace('\\', '/', get_class($this))));
    }

    public function getArgs()
    {

        $args = array(
            '--' . $this->getType()
        );

        foreach ($this as $name => $value) {
            if (!in_array($name, array('deferred', 'result', 'process')) && $value !== null && $value !== false && !is_array($value)) {
                $name = $this->decamelize($name);

                if ($name === true) {
                    $args[] = $value;
                } else {
                    $args[$name] = $value;
                }
            }
        }

        return $args;
    }

    protected function decamelize($name)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));
    }

    protected function parseValue($value)
    {
        return $value;
    }

    public function onEnd()
    {
        $this->deferred->resolve($this->parseValue(trim($this->result)));

        $this->close();
    }

    public function then($fulfilledHandler = null, $errorHandler = null, $progressHandler = null)
    {
        return $this->deferred->then($fulfilledHandler, $errorHandler, $progressHandler);
    }

    public function close()
    {
        if ($this->process !== null) {
            $this->process->kill();

            $streams = array($this->process->outputStream(), $this->process->inputStream(), $this->process->errorStream());
            foreach ($streams as $stream) {
                if ($stream !== null) {
                    $stream->close();
                }
            }

            $this->process = null;
        }
    }

    protected function writeln($line)
    {
        if ($this->process !== null) {
            $this->process->inputStream()->write($line . PHP_EOL);
        }
    }
}
