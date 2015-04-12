<?php

use Clue\React\Zenity\Zen\TextInfoZen;

class TextInfoZenTest extends BaseZenTest
{
    public function testAddLine()
    {
        $zen = new TextInfoZen();
        $zen->go($this->process);

        $this->assertSame($zen, $zen->addLine('hello'));
        $this->assertSame($zen, $zen->addLine('world'));

        $this->assertEquals('hello' . PHP_EOL . 'world' . PHP_EOL, $this->stdin);
    }
}
