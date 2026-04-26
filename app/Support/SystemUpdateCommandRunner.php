<?php

namespace App\Support;

use Symfony\Component\Process\Process;

final class SystemUpdateCommandRunner
{
    /**
     * @return array{ok:bool,message:string}
     */
    public function run(): array
    {
        $git = $this->resolveExecutable('git', [
            'C:\Program Files\Git\cmd\git.exe',
            'C:\Program Files\Git\bin\git.exe',
            'C:\Program Files (x86)\Git\cmd\git.exe',
            'C:\Program Files (x86)\Git\bin\git.exe',
        ]);
        $composer = $this->resolveExecutable('composer', [
            'C:\ProgramData\ComposerSetup\bin\composer.bat',
            'C:\ProgramData\ComposerSetup\bin\composer',
            'C:\Program Files\Composer\composer.bat',
            'C:\Program Files (x86)\Composer\composer.bat',
        ]);
        $npm = $this->resolveExecutable('npm', [
            'C:\Program Files\nodejs\npm.cmd',
            'C:\Program Files (x86)\nodejs\npm.cmd',
        ]);
        $phpBinary = $this->resolvedPhpBinary();

        $commands = [
            $git.' pull',
            $composer.' install --no-interaction --prefer-dist',
            $npm.' install',
            $phpBinary.' artisan migrate --force',
            $phpBinary.' artisan config:clear',
            $phpBinary.' artisan cache:clear',
            $phpBinary.' artisan route:clear',
            $phpBinary.' artisan view:clear',
            $npm.' run build',
        ];

        foreach ($commands as $command) {
            $process = Process::fromShellCommandline($command, base_path());
            $process->setTimeout(1200);
            $process->run();

            if (! $process->isSuccessful()) {
                $output = trim($process->getErrorOutput()) !== '' ? trim($process->getErrorOutput()) : trim($process->getOutput());
                $lastLine = $this->lastLine($output);

                return [
                    'ok' => false,
                    'message' => 'Update command failed: '.$command.($lastLine !== '' ? ' ('.$lastLine.')' : '.'),
                ];
            }
        }

        return [
            'ok' => true,
            'message' => 'System update commands completed successfully.',
        ];
    }

    private function lastLine(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));

        return $lines === [] ? '' : (string) end($lines);
    }

    /**
     * Keep compatibility with existing PATH behavior while adding safe Windows fallbacks.
     *
     * @param  list<string>  $windowsCandidates
     */
    private function resolveExecutable(string $default, array $windowsCandidates): string
    {
        if (! $this->isWindows()) {
            return $default;
        }

        foreach ($windowsCandidates as $candidate) {
            if (is_file($candidate)) {
                return '"'.$candidate.'"';
            }
        }

        return $default;
    }

    private function resolvedPhpBinary(): string
    {
        if (! $this->isWindows()) {
            return PHP_BINARY;
        }

        if (is_file(PHP_BINARY)) {
            return '"'.PHP_BINARY.'"';
        }

        return PHP_BINARY;
    }

    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}
