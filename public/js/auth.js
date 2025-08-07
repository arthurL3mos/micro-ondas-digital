document.addEventListener('DOMContentLoaded', function() {
    const API_BASE_URL = 'http://localhost:8000';
    const loginForm = document.getElementById('login-form');
    const messageDiv = document.getElementById('login-message');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        try {
            const response = await axios.post(`${API_BASE_URL}/login`, {
                username: username,
                password: password
            });

            if (response.data.success) {
                // Armazena o token no localStorage
                localStorage.setItem('jwt_token', response.data.token);
                
                // Redireciona para o painel
                window.location.href = 'panel.html';
            } else {
                showMessage('Credenciais inválidas', 'error');
            }
        } catch (error) {
            console.error('Erro no login:', error);
            showMessage(error.response?.data?.message || 'Erro ao fazer login', 'error');
        }
    });

    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = 'message ' + type;
        setTimeout(() => {
            messageDiv.textContent = '';
            messageDiv.className = 'message';
        }, 5000);
    }

    // Verifica se já está logado (token existe)
    if (localStorage.getItem('jwt_token') && window.location.pathname.endsWith('index.html')) {
        window.location.href = 'panel.html';
    }
});