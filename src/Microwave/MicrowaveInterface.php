<?php

namespace Microwave\Microwave;

interface MicrowaveInterface
{
    public function start(int $time, int $power): array;
    public function validateTime(int $time): void;
    public function validatePower(int $power): void;
}
