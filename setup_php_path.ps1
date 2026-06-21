$ErrorActionPreference = "Stop"

$candidatePaths = @(
    "C:\xampp\php",
    "C:\laragon\bin\php",
    "C:\wamp64\bin\php"
)

function Find-PhpPath {
    foreach ($path in $candidatePaths) {
        if (Test-Path $path) {
            $php = Get-ChildItem -Path $path -Filter php.exe -Recurse -ErrorAction SilentlyContinue |
                Select-Object -First 1

            if ($php) {
                return $php.DirectoryName
            }
        }
    }

    $existing = Get-Command php -ErrorAction SilentlyContinue
    if ($existing) {
        return Split-Path -Parent $existing.Source
    }

    return $null
}

$phpPath = Find-PhpPath

if (-not $phpPath) {
    Write-Host "PHP belum ditemukan di komputer ini." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Install salah satu pilihan berikut:"
    Write-Host "1. XAMPP: https://www.apachefriends.org/download.html"
    Write-Host "2. PHP CLI via winget:"
    Write-Host "   winget install PHP.PHP"
    Write-Host ""
    Write-Host "Setelah install, jalankan ulang script ini:"
    Write-Host "   powershell -ExecutionPolicy Bypass -File .\setup_php_path.ps1"
    exit 1
}

$currentUserPath = [Environment]::GetEnvironmentVariable("Path", "User")
$pathParts = $currentUserPath -split ";" | Where-Object { $_ -ne "" }

if ($pathParts -contains $phpPath) {
    Write-Host "PHP sudah ada di PATH user: $phpPath" -ForegroundColor Green
} else {
    $newPath = ($pathParts + $phpPath) -join ";"
    [Environment]::SetEnvironmentVariable("Path", $newPath, "User")
    Write-Host "Berhasil menambahkan PHP ke PATH user: $phpPath" -ForegroundColor Green
    Write-Host "Tutup dan buka ulang terminal/VS Code agar PATH baru terbaca."
}

Write-Host ""
Write-Host "Tes command:"
Write-Host "   php -v"
Write-Host "   php -l auth\login.php"
