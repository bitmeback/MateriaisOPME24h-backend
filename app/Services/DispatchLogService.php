<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Services;

use MateriaisOpme\App\Repositories\EnviosRelatoriosRepository;
use RuntimeException;

final class DispatchLogService
{
    private const LOG_PATH = '/var/log/materiais_opme_envios.log';

    private ?EnviosRelatoriosRepository $repository = null;

    private function repository(): EnviosRelatoriosRepository
    {
        if ($this->repository === null) {
            $this->repository = new EnviosRelatoriosRepository();
        }
        return $this->repository;
    }

    /**
     * Registra um envio de relatório por e-mail no arquivo de log e no banco de dados.
     *
     * @param array  $vendor   Dados do fornecedor (name, cnpj_normalized)
     * @param string $canal    'email'
     * @param string $emails   Lista de e-mails destinatários (separados por vírgula)
     * @param string $emailMsg Conteúdo do e-mail enviado
     */
    public function logEnvio(
        array $vendor,
        string $canal = 'email',
        string $emails = '',
        string $emailMsg = '',
    ): void {
        $dataEnvio = date('Y-m-d H:i:s');
        $emailEnviado = in_array($canal, ['email', 'ambos'], true);

        // 1) Log em arquivo (JSON line)
        $this->appendFileLog([
            'data_envio'          => $dataEnvio,
            'fornecedor_nome'     => $vendor['name'] ?? '',
            'fornecedor_cnpj'     => $vendor['cnpj_normalized'] ?? '',
            'canal'               => $canal,
            'email_enviado'       => $emailEnviado,
            'email_destinatarios' => $emails,
        ]);

        // 2) Log no banco de dados
        try {
            $this->repository()->insert([
                'fornecedor_nome'     => $vendor['name'] ?? '',
                'fornecedor_cnpj'     => $vendor['cnpj_normalized'] ?? '',
                'data_envio'          => $dataEnvio,
                'email_enviado'       => $emailEnviado,
                'email_destinatarios' => $emails,
                'email_conteudo'      => $emailMsg,
            ]);
        } catch (\Throwable $e) {
            error_log('DispatchLogService: falha ao inserir no BD: ' . $e->getMessage());
        }
    }

    private function appendFileLog(array $entry): void
    {
        $dir = dirname(self::LOG_PATH);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log('DispatchLogService: falha ao criar diretório de log: ' . $dir);
            return;
        }

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        $result = @file_put_contents(
            self::LOG_PATH,
            $line . "\n",
            FILE_APPEND | LOCK_EX
        );

        if ($result === false) {
            error_log('DispatchLogService: falha ao escrever em ' . self::LOG_PATH);
        }
    }
}
