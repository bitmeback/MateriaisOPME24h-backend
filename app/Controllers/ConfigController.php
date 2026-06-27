<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Middleware\AuthMiddleware;
use MateriaisOpme\App\Services\AuditService;
use MateriaisOpme\App\Support\Csrf;
use MateriaisOpme\App\Support\FileConfig;
use MateriaisOpme\App\Support\View;

final class ConfigController
{
    private AuthMiddleware $auth;
    private AuditService $audit;

    public function __construct()
    {
        $this->auth = new AuthMiddleware();
        $this->audit = new AuditService();
    }

    public function system(): void
    {
        $this->auth->requireRole('admin');
        View::render('config_system', [
            'csrf_token' => Csrf::token(),
            'values' => $this->systemValues(),
            'error' => null,
            'success' => null,
        ]);
    }

    public function saveSystem(): void
    {
        $this->auth->requireRole('admin');
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->renderSystem('CSRF inválido.');
            return;
        }

        $path = dirname(__DIR__, 2) . '/config/generated/production.backend.conf';
        $current = FileConfig::parse($path);
        $updated = $this->mergeSystemConfig($current, $_POST);

        try {
            FileConfig::write($path, $updated);
            $this->audit->record('update', 'config', 'backend', ['path' => $path, 'keys' => array_keys($updated)]);
            $this->renderSystem(null, 'Configurações do sistema salvas com sucesso.');
        } catch (\Throwable $e) {
            $this->renderSystem($e->getMessage());
        }
    }

    public function credentials(): void
    {
        $this->auth->requireRole('admin');
        View::render('config_credentials', [
            'csrf_token' => Csrf::token(),
            'values' => $this->credentialsValues(),
            'error' => null,
            'success' => null,
        ]);
    }

    public function saveCredentials(): void
    {
        $this->auth->requireRole('admin');
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->renderCredentials('CSRF inválido.');
            return;
        }

        $path = dirname(__DIR__, 2) . '/config/generated/production.credentials.conf';
        $current = FileConfig::parse($path);
        $user = trim((string)($_POST['user'] ?? ''));
        $pass = (string)($_POST['pass'] ?? '');

        if ($user === '') {
            $this->renderCredentials('Informe o usuário das credenciais.');
            return;
        }

        $updated = $current;
        $updated['user'] = $user;
        if ($pass !== '') {
            $updated['pass'] = $pass;
        }

        try {
            FileConfig::write($path, $updated);
            $this->audit->record('update', 'config', 'credentials', ['path' => $path, 'keys' => array_keys($updated)]);
            $this->renderCredentials(null, 'Credenciais salvas com sucesso.');
        } catch (\Throwable $e) {
            $this->renderCredentials($e->getMessage());
        }
    }


    private function systemValues(): array
    {
        $config = FileConfig::parse(dirname(__DIR__, 2) . '/config/generated/production.backend.conf');

        return [
            'app_name' => $config['app_name'] ?? 'Materiais Opme Backend',
            'environment' => $config['environment'] ?? 'production',
            'base_url' => $config['base_url'] ?? '',
            'timezone' => $config['timezone'] ?? 'America/Sao_Paulo',
            'session_secret' => '',
            'csrf_secret' => '',
            'compras_credentials_file' => $config['compras_credentials_file'] ?? '/root/.credentials_compras4.conf',
            'log_dir' => $config['log_dir'] ?? '/var/log/materiais_opme_backend',
        ];
    }

    private function credentialsValues(): array
    {
        $config = FileConfig::parse(dirname(__DIR__, 2) . '/config/generated/production.credentials.conf');
        return [
            'user' => $config['user'] ?? '',
            'pass' => '',
        ];
    }


    private function mergeSystemConfig(array $current, array $post): array
    {
        $updated = $current;
        $updated['app_name'] = trim((string)($post['app_name'] ?? $updated['app_name'] ?? 'Materiais Opme Backend'));
        $updated['environment'] = trim((string)($post['environment'] ?? $updated['environment'] ?? 'production'));
        $updated['base_url'] = trim((string)($post['base_url'] ?? $updated['base_url'] ?? ''));
        $updated['timezone'] = trim((string)($post['timezone'] ?? $updated['timezone'] ?? 'America/Sao_Paulo'));

        $sessionSecret = (string)($post['session_secret'] ?? '');
        if ($sessionSecret !== '') {
            $updated['session_secret'] = $sessionSecret;
        }

        $csrfSecret = (string)($post['csrf_secret'] ?? '');
        if ($csrfSecret !== '') {
            $updated['csrf_secret'] = $csrfSecret;
        }

        $updated['compras_credentials_file'] = trim((string)($post['compras_credentials_file'] ?? $updated['compras_credentials_file'] ?? '/root/.credentials_compras4.conf'));
        $updated['log_dir'] = trim((string)($post['log_dir'] ?? $updated['log_dir'] ?? '/var/log/materiais_opme_backend'));

        return $updated;
    }


    private function renderSystem(?string $error = null, ?string $success = null): void
    {
        View::render('config_system', [
            'csrf_token' => Csrf::token(),
            'values' => $this->systemValues(),
            'error' => $error,
            'success' => $success,
        ]);
    }

    private function renderCredentials(?string $error = null, ?string $success = null): void
    {
        View::render('config_credentials', [
            'csrf_token' => Csrf::token(),
            'values' => $this->credentialsValues(),
            'error' => $error,
            'success' => $success,
        ]);
    }

}
