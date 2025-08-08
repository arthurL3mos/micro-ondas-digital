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
        // Destrói a sessão completamente antes de cada teste
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        // Reinicia a sessão
        session_start();
        $_SESSION = [];

        $this->microwave = new Microwave();
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
        // Cria um arquivo temporário para teste com dados controlados
        $tempFile = tempnam(sys_get_temp_dir(), 'microwave');
        file_put_contents($tempFile, json_encode([
            ['id' => 99, 'name' => 'Test Program'] // Dados específicos para teste
        ]));

        // Usando Reflection para substituir o caminho do arquivo
        $reflection = new \ReflectionClass($this->microwave);
        $property = $reflection->getProperty('programsFile');
        $property->setAccessible(true);
        $originalFile = $property->getValue($this->microwave);
        $property->setValue($this->microwave, $tempFile);

        $result = $this->microwave->listPrograms();

        // Restaura o valor original
        $property->setValue($this->microwave, $originalFile);
        unlink($tempFile);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(1, $result['programs']);
        $this->assertEquals('Test Program', $result['programs'][0]['name']);
    }

    public function testListProgramsWithInvalidFile()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'microwave');
        unlink($tempFile); // Garante que o arquivo não existe

        $reflection = new \ReflectionClass($this->microwave);
        $property = $reflection->getProperty('programsFile');
        $property->setAccessible(true);
        $originalFile = $property->getValue($this->microwave);
        $property->setValue($this->microwave, $tempFile);

        $result = $this->microwave->listPrograms();

        // Restaura o valor original
        $property->setValue($this->microwave, $originalFile);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('não encontrado', $result['message']);
    }

    public function testAddProgramWithValidData()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'microwave');
        file_put_contents($tempFile, json_encode([]));

        $reflection = new \ReflectionClass($this->microwave);
        $property = $reflection->getProperty('programsFile');
        $property->setAccessible(true);
        $originalFile = $property->getValue($this->microwave);
        $property->setValue($this->microwave, $tempFile);

        $result = $this->microwave->addProgram('Novo Programa', 'Teste', 60, 8, 'Instruções');

        // Verifica se o arquivo foi modificado
        $this->assertFileExists($tempFile);
        $content = json_decode(file_get_contents($tempFile), true);

        // Limpeza
        $property->setValue($this->microwave, $originalFile);
        unlink($tempFile);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertEquals('Novo Programa', $content[0]['name']);
    }

    public function testAddProgramWithInvalidTime()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->microwave->addProgram('Novo Programa', 'Teste', 0, 8, 'Instruções');
    }

    public function testRemoveProgram()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'microwave');
        file_put_contents($tempFile, json_encode([
            ['id' => 1, 'name' => 'Programa 1'],
            ['id' => 6, 'name' => 'Programa 6'],
            ['id' => 7, 'name' => 'Programa 7']
        ]));

        $reflection = new \ReflectionClass($this->microwave);
        $property = $reflection->getProperty('programsFile');
        $property->setAccessible(true);
        $originalFile = $property->getValue($this->microwave);
        $property->setValue($this->microwave, $tempFile);

        $result = $this->microwave->removeProgram(6);

        $content = json_decode(file_get_contents($tempFile), true);

        // Limpeza
        $property->setValue($this->microwave, $originalFile);
        unlink($tempFile);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(2, $content);

        // Verifica quais programas permaneceram
        $remainingIds = array_column($content, 'id');
        $this->assertContains(1, $remainingIds);
        $this->assertContains(7, $remainingIds);
        $this->assertNotContains(6, $remainingIds);
    }

    public function testCannotRemoveDefaultProgram()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->microwave->removeProgram(1);
    }
}
