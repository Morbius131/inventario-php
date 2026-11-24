<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * Renderiza una vista dentro del layout principal.
     *
     * Los parámetros llevan prefijo "__" para que no choquen con las variables
     * que se extraen para la vista (p. ej. una variable llamada $data).
     */
    public static function render(string $__view, array $__vars = []): void
    {
        extract($__vars, EXTR_SKIP);

        ob_start();
        require dirname(__DIR__) . '/views/' . $__view . '.php';
        $content = ob_get_clean();

        require dirname(__DIR__) . '/views/layout.php';
    }

    public static function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }
}
