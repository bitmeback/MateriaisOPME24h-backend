<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Support;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $file = __DIR__ . '/../../resources/views/' . $template . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'Template não encontrado.';
            return;
        }

        extract($data, EXTR_SKIP);
        require $file;
    }
}
