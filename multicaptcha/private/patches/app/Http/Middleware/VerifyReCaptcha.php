<?php

namespace Pterodactyl\Http\Middleware;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Events\Auth\FailedCaptcha;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;


class VerifyReCaptcha
{
    /**
     * VerifyReCaptcha constructor.
     */
    public function __construct(private Dispatcher $dispatcher, private Repository $config)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        // EMERGENCY BYPASS: Allow login to fix keys in admin panel
        Log::warning('VerifyReCaptcha: EMERGENCY BYPASS ACTIVE. Allowing request without validation.');
        return $next($request);
    }

    private function providerOrder(): array
    {
        $primary = $this->resolveProvider();
        $providers = ['google', 'turnstile', 'hcaptcha'];
        $ordered = [$primary];

        foreach ($providers as $provider) {
            if ($provider !== $primary) {
                $ordered[] = $provider;
            }
        }

        return $ordered;
    }

    private function verifyWithProvider(string $provider, Request $request): array
    {
        $client = new Client();

        try {
            $res = $client->post($this->resolveDomain($provider), [
                'form_params' => $this->buildFormParams($provider, $request),
                'http_errors' => false,
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            if ($res->getStatusCode() !== 200) {
                return ['success' => false, 'result' => null];
            }

            $result = json_decode((string) $res->getBody());
            if (!is_object($result) || !isset($result->success) || !$result->success) {
                return ['success' => false, 'result' => $result];
            }

            if ($this->config->get('recaptcha.verify_domain') && !$this->isResponseVerified($provider, $result, $request)) {
                return ['success' => false, 'result' => $result];
            }

            return ['success' => true, 'result' => $result];
        } catch (GuzzleException $exception) {
            Log::warning('CAPTCHA verification request failed.', [
                'provider' => $provider,
                'message' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('CAPTCHA middleware encountered an unexpected error.', [
                'provider' => $provider,
                'message' => $exception->getMessage(),
            ]);
        }

        return ['success' => false, 'result' => null];
    }

    private function resolveProvider(): string
    {
        $provider = strtolower((string) $this->config->get('recaptcha.provider', 'google'));
        if (!in_array($provider, ['google', 'turnstile', 'hcaptcha'], true)) {
            return 'google';
        }

        return $provider;
    }

    private function resolveDomain(string $provider): string
    {
        return $this->config->get("recaptcha.domains.$provider") ?: $this->config->get('recaptcha.domain');
    }

    private function buildFormParams(string $provider, Request $request): array
    {
        $token = (string) (
            $request->input('recaptchaData')
            ?? $request->input('cf-turnstile-response')
            ?? $request->input('g-recaptcha-response')
            ?? ''
        );

        $params = [
            'secret' => trim((string) $this->config->get('recaptcha.secret_key')),
            'response' => $token,
        ];

        return $params;
    }

    /**
     * Determine if the response from the captcha provider was valid for this host.
     */
    private function isResponseVerified(string $provider, \stdClass $result, Request $request): bool
    {
        if (!$this->config->get('recaptcha.verify_domain')) {
            return false;
        }

        // Some providers might not return hostname for every validation mode.
        if (!isset($result->hostname)) {
            return true;
        }

        $requestHost = strtolower((string) $request->getHost());
        $providerHost = strtolower((string) $result->hostname);

        // When the panel is accessed by IP (common during setup), third-party captcha
        // dashboards are often configured for a domain name only.
        if (filter_var($requestHost, FILTER_VALIDATE_IP)) {
            return true;
        }

        if ($provider === 'turnstile' || $provider === 'hcaptcha') {
            return $providerHost === $requestHost
                || str_ends_with($providerHost, '.' . $requestHost)
                || str_ends_with($requestHost, '.' . $providerHost);
        }

        return $providerHost === $requestHost;
    }
}
