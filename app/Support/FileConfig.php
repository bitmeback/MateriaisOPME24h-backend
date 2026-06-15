<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Support;

final class FileConfig
{
    public static function parse(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $config = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }

            $parts = explode(':', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $config[$key] = $value;
        }

        return $config;
    }

    public static function stringify(array $config): string
    {
        $lines = [];
        foreach ($config as $key => $value) {
            if (!is_scalar($key)) {
                continue;
            }

            $normalizedKey = trim((string)$key);
            if ($normalizedKey === '') {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) {
                $value = '';
            }

            $lines[] = $normalizedKey . ':' . trim((string)$value);
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    public static function write(string $path, array $config): void
    {
        $dir = dirname($path);
        $content = self::stringify($config);

        if (is_file($path) && is_writable($path)) {
            if (file_put_contents($path, $content, LOCK_EX) === false) {
                throw new \RuntimeException('Falha ao gravar configuração em ' . $path . '. Verifique permissões.');
            }

            @chmod($path, 0660);
            return;
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \RuntimeException('Arquivo de configuração sem permissão de escrita em ' . $path);
        }

        $tmp = tempnam($dir, 'cfg_');
        if ($tmp === false) {
            throw new \RuntimeException('Não foi possível criar arquivo temporário.');
        }

        if (file_put_contents($tmp, $content, LOCK_EX) === false) {
            @unlink($tmp);
            throw new \RuntimeException('Falha ao gravar configuração temporária.');
        }

        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('Falha ao substituir arquivo de configuração.');
        }

        @chmod($path, 0660);
    }
}
