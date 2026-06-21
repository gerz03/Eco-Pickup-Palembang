<?php
function loadEnvFile(string $filePath): void
{
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $pair = explode('=', $trimmed, 2);
        if (count($pair) !== 2) {
            continue;
        }

        $name = trim($pair[0]);
        $value = trim($pair[1]);

        if ($name === '') {
            continue;
        }

        if (getenv($name) === false) {
            putenv("$name=$value");
        }
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
