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
    private string $programsFile;

    public function __construct()
    {
        $this->programsFile = __DIR__ . '/programs.json';
    }

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

        foreach ($programs as &$program) {
            $program['isDefault'] = ($program['id'] <= 5);
        }

        return [
            'status' => 'success',
            'programs' => $programs
        ];
    }

    public function addProgram(string $name, string $food, int $time, int $power, string $instructions): array
    {
        $this->validateTime($time, true);
        $this->validatePower($power);

        $filePath = __DIR__ . '/programs.json';

        // Carrega os programas existentes
        $programs = [];
        if (file_exists($filePath)) {
            $programs = json_decode(file_get_contents($filePath), true);
            if ($programs === null) {
                $programs = [];
            }
        }

        // Gera um novo ID
        $newId = 1;
        if (!empty($programs)) {
            $maxId = max(array_column($programs, 'id'));
            $newId = $maxId + 1;
        }

        // Cria o novo programa
        $newProgram = [
            'id' => $newId,
            'name' => $name,
            'food' => $food,
            'time' => $time,
            'power' => $power,
            'heatingChar' => $this->generateHeatingChar(),
            'instructions' => $instructions
        ];

        // Adiciona ao array
        $programs[] = $newProgram;

        // Salva no arquivo
        if (file_put_contents($filePath, json_encode($programs, JSON_PRETTY_PRINT))) {
            return [
                'status' => 'success',
                'program' => $newProgram,
                'message' => 'Programa adicionado com sucesso'
            ];
        } else {
            throw new RuntimeException('Falha ao salvar o arquivo de programas');
        }
    }

    public function removeProgram(int $id): array
    {
        // Não permite remover os programas padrão (IDs 1-5)
        if ($id <= 5) {
            throw new InvalidArgumentException('Não é permitido remover programas padrão');
        }

        $filePath = __DIR__ . '/programs.json';

        // Carrega os programas existentes
        $programs = [];
        if (file_exists($filePath)) {
            $programs = json_decode(file_get_contents($filePath), true);
            if ($programs === null) {
                $programs = [];
            }
        }

        // Filtra o programa a ser removido
        $filteredPrograms = array_filter($programs, function ($program) use ($id) {
            return $program['id'] !== $id;
        });

        // Verifica se algum programa foi removido
        if (count($filteredPrograms) === count($programs)) {
            throw new InvalidArgumentException('Programa não encontrado');
        }

        // Salva no arquivo
        if (file_put_contents($filePath, json_encode(array_values($filteredPrograms), JSON_PRETTY_PRINT))) {
            return [
                'status' => 'success',
                'message' => 'Programa removido com sucesso'
            ];
        } else {
            throw new RuntimeException('Falha ao salvar o arquivo de programas');
        }
    }

    private function generateHeatingChar(): string
    {
        $chars = ['*', '#', '@', '&', '%', '$', '!', '?', '~', '^'];
        return $chars[array_rand($chars)];
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
