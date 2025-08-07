document.addEventListener('DOMContentLoaded', function () {

    // No início do arquivo, verifique a autenticação
    if (!localStorage.getItem('jwt_token')) {
        window.location.href = 'index.html';
    }

    // Adicione no final do arquivo:
    document.getElementById('logout-btn')?.addEventListener('click', function () {
        localStorage.removeItem('jwt_token');
        window.location.href = 'index.html';
    });

    // Configure o Axios para enviar o token em todas as requisições
    axios.interceptors.request.use(config => {
        const token = localStorage.getItem('jwt_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    }, error => {
        return Promise.reject(error);
    });

    // Interceptar respostas 401 (não autorizado)
    axios.interceptors.response.use(response => response, error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('jwt_token');
            window.location.href = 'index.html';
        }
        return Promise.reject(error);
    });


    // Elementos da interface
    const timeInput = document.getElementById('time-input');
    const powerInput = document.getElementById('power-input');
    const timeDisplay = document.getElementById('time-display');
    const powerDisplay = document.getElementById('power-display');
    const startBtn = document.getElementById('start-btn');
    const quickStartBtn = document.getElementById('quick-start-btn');
    const pauseCancelBtn = document.getElementById('pause-cancel-btn');
    const keys = document.querySelectorAll('.key');
    const messageDisplay = document.getElementById('message-display');

    const API_BASE_URL = 'http://localhost:8000';
    let statusInterval;
    let countdownInterval;
    let isRunning = false;

    init();

    function init() {
        setupEventListeners();
        checkInitialStatus();
    }

    function handleKeyPress() {
        if (!document.activeElement.matches('#time-input, #power-input')) {
            timeInput.focus();
        }
        const activeInput = document.activeElement;

        if (activeInput === timeInput) {
            timeInput.value += this.textContent;
            if (parseInt(timeInput.value) > 120) {
                timeInput.value = '120';
            }
        } else if (activeInput === powerInput) {
            powerInput.value = this.textContent;
        }
    }

    function setupEventListeners() {
        // Teclado numérico
        keys.forEach(key => {
            key.addEventListener('click', handleKeyPress);
        });

        // Botão iniciar
        startBtn.addEventListener('click', startHeating);

        // Botão início rápido
        quickStartBtn.addEventListener('click', quickStart);

        // Botão pausar/cancelar
        pauseCancelBtn.addEventListener('click', togglePause);
    }

    async function checkInitialStatus() {
        try {
            const response = await axios.get(`${API_BASE_URL}/status`);
            if (response.data.status === 'running') {
                isRunning = true;
                startStatusUpdates();
                updateDisplay(response.data.time, response.data.power);
            }
        } catch (error) {
            console.error('Erro ao verificar status inicial:', error);
        }
    }

    async function startHeating() {
        const time = parseInt(timeInput.value) || 0;
        const power = parseInt(powerInput.value) || 10;

        try {
            const response = await axios.post(`${API_BASE_URL}/start`, {
                time: time,
                power: power
            });

            if (response.data.status === 'success') {
                isRunning = true;
                pauseCancelBtn.textContent = 'Pausar';
                startStatusUpdates();
                updateDisplay(response.data.time, response.data.power);
            } else {
                showMessage(response.data.message || 'Erro ao iniciar aquecimento');
            }
        } catch (error) {
            console.error('Erro:', error);
            showMessage(error.response?.data?.message || 'Falha na comunicação');
        }
    }

    function quickStart() {
        const currentTime = parseInt(timeInput.value) || 0;
        const newTime = Math.min(currentTime + 30, 120);
        timeInput.value = newTime;
        powerInput.value = '10';

        startHeating();
    }

    async function togglePause() {
        try {
            const endpoint = isRunning ? '/pause' : '/resume';
            const response = await axios.post(`${API_BASE_URL}${endpoint}`);

            if (response.data.status === 'success') {
                pauseCancelBtn.textContent = !isRunning ? 'Pausar' : 'Cancelar';
                if (isRunning === true) {
                    startStatusUpdates();
                } else {
                    resetDisplay();
                }
            }
        } catch (error) {
            console.error('Erro ao pausar/retomar:', error);
            showMessage(error.response?.data?.message || 'Erro na operação');
        }
    }

    function resetDisplay() {
        console.log('chamou a reset display');
        timeDisplay.textContent = '00:00';
        powerDisplay.textContent = 'Potência: 10';
        timeInput.value = '';
        powerInput.value = '10';
        pauseCancelBtn.textContent = 'Cancelar'; // Mantém o texto como Cancelar
    }

    function startStatusUpdates() {
        clearStatusInterval(); // Limpa qualquer intervalo existente

        // Primeira verificação imediata
        checkStatusAndUpdate().then(shouldContinue => {
            if (shouldContinue) {
                statusInterval = setInterval(async () => {
                    await checkStatusAndUpdate();
                }, 1000);
            }
        });
    }

    async function checkStatusAndUpdate() {
        try {
            const response = await axios.get(`${API_BASE_URL}/status`);

            // Verifique primeiro se está pausado
            if (response.data.isPaused) {
                clearStatusInterval();
                isRunning = false;
                updateDisplay(response.data.time, response.data.power);
                showMessage('Aquecimento Pausado!');
                return false;
            }

            // Depois verifique se ainda tem tempo
            if (response.data.time > 0) {
                updateDisplay(response.data.time, response.data.power);
                return true; // Continua verificando
            }
            else {
                clearStatusInterval();
                isRunning = false;
                resetDisplay();
                showMessage('Aquecimento concluído!');
                return false;
            }
        } catch (error) {
            console.error('Erro ao verificar status:', error);
            clearStatusInterval();
            showMessage('Erro ao verificar status');
            return false;
        }
    }

    // limpar tempo do micro-ondas
    function clearStatusInterval() {
        if (statusInterval) {
            clearInterval(statusInterval);
            statusInterval = null;
        }
    }

    // atualização do display do micro-ondas
    function updateDisplay(time, power) {
        const minutes = Math.floor(time / 60);
        const seconds = time % 60;
        timeDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        powerDisplay.textContent = `Potência: ${power}`;

        timeInput.value = time;
        powerInput.value = power;
    }

    // Exibição de mensagens no painel do micro-ondas
    function showMessage(message) {
        messageDisplay.textContent = message;
        setTimeout(() => messageDisplay.textContent = '', 5000);
    }
});