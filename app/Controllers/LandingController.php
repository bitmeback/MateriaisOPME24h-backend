<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Support\Csrf;
use MateriaisOpme\App\Support\View;

final class LandingController
{
    public function index(): void
    {
        View::render('landing', [
            'csrf_token' => Csrf::token(),
            'error' => null,
            'form_action' => '/login',
            'button_label' => 'Entrar',
        ]);
    }
}
