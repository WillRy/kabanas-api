# 🏕️ Kabanas

Kabanas é uma plataforma completa para gestão de aluguel de cabanas e casas por temporada, composta por uma API backend em Laravel e um frontend SPA em React.
A aplicação oferece autenticação segura, controle de usuários, gestão de imóveis, reservas, e interface moderna e responsiva.

## ⚙️ Backend — Laravel API

### 🧱 Stack

- Laravel 12+

PHP 8.4+

MySQL 8+

MySQL (para filas e cache)

Laravel Sanctum + JWT (autenticação híbrida)

Mail Queues (envio assíncrono de e-mails)

PHPUnit (testes automatizados)

### 🔐 Autenticação

O backend utiliza uma combinação de:

- Sanctum para autenticação de sessões SPA;
- JWT + Refresh Tokens para acessos via dispositivos móveis ou externos.

### 🧪 Testes (100% de cobertura)

Cobertura completa de:

Models: Validação de relacionamentos, casts e mutators.

Controllers: Fluxos de autenticação, criação e listagem de recursos.

Endpoints: Testes de integração cobrindo rotas REST principais.

```shell
#rodar os testes
php artisan test

#gerar relatorio de cobertura (vai gerar um index.html dentro da pasta coverage)
php artisan test --coverage-html coverage
```

### 📨 Filas de E-mail

As notificações e confirmações (como reservas ou redefinição de senha) são processadas de forma assíncrona via Redis Queue para melhor performance.

## 💻 Frontend — React SPA
### 🧱 Stack

- React 18+
- Vite
- React Router v7
- TanStack Query (React Query) — gerenciamento de estado de dados assíncronos
- Tailwind CSS — estilização moderna e responsiva


### ⚡ Funcionalidades

- Autenticação
- Listagem e gerenciamento de cabanas
- Listagem e gerenciamento de reservas
- Gerenciamento de check-in/check-out
- Dashboards:
    - Atividades do dia
    - Reservas por período
    - Vendas por período
    - Check-Ins por período
    - Taxa de ocupação
    - Gráfico de evolução de vendas/reservas
- Tabelas dinâmicas com paginação e ordenação server-side
- Prefetching de dados (API-side) para navegação fluida
- Feedback visual otimizado (loading, error boundaries)


## Instalação

### BackEnd

```shell
git clone https://github.com/WillRy/kabanas-api.git

cd kabanas-api

docker compose up -d
```

### FrontEnd
```shell
git clone https://github.com/WillRy/kabanas-spa.git

cd kabanas-spa

npm i

npm run dev
```
