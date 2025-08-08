<?php

namespace Microwave;

use RuntimeException;
use InvalidArgumentException;

class Microwave
{
    private $aquecimentoAtivo = false;
    private $tempoRestante = 0;
    private $potenciaAtual = 0;
    private $isPaused = false;
    private $ultimaAtualizacao = 0;

    public function start(int $time, int $power, bool $isPredefined = false): array
    {
        $this->validateTime($time, $isPredefined);
        $this->validatePower($power);

        $this->aquecimentoAtivo = true;
        $this->tempoRestante = $time;
        $this->potenciaAtual = $power;
        $this->isPaused = false;
        $this->ultimaAtualizacao = time();

        $this->saveState();

        return [
            'status' => 'success',
            'time' => $this->tempoRestante,
            'power' => $this->potenciaAtual,
            'isPaused' => $this->isPaused,
            'message' => 'Micro-ondas iniciado com sucesso'
        ];
    }

    public function pause(): array
    {
        $this->loadState();

        if (!$this->aquecimentoAtivo) {
            return ['status' => 'error', 'message' => 'Nenhum aquecimento ativo para pausar'];
        }

        if ($this->isPaused) {
            return ['status' => 'error', 'message' => 'Aquecimento já está pausado'];
        }

        // Atualiza o tempo restante antes de pausar
        $this->updateRemainingTime();
        if ($this->tempoRestante <= 0) {
            $this->aquecimentoAtivo = false;
            $this->saveState();
            return ['status' => 'error', 'message' => 'Aquecimento já concluído'];
        }
        $this->isPaused = true;
        $this->saveState();

        return [
            'status' => 'success',
            'time' => $this->tempoRestante,
            'isPaused' => true,
            'message' => 'Aquecimento pausado'
        ];
    }

    public function resume(): array
    {
        $this->loadState();

        if (!$this->aquecimentoAtivo) {
            return ['status' => 'error', 'message' => 'Nenhum aquecimento ativo para retomar'];
        }

        if (!$this->isPaused) {
            return ['status' => 'error', 'message' => 'Aquecimento não está pausado'];
        }

        $this->isPaused = false;
        $this->ultimaAtualizacao = time();
        $this->saveState();

        return [
            'status' => 'success',
            'time' => $this->tempoRestante,
            'isPaused' => false,
            'message' => 'Aquecimento retomado'
        ];
    }

    public function getStatus(): array
    {
        $this->loadState();

        if (!$this->aquecimentoAtivo) {
            return ['status' => 'inactive', 'message' => 'Nenhum aquecimento ativo'];
        }

        $this->updateRemainingTime();

        if ($this->tempoRestante <= 0) {
            $this->aquecimentoAtivo = false;
            $this->saveState();
            return ['status' => 'completed', 'message' => 'Aquecimento concluído'];
        }

        $this->saveState();

        return [
            'status' => 'active',
            'time' => $this->tempoRestante,
            'power' => $this->potenciaAtual,
            'isPaused' => $this->isPaused
        ];
    }

    public function listPrograms(): array
    {
        $filePath = __DIR__ . '/programs.json';

        if (!file_exists($filePath)) {
            return ['status' => 'error', 'message' => 'Arquivo de programas não encontrado'];
        }

        $programs = json_decode(file_get_contents($filePath), true);

        if ($programs === null) {
            return ['status' => 'error', 'message' => 'Erro ao ler programas'];
        }

        return [
            'status' => 'success',
            'programs' => $programs
        ];
    }

    private function updateRemainingTime(): void
    {
        if (!$this->isPaused && $this->aquecimentoAtivo) {
            $agora = time();
            $decorrido = $agora - $this->ultimaAtualizacao;
            $this->tempoRestante = max(0, $this->tempoRestante - $decorrido);
            $this->ultimaAtualizacao = $agora;
        }
    }

    private function saveState(): void
    {
        $_SESSION['microwave_state'] = [
            'aquecimentoAtivo' => $this->aquecimentoAtivo,
            'tempoRestante' => $this->tempoRestante,
            'potenciaAtual' => $this->potenciaAtual,
            'isPaused' => $this->isPaused,
            'ultimaAtualizacao' => $this->ultimaAtualizacao
        ];
    }

    private function loadState(): void
    {
        if (isset($_SESSION['microwave_state'])) {
            $state = $_SESSION['microwave_state'];
            $this->aquecimentoAtivo = $state['aquecimentoAtivo'];
            $this->tempoRestante = $state['tempoRestante'];
            $this->potenciaAtual = $state['potenciaAtual'];
            $this->isPaused = $state['isPaused'];
            $this->ultimaAtualizacao = $state['ultimaAtualizacao'];
        }
    }

    private function validateTime(int $time, bool $isPredefined = false): void
    {
        if ($time <= 0) {
            throw new InvalidArgumentException('Tempo deve ser maior que zero');
        }
        if (!$isPredefined && $time > 120) {
            throw new InvalidArgumentException('Tempo deve ser menor que 120');
        }
    }
    private function validatePower(int $power): void
    {
        if ($power < 1 || $power > 10) {
            throw new InvalidArgumentException('Potência deve ser entre 1 e 10');
        }
    }
}
