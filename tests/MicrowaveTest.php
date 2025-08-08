<?php

namespace Tests;

use Microwave\Microwave;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;

class MicrowaveTest extends TestCase
{
    private Microwave $microwave;

    protected function setUp(): void
    {
        $this->microwave = new Microwave();
        // Limpa a sessão antes de cada teste
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testStartWithValidParameters()
    {
        $result = $this->microwave->start(30, 5);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(30, $result['time']);
        $this->assertEquals(5, $result['power']);
        $this->assertFalse($result['isPaused']);
    }

    public function testStartWithInvalidTime()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->microwave->start(0, 5);
    }

    public function testStartWithInvalidPower()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->microwave->start(30, 11);
    }

    public function testPauseWhenRunning()
    {
        $this->microwave->start(30, 5);
        $result = $this->microwave->pause();
        
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['isPaused']);
        $this->assertLessThanOrEqual(30, $result['time']);
    }

    public function testPauseWhenNotRunning()
    {
        $result = $this->microwave->pause();
        $this->assertEquals('error', $result['status']);
    }

    public function testResumeWhenPaused()
    {
        $this->microwave->start(30, 5);
        $this->microwave->pause();
        $result = $this->microwave->resume();
        
        $this->assertEquals('success', $result['status']);
        $this->assertFalse($result['isPaused']);
    }

    public function testResumeWhenNotPaused()
    {
        $result = $this->microwave->resume();
        $this->assertEquals('error', $result['status']);
    }

    public function testGetStatusWhenInactive()
    {
        $result = $this->microwave->getStatus();
        $this->assertEquals('inactive', $result['status']);
    }

    public function testGetStatusWhenRunning()
    {
        $this->microwave->start(30, 5);
        $result = $this->microwave->getStatus();
        
        $this->assertEquals('active', $result['status']);
        $this->assertEquals(5, $result['power']);
        $this->assertFalse($result['isPaused']);
    }

    public function testGetStatusWhenPaused()
    {
        $this->microwave->start(30, 5);
        $this->microwave->pause();
        $result = $this->microwave->getStatus();
        
        $this->assertTrue($result['isPaused']);
    }

    public function testListProgramsWithValidFile()
    {
        // Cria um arquivo temporário para teste
        $tempFile = tempnam(sys_get_temp_dir(), 'microwave');
        file_put_contents($tempFile, json_encode([
            ['id' => 1, 'name' => 'Test Program']
        ]));
        
        // Usando Reflection para substituir o caminho do arquivo
        $reflection = new \ReflectionClass($this->microwave);
        $property = $reflection->getProperty('programsFile');
        $property->setAccessible(true);
        $property->setValue($this->microwave, $tempFile);
        
        $result = $this->microwave->listPrograms();
        
        $this->assertEquals('success', $result['status']);
        $this->assertCount(1, $result['programs']);
        
        unlink($tempFile);
    }

    public function testListProgramsWithInvalidFile()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'microwave');
        unlink($tempFile);
        
        $reflection = new \ReflectionClass($this->microwave);
        $property = $reflection->getProperty('programsFile');
        $property->setAccessible(true);
        $property->setValue($this->microwave, $tempFile);
        
        $result = $this->microwave->listPrograms();
        
        $this->assertEquals('error', $result['status']);
    }

    public function testAddProgramWithValidData()
    {
        // Configura arquivo temporário para o teste
        $tempFile = tempnam(sys_get_temp_dir(), 'microwave');
        file_put_contents($tempFile, json_encode([]));
        
        $reflection = new \ReflectionClass($this->microwave);
        $property = $reflection->getProperty('programsFile');
        $property->setAccessible(true);
        $property->setValue($this->microwave, $tempFile);
        
        $result = $this->microwave->addProgram('Novo Programa', 'Teste', 60, 8, 'Instruções');
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Novo Programa', $result['program']['name']);
        
        $content = json_decode(file_get_contents($tempFile), true);
        $this->assertCount(1, $content);
        
        unlink($tempFile);
    }

    public function testAddProgramWithInvalidTime()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->microwave->addProgram('Novo Programa', 'Teste', 0, 8, 'Instruções');
    }

    public function testRemoveProgram()
    {
        // Configura arquivo temporário com dados iniciais do programa
        $tempFile = tempnam(sys_get_temp_dir(), 'microwave');
        file_put_contents($tempFile, json_encode([
            ['id' => 1, 'name' => 'Programa 1'],
            ['id' => 6, 'name' => 'Programa 6']
        ]));
        
        $reflection = new \ReflectionClass($this->microwave);
        $property = $reflection->getProperty('programsFile');
        $property->setAccessible(true);
        $property->setValue($this->microwave, $tempFile);
        
        $result = $this->microwave->removeProgram(6);
        
        $this->assertEquals('success', $result['status']);
        
        $content = json_decode(file_get_contents($tempFile), true);
        $this->assertCount(1, $content);
        $this->assertEquals(1, $content[0]['id']);
        
        unlink($tempFile); // Limpeza
    }

    public function testCannotRemoveDefaultProgram()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->microwave->removeProgram(1);
    }
}