<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Services;

use MateriaisOpme\App\Repositories\FornecedoresRepository;

/**
 * Serviço de envio de e-mails via Node.js (materiais_opme_24h_send_email_db.js).
 *
 * Lê fornecedores e itens do banco de dados, gera os matches.json
 * e dispara o envio via SMTP usando o script Node.js.
 */
final class EmailDispatchService
{
    private string $scriptPath;
    private string $logDir;
    private bool $isTest;
    private string $dbName;

    public function __construct()
    {
        $this->scriptPath = '/root/materiais-opme-24h-git/scripts';
        $this->logDir = '/var/log';
        $this->isTest = (getenv('OPME_ENV') === 'homologacao');
        $this->dbName = $this->isTest ? 'materiais_opme_teste' : 'materiais_opme';
    }

    /**
     * Executa o fluxo completo: parse → match → send (dry-run ou force).
     *
     * @param string $mode 'dry-run' para validação, 'force' para envio real
     * @return array{exit_code: int, output: string, log_file: string}
     */
    public function runPipeline(string $mode = 'dry-run'): array
    {
        $parseScript = $this->scriptPath . '/opme_parse_from_db.js';
        $matchScript = $this->scriptPath . '/opme_match_from_db.js';
        $sendScript  = $this->scriptPath . '/materiais_opme_24h_send_email_db.js';

        $envFlag = $this->isTest ? '--test' : '';
        $dryRunTestFlag = '--dry-run-test';
        $dryRunFlag = ($mode === 'dry-run') ? '--dry-run' : '';
        $forceFlag = ($mode === 'force') ? '--force' : '';

        $output = '';
        $exitCode = 0;

        // Step 1: Parse
        $cmd = "node {$parseScript} {$envFlag} {$dryRunTestFlag} 2>&1";
        exec($cmd, $out, $code);
        $output .= "[parse] exit={$code}\n" . implode("\n", $out) . "\n";
        if ($code !== 0) {
            return ['exit_code' => $code, 'output' => $output, 'log_file' => ''];
        }

        // Step 2: Match
        $cmd = "node {$matchScript} {$envFlag} {$dryRunTestFlag} 2>&1";
        exec($cmd, $out, $code);
        $output .= "[match] exit={$code}\n" . implode("\n", $out) . "\n";
        if ($code !== 0) {
            return ['exit_code' => $code, 'output' => $output, 'log_file' => ''];
        }

        // Step 3: Send
        $cmd = "node {$sendScript} {$envFlag} {$dryRunTestFlag} {$dryRunFlag} {$forceFlag} 2>&1";
        exec($cmd, $out, $code);
        $output .= "[send] exit={$code}\n" . implode("\n", $out) . "\n";

        // Extrair log_file do output
        $logFile = '';
        foreach ($out as $line) {
            if (preg_match('/Log:\s+(.+)/', $line, $m)) {
                $logFile = trim($m[1]);
                break;
            }
        }

        return ['exit_code' => $code, 'output' => $output, 'log_file' => $logFile];
    }

    /**
     * Envia e-mail para um fornecedor específico (usado pelo botão "Enviar pendências").
     * Executa o pipeline completo em modo dry-run-test + force.
     *
     * @return array{success: bool, message: string, log_file: string}
     */
    public function sendPending(array $vendor): array
    {
        $result = $this->runPipeline('force');

        if ($result['exit_code'] === 0) {
            return [
                'success' => true,
                'message' => 'E-mails enviados com sucesso.',
                'log_file' => $result['log_file'],
            ];
        }

        return [
            'success' => false,
            'message' => 'Erro no envio. Verifique o log: ' . $result['log_file'],
            'log_file' => $result['log_file'],
        ];
    }
}
