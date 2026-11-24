<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

/** Escapa un valor para imprimirlo de forma segura en HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return Csrf::field();
}

function auth_user(): ?array
{
    return Auth::user();
}

/** Formatea centavos como moneda: 1250000 => "$ 12.500,00". */
function money(int $cents): string
{
    return '$ ' . number_format($cents / 100, 2, ',', '.');
}

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'][] = [$type, $message];
}

/** @return list<array{0:string,1:string}> */
function flash_get(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $messages;
}

/** Construye una URL descartando parámetros vacíos. */
function url(string $path, array $query = []): string
{
    $query = array_filter($query, static fn ($v) => $v !== '' && $v !== null);

    return $path . ($query ? '?' . http_build_query($query) : '');
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key])
        ? '<small class="error">' . e($errors[$key]) . '</small>'
        : '';
}
