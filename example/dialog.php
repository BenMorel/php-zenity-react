<?php

use React\EventLoop\Factory;
use Clue\React\Zenity\Launcher;
use Clue\React\Zenity\Builder;

require __DIR__ . '/../vendor/autoload.php';

$loop = Factory::create();

$launcher = new Launcher($loop);
$builder = new Builder($launcher);

$builder->entry('What\'s your name?', getenv('USER'))->setTitle('Enter your name')->run()->then(function ($name) use ($builder) {
    $builder->info('Welcome to the introduction of zenity, ' . $name)->run()->then(function () use ($builder) {
        $builder->question('Do you want to quit?')->run()->then(function () use ($builder) {
            $builder->error('Oh noes!')->run();
        }, function() use ($builder) {
            $builder->warning('You should have selected yes!')->run();
        });
    });
}, function () use ($builder) {
    $builder->warning('No name given')->run();
});

$loop->run();
