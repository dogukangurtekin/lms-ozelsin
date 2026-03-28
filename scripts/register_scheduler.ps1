$taskName = "OzelsinLmsScheduler"
$projectRoot = "C:\xampp\htdocs\lms-ozelsin"
$runner = Join-Path $projectRoot "scripts\run_scheduler.bat"

if (-not (Test-Path $runner)) {
    throw "Scheduler calistirici bulunamadi: $runner"
}

$escapedRunner = $runner.Replace('"', '\"')
$createArgs = @(
    '/Create',
    '/F',
    '/SC', 'MINUTE',
    '/MO', '1',
    '/TN', $taskName,
    '/TR', "cmd.exe /c `"$escapedRunner`""
)

$result = & schtasks.exe @createArgs 2>&1
$exitCode = $LASTEXITCODE

if ($exitCode -ne 0) {
    throw ("Scheduled task olusturulamadi: " + ($result -join [Environment]::NewLine))
}

Write-Output "Scheduled task kaydedildi: $taskName"
