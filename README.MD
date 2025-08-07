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
    php -S localhost:8080 -t frontend

    - E acesse http://localhost:8080

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

| Método | Endpoint   | Descrição                      | Autenticação Requerida |
|--------|------------|--------------------------------|------------------------|
| POST   | `/login`   | Autenticação de usuário        | Não                    |
| POST   | `/start`   | Inicia o micro-ondas           | Sim (JWT)              |
| POST   | `/pause`   | Pausa o aquecimento            | Sim (JWT)              |
| POST   | `/resume`  | Retoma o aquecimento           | Sim (JWT)              |
| GET    | `/status`  | Verifica o status atual        | Sim (JWT)              |