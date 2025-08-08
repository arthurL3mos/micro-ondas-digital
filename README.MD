# Micro-ondas Digital

Projeto de simulação de um micro-ondas digital utilizando PHP com Slim Framework para o backend e HTML/CSS/JavaScript para o frontend.

## Pré-requisitos

- PHP 8.0 ou superior
- Composer
- Servidor web (WAMP, XAMPP, LAMP ou servidor PHP embutido)

## Como executar o Projrto

1. **Cloneo o repositório**
    ```bash
    git clone https://github.com/arthurL3mos/microondas-digital.git
    cd microondas-digital

2. **Instale as depedências**
    ```bash
    composer install

3. **Inicie o servidor backend**
    ```bash
    php -S localhost:8000 

4. **Para acessar o frontend**

    - Abra o arquivo fronted/index.html diretamente no navegador
    - Ou inicie um servidor para o frontend:
    ```bash
    php -S localhost:8000

    - E acesse http://localhost:8000/public

## Configuração de Ambiente
1. Copie o arquivo `.env.example` para `.env`
2. Edite as variáveis conforme seu ambiente:
   - `APP_URL`: URL base da aplicação
   - `API_BASE_PATH`: Caminho para a API

## Variáveis de Ambiente Disponíveis

- `APP_ENV`: Ambiente (development, production)
- `APP_URL`: URL base da aplicação
- `API_BASE_PATH`: Caminho para a API

## Credenciais Padrão

O sistema vem com dois usuários pré-configurados:

| Usuário | Senha    | Função           |
|---------|----------|------------------|
| admin   | senha123 | Administrador    |
| user    | user123  | Usuário comum    |


## 🔄 Rotas da API

| Método | Endpoint         | Descrição                             | Autenticação | Body Request Example              |
|--------|------------------|--------------------------------       |--------------|-----------------------------------|
| POST   | `/login`         | Autenticação de usuário               | Não          | `{"username":"admin","password":"senha123"}` |
| POST   | `/start`         | Inicia o micro-ondas                  | Sim          | `{"time": 30, "power": 8}`        |
| POST   | `/pause`         | Pausa o aquecimento                   | Sim          | -                                  |
| POST   | `/resume`        | Retoma o aquecimento                  | Sim          | -                                  |
| GET    | `/status`        | Verifica o status atual               | Sim          | -                                  |
| GET    | `/programs`      | Lista todos os programas cadastrados  | Sim          | -                                  |
| POST   | `/programs/add`  | permite cadastrar novos programas     | Sim          | -                                  |
| DELETE | `/programs/delete`| permite excluir um programa pelo id  | Sim          | -                                  |

**Exemplo de Response (200 OK):**
```json
{
  "status": "running",
  "time": 25,
  "power": 8,
  "isPaused": false
}
```

🧩 Estrutura do Projeto
```text
microondas-digital/
├── public/              # Frontend e entrada pública
│   ├── css/
│   ├── js/
│   ├── index.html
│   └── panel.html
├── src/                 # Código fonte PHP
│   ├── Microwave/
│   ├── Auth/
│   └── ...
├── tests/               # Testes automatizados
│   ├── MicrowaveTest.php
│   └── ApiTest.php
├── vendor/              # Dependências do Composer
├── .env.example         # Modelo de variáveis de ambiente
├── composer.json        # Configuração do PHP
└── README.md            # Este arquivo
```