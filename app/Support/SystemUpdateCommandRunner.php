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
        $commands = [
            'git pull',
            'composer install --no-interaction --prefer-dist',
            'npm install',
            PHP_BINARY.' artisan migrate --force',
            PHP_BINARY.' artisan config:clear',
            PHP_BINARY.' artisan cache:clear',
            PHP_BINARY.' artisan route:clear',
            PHP_BINARY.' artisan view:clear',
            'npm run build',
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
}
