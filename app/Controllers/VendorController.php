<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Services\AuditService;
use MateriaisOpme\App\Services\DispatchLogService;
use MateriaisOpme\App\Services\EmailDispatchService;
use MateriaisOpme\App\Services\VendorService;
use MateriaisOpme\App\Support\Cnpj;
use MateriaisOpme\App\Support\Csrf;
use MateriaisOpme\App\Support\View;

final class VendorController
{
    private AuditService $audit;
    private DispatchLogService $dispatchLog;

    public function __construct()
    {
        $this->audit = new AuditService();
        $this->dispatchLog = new DispatchLogService();
    }

    public function index(): void
    {
        $service = new VendorService();
        $query = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        if ($status === '' || strcasecmp($status, 'all') === 0) {
            $status = '';
        }
        $sort = trim((string)($_GET['sort'] ?? 'cnpj_asc'));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 10)));

        $allVendors = $service->all();
        $statusOptions = array_values(array_unique(array_filter(array_map(static fn(array $vendor): string => trim((string)($vendor['status'] ?? '')), $allVendors), static fn(string $status): bool => $status !== '')));
        sort($statusOptions, SORT_NATURAL | SORT_FLAG_CASE);

        $result = $service->list([
            'query' => $query,
            'status' => $status,
            'sort' => $sort,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $filteredAll = $service->list([
            'query' => $query,
            'status' => $status,
            'sort' => $sort,
            'page' => 1,
            'per_page' => 0,
        ]);

        $filteredItems = $filteredAll['items'] ?? [];
        $stats = [
            'total' => (int)($filteredAll['total'] ?? 0),
            'send_enabled' => count(array_filter($filteredItems, static fn(array $vendor): bool => !empty($vendor['send_report']))),
            'with_pending' => count(array_filter($filteredItems, static fn(array $vendor): bool => $service->displayPendingCount($vendor) > 0)),
            'ready_to_send' => count(array_filter($filteredItems, static fn(array $vendor): bool => $service->canSendPending($vendor))),
        ];

        $vendors = array_map(
            static fn(array $vendor): array => $vendor + [
                'can_send_pending' => $service->canSendPending($vendor),
                'pending_display_count' => $service->displayPendingCount($vendor),
            ],
            $result['items']
        );

        $success = (string)($_SESSION['flash_success'] ?? '');
        $error = (string)($_SESSION['flash_error'] ?? '');
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        View::render('fornecedores', [
            'vendors' => $vendors,
            'csrf_token' => Csrf::token(),
            'query' => $query,
            'status' => $status,
            'sort' => $sort,
            'pagination' => $result,
            'status_options' => $statusOptions,
            'result_count' => $result['total'],
            'stats' => $stats,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function create(): void
    {
        $service = new VendorService();
        View::render('fornecedor_form', [
            'vendor' => $this->blankVendor(),
            'csrf_token' => Csrf::token(),
            'action' => '/fornecedores/novo',
            'error' => null,
            'warning' => null,
            'existing_vendors' => array_values(array_filter(array_map(static fn(array $item): array => [
                'cnpj' => (string)($item['cnpj'] ?? ''),
                'name' => (string)($item['name'] ?? ''),
            ], $service->all()), static fn(array $item): bool => ($item['cnpj'] ?? '') !== '')),
        ]);
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->renderForm([], '/fornecedores/novo', 'CSRF inválido.');
            return;
        }

        $service = new VendorService();
        $vendor = $this->fromPost();
        [$error, $warning] = $this->validateVendorInput($vendor);
        if (!empty($error)) {
            $this->renderForm($vendor, '/fornecedores/novo', $error, null);
            return;
        }

        if (!empty($warning)) {
            $this->renderForm($vendor, '/fornecedores/novo', null, $warning);
            return;
        }

        if (($duplicate = $service->findByCnpj((string)($vendor['cnpj'] ?? ''))) !== null) {
            $duplicateName = trim((string)($duplicate['name'] ?? ''));
            $suffix = $duplicateName !== '' ? ' (' . $duplicateName . ')' : '';
            $this->renderForm($vendor, '/fornecedores/novo', 'Já existe um fornecedor com esse CNPJ.' . $suffix);
            return;
        }

        try {
            $service->upsert($vendor);
            $this->audit->record('create', 'vendor', $vendor['cnpj'], [
                'cnpj_formatted' => $vendor['cnpj_formatted'],
                'send_report' => $vendor['send_report'],
                'status' => $vendor['status'],
                'pendencias' => $vendor['pendencias'],
            ]);
            header('Location: /fornecedores');
            exit;
        } catch (\Throwable $e) {
            $this->renderForm($vendor, '/fornecedores/novo', $e->getMessage());
        }
    }

    public function edit(string $id = ''): void
    {
        $service = new VendorService();
        $vendor = $service->findByCnpj(Cnpj::normalize($id));
        if ($vendor === null) {
            http_response_code(404);
            echo 'Fornecedor não encontrado.';
            return;
        }

        View::render('fornecedor_form', [
            'vendor' => $vendor + [
                'pending_display_count' => $service->displayPendingCount($vendor),
            ],
            'csrf_token' => Csrf::token(),
            'action' => '/fornecedores/editar/' . urlencode((string)$vendor['cnpj']),
            'error' => null,
            'warning' => null,
            'existing_vendors' => array_values(array_filter(array_map(static fn(array $item): array => [
                'cnpj' => (string)($item['cnpj'] ?? ''),
                'name' => (string)($item['name'] ?? ''),
            ], $service->all()), static fn(array $item): bool => ($item['cnpj'] ?? '') !== '' && ($item['cnpj'] ?? '') !== (string)($vendor['cnpj'] ?? ''))),
        ]);
    }

    public function update(string $id = ''): void
    {
        $oldId = Cnpj::normalize($id);
        $service = new VendorService();
        $current = $oldId !== '' ? $service->findByCnpj($oldId) : null;

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->renderForm($this->fromPost(), '/fornecedores/editar/' . urlencode($oldId), 'CSRF inválido.');
            return;
        }

        $vendor = $this->fromPost();
        [$error, $warning] = $this->validateVendorInput($vendor);
        if (!empty($error)) {
            $this->renderForm($vendor, '/fornecedores/editar/' . urlencode($oldId), $error, null);
            return;
        }

        if (!empty($warning)) {
            $this->renderForm($vendor, '/fornecedores/editar/' . urlencode($oldId), null, $warning);
            return;
        }

        $newId = $vendor['cnpj'];
        if ($newId === '') {
            $this->renderForm($vendor, '/fornecedores/editar/' . urlencode($oldId), 'CNPJ inválido.');
            return;
        }

        $duplicate = $service->findByCnpj($newId);
        if ($duplicate !== null && $newId !== $oldId) {
            $duplicateName = trim((string)($duplicate['name'] ?? ''));
            $suffix = $duplicateName !== '' ? ' (' . $duplicateName . ')' : '';
            $this->renderForm($vendor, '/fornecedores/editar/' . urlencode($oldId), 'Já existe um fornecedor com esse CNPJ.' . $suffix);
            return;
        }

        $currentPending = 0;
        if (is_array($current)) {
            $currentPending = max(
                (int)($current['pendencias'] ?? 0),
                $service->displayPendingCount($current)
            );
        }
        if ((int)($vendor['pendencias'] ?? 0) <= 0 && $currentPending > 0) {
            $vendor['pendencias'] = $currentPending;
        }

        try {
            $vendor['previous_cnpj'] = $oldId;
            $service->upsert($vendor);
            $this->audit->record('update', 'vendor', $newId, [
                'previous_cnpj' => $oldId,
                'cnpj_formatted' => $vendor['cnpj_formatted'],
                'send_report' => $vendor['send_report'],
                'status' => $vendor['status'],
                'pendencias' => $vendor['pendencias'],
            ]);
            header('Location: /fornecedores');
            exit;
        } catch (\Throwable $e) {
            $this->renderForm($vendor, '/fornecedores/editar/' . urlencode($newId), $e->getMessage());
        }
    }

    public function delete(string $id = ''): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido.';
            return;
        }

        $normalized = Cnpj::normalize($id);
        (new VendorService())->delete($normalized);
        $this->audit->record('delete', 'vendor', $normalized);
        header('Location: /fornecedores');
        exit;
    }

    public function sendPending(string $id = ''): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido.';
            return;
        }

        $service = new VendorService();
        $normalized = Cnpj::normalize($id);
        $vendor = $service->findByCnpj($normalized);
        if ($vendor === null) {
            http_response_code(404);
            echo 'Fornecedor não encontrado.';
            return;
        }

        if (!$service->canSendPending($vendor)) {
            http_response_code(400);
            echo 'Fornecedor sem condições para enviar pendências.';
            return;
        }

        try {
            $result = $this->dispatchPendingVendor($service, $vendor);
            $this->audit->record('send_pending', 'vendor', $normalized, [
                'pendencias_anteriores' => $result['pendencias_anteriores'],
                'pendencias_restantes' => $result['pendencias_restantes'],
                'success' => $result['success'],
                'contacts' => $result['contacts'],
                'log_file' => $result['log_file'],
            ]);

            // Registrar no histórico de envios
            $contacts = $result['contacts'] ?? '';
            $this->dispatchLog->logEnvio(
                vendor: $vendor,
                canal: 'email',
                emails: $contacts,
                emailMsg: $result['message'] ?? '',
            );

            if ($result['success']) {
                $_SESSION['flash_success'] = 'Pendências enviadas via e-mail para: ' . $contacts;
            } else {
                $_SESSION['flash_error'] = $result['message'];
            }
            header('Location: /fornecedores');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /fornecedores');
            exit;
        }
    }

    public function reprocessPending(): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido.';
            return;
        }

        $service = new VendorService();
        $eligible = array_values(array_filter(
            $service->all(),
            static fn(array $vendor): bool => $service->canSendPending($vendor)
        ));

        if ($eligible === []) {
            $_SESSION['flash_error'] = 'Nenhum fornecedor elegível encontrado para reprocessar.';
            header('Location: /fornecedores');
            exit;
        }

        $dispatchService = new EmailDispatchService();
        $sentVendors = 0;
        $sentContacts = 0;
        $details = [];

        try {
            foreach ($eligible as $vendor) {
                $result = $this->dispatchPendingVendor($service, $vendor, $dispatchService);
                $sentVendors++;
                $contactsList = array_filter(array_map('trim', explode(',', $result['contacts'] ?? '')));
                $sentContacts += count($contactsList);
                $details[] = [
                    'cnpj' => $vendor['cnpj'] ?? '',
                    'name' => (string)($vendor['name'] ?? ''),
                    'contacts' => $result['contacts'],
                    'success' => $result['success'],
                    'pendencias_anteriores' => $result['pendencias_anteriores'],
                    'pendencias_restantes' => $result['pendencias_restantes'],
                ];
            }

            $this->audit->record('reprocess_pending', 'vendor', 'batch', [
                'vendors_eligible' => count($eligible),
                'vendors_sent' => $sentVendors,
                'contacts_sent' => $sentContacts,
                'details' => $details,
            ]);

            // Registrar no histórico de envios (um registro por fornecedor)
            foreach ($details as $detail) {
                $this->dispatchLog->logEnvio(
                    vendor: ['name' => $detail['name'], 'cnpj' => $detail['cnpj']],
                    canal: 'email',
                    emails: $detail['contacts'] ?? '',
                    emailMsg: '',
                );
            }

            $_SESSION['flash_success'] = 'Reprocessamento concluído: ' . $sentVendors . ' fornecedor(es) e ' . $sentContacts . ' contato(s) enviados.';
            header('Location: /fornecedores');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /fornecedores');
            exit;
        }
    }

    private function dispatchPendingVendor(VendorService $service, array $vendor, ?EmailDispatchService $dispatchService = null): array
    {
        $dispatchService ??= new EmailDispatchService();
        $normalized = Cnpj::normalize((string)($vendor['cnpj'] ?? ''));
        if ($normalized === '') {
            throw new \RuntimeException('CNPJ inválido para envio.');
        }

        $result = $dispatchService->sendPending($vendor);

        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'log_file' => $result['log_file'] ?? '',
            'contacts' => $vendor['contacts'] ?? '',
            'pendencias_anteriores' => (int)($vendor['pendencias'] ?? 0),
            'pendencias_restantes' => (int)($vendor['pendencias'] ?? 0),
        ];
    }

    private function fromPost(): array
    {
        return [
            'cnpj' => Cnpj::normalize((string)($_POST['cnpj'] ?? '')),
            'cnpj_formatted' => Cnpj::format((string)($_POST['cnpj'] ?? '')),
            'send_report' => filter_var($_POST['send_report'] ?? '0', FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false,
            'status' => trim((string)($_POST['status'] ?? '')),
            'contacts' => self::normalizeContactsString((string)($_POST['contacts'] ?? '')),
            'name' => trim((string)($_POST['name'] ?? '')),
            'notes' => trim((string)($_POST['notes'] ?? '')),
            'pendencias' => max(0, (int)($_POST['pendencias'] ?? 0)),
        ];
    }

    private static function normalizeContactsString(string $contacts): string
    {
        $parts = array_map('trim', explode(',', $contacts));
        $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));

        return implode(', ', $parts);
    }

    private function renderForm(array $vendor, string $action, ?string $error = null, ?string $warning = null): void
    {
        $service = new VendorService();
        $currentId = Cnpj::normalize((string)($vendor['cnpj'] ?? ''));
        $existingCnpjs = array_values(array_filter(array_map(
            static fn(array $item): string => (string)($item['cnpj'] ?? ''),
            $service->all()
        ), static fn(string $cnpj): bool => $cnpj !== '' && $cnpj !== $currentId));

        View::render('fornecedor_form', [
            'vendor' => $vendor,
            'csrf_token' => Csrf::token(),
            'action' => $action,
            'error' => $error,
            'warning' => $warning,
            'existing_cnpjs' => $existingCnpjs,
        ]);
    }

    private function blankVendor(): array
    {
        return [
            'cnpj' => '',
            'cnpj_formatted' => '',
            'send_report' => false,
            'status' => '',
            'contacts' => '',
            'name' => '',
            'notes' => '',
            'pendencias' => 0,
        ];
    }

    private function validateVendorInput(array $vendor): array
    {
        $service = new VendorService();
        $cnpj = trim((string)($vendor['cnpj'] ?? ''));
        if ($cnpj === '') {
            return ['CNPJ é obrigatório.', null];
        }

        $name = trim((string)($vendor['name'] ?? ''));
        if ($name === '') {
            return ['Nome é obrigatório.', null];
        }

        $sendReport = !empty($vendor['send_report']);
        $contacts = trim((string)($vendor['contacts'] ?? ''));
        if ($sendReport && $contacts === '') {
            return [null, '<strong>Não é possível cadastrar como ativado se não houver pelo menos 1 contato</strong>'];
        }

        if ($contacts !== '' && $service->normalizeContacts($contacts) === []) {
            return ['Contato inválido. Use telefones (55DDDxxxxxxxx) ou e-mails válidos, separados por vírgula.', null];
        }

        return [null, null];
    }
}
