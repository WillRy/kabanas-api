<?php

namespace App\AuthGuard;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class JwtGuard implements Guard
{
    use \Illuminate\Auth\GuardHelpers;

    protected $request;

    protected $ttl = 3600;

    protected $claims = [];

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    public function setTTL($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function claims(array $claims)
    {
        $this->claims = $claims;

        return $this;
    }

    public function attempt(array $credentials = [])
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            $this->setUser($user);

            $payload = array_merge($this->claims, [
                'iss' => url('/'), // Emissor do token
                'sub' => $user->getAuthIdentifier(), // Assunto do token (ID do usuário)
                'iat' => time(), // Hora em que o token foi emitido
                'exp' => time() + $this->ttl, // Hora de expiração do token
            ]);

            return JWT::encode($payload, config('app.key'), 'HS256');
        }

        return false;
    }

    public function tokenById($id)
    {
        $user = $this->provider->retrieveById($id);

        if ($user) {
            $this->setUser($user);

            $payload = array_merge($this->claims, [
                'iss' => url('/'), // Emissor do token
                'sub' => $user->getAuthIdentifier(), // Assunto do token (ID do usuário)
                'iat' => time(), // Hora em que o token foi emitido
                'exp' => time() + $this->ttl, // Hora de expiração do token
            ]);

            return JWT::encode($payload, config('app.key'), 'HS256');
        }

        return false;
    }

    public function logout()
    {
        // Para JWT, não há estado de sessão a ser destruído.
        // O token simplesmente não será mais válido após sua expiração.
        $this->user = null;

        return true;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $token = $this->request->bearerToken();

        if (! $token) {
            $token = $this->request->cookie('token');
        }

        if (empty($token)) {
            return null;
        }

        try {
            // A chave secreta deve ser a mesma usada para gerar o token.
            // É recomendado armazená-la em seu arquivo .env.
            $credentials = JWT::decode($token, new Key(config('app.key'), 'HS256'));
        } catch (Exception $e) {
            // Token inválido (expirado, assinatura incorreta, etc.)
            return null;
        }

        // 'sub' (subject) é o padrão para o ID do usuário no payload do JWT.
        if (empty($credentials->sub)) {
            return null;
        }

        if ($credentials->session_id) {
            $valido = (new \App\Service\JwtService)->isLogged($token);
            if (! $valido) {
                return null;
            }
        }

        return $this->user = $this->provider->retrieveById($credentials->sub);
    }

    /**
     * Validate a user's credentials.
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        // Para uma guarda stateless como JWT, a validação principal
        // ocorre no método user(). Este método pode não ser necessário
        // ou pode ser adaptado conforme a necessidade.
        if ($this->user()) {
            return true;
        }

        return false;
    }
}
