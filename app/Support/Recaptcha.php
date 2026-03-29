<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * reCAPTCHA v3: one place for "when to show / require" and server-side verification.
 *
 * Skips processing in local/debug so dev matches login behavior and keys are not required locally.
 */
final class Recaptcha
{
    public static function shouldProcess(): bool
    {
        $siteKey = config('services.recaptcha.v3.site_key');
        $secretKey = config('services.recaptcha.v3.secret_key');
        if (! is_string($siteKey) || $siteKey === '' || ! is_string($secretKey) || $secretKey === '') {
            return false;
        }

        if (config('app.debug') || app()->environment('local')) {
            return false;
        }

        return true;
    }

    /**
     * @return array{ok: true}|array{ok: false, reason: 'network'|'invalid'}
     */
    public static function verifyV3(Request $request, string $token, string $expectedAction): array
    {
        if (! self::shouldProcess()) {
            return ['ok' => true];
        }

        $secretKey = (string) config('services.recaptcha.v3.secret_key');
        $payload = [
            'secret' => $secretKey,
            'response' => $token,
        ];

        if (config('services.recaptcha.v3.verify_remote_ip', true)) {
            $payload['remoteip'] = $request->ip();
        }

        try {
            $verify = Http::asForm()
                ->timeout(8)
                ->connectTimeout(3)
                ->post('https://www.google.com/recaptcha/api/siteverify', $payload);
        } catch (ConnectionException $e) {
            report($e);

            return ['ok' => false, 'reason' => 'network'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'reason' => 'network'];
        }

        $body = $verify->json() ?? [];

        if (! ($body['success'] ?? false)) {
            logger()->warning('reCAPTCHA siteverify returned success=false', [
                'error-codes' => $body['error-codes'] ?? [],
                'hostname' => $body['hostname'] ?? null,
            ]);

            return ['ok' => false, 'reason' => 'invalid'];
        }

        $returnedAction = (string) ($body['action'] ?? '');
        if ($returnedAction !== '' && $returnedAction !== $expectedAction) {
            logger()->warning('reCAPTCHA action mismatch', [
                'expected' => $expectedAction,
                'got' => $returnedAction,
            ]);

            return ['ok' => false, 'reason' => 'invalid'];
        }

        $threshold = (float) config('services.recaptcha.v3.score_threshold', 0.35);
        $score = (float) ($body['score'] ?? 0);
        if ($score < $threshold) {
            logger()->info('reCAPTCHA score below threshold', [
                'score' => $score,
                'threshold' => $threshold,
                'action' => $expectedAction,
            ]);

            return ['ok' => false, 'reason' => 'invalid'];
        }

        return ['ok' => true];
    }
}
