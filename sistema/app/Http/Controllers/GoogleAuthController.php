<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AccountQueries;
use App\Application\Identity\CompleteGoogleSignIn;
use App\Application\Identity\ExchangeLoginCode;
use App\Application\Identity\GoogleIdentityProvider;
use App\Application\Identity\StartGoogleSignIn;
use App\Domain\Shared\DomainError;
use App\Http\Request;
use App\Http\Response;
use App\Http\Validation\Validator;

/**
 * "Entrar com o Google". The browser is redirected to Google and back; the
 * result reaches the app as a one-time code in the URL fragment (never sent
 * to servers or in Referer), which the page exchanges for a session via POST.
 */
final class GoogleAuthController
{
    private const STATE_COOKIE = 'nuva_google_state';
    private const COOKIE_PATH = '/api/auth/google';

    public function __construct(
        private readonly GoogleIdentityProvider $google,
        private readonly StartGoogleSignIn $start,
        private readonly CompleteGoogleSignIn $complete,
        private readonly ExchangeLoginCode $exchange,
        private readonly AccountQueries $accounts,
    ) {
    }

    public function providers(Request $request): Response
    {
        return Response::json(['google' => $this->google->isConfigured()]);
    }

    public function redirect(Request $request): Response
    {
        try {
            $start = ($this->start)();
        } catch (DomainError $error) {
            return self::backToLogin(['google_error' => $error->getMessage()]);
        }

        return Response::redirect($start['url'])
            ->withHeader('Set-Cookie', self::stateCookie($start['state'], 600, $request->https));
    }

    public function callback(Request $request): Response
    {
        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');

        if ($request->query('error') !== null || $code === '' || $state === '') {
            $response = self::backToLogin(['google_error' => 'O login com o Google foi cancelado.']);
        } else {
            try {
                $loginCode = ($this->complete)($code, $state, $request->cookie(self::STATE_COOKIE), $request->clientIp);
                $response = self::backToLogin(['google_code' => $loginCode]);
            } catch (DomainError $error) {
                $response = self::backToLogin(['google_error' => $error->getMessage()]);
            }
        }

        return $response->withHeader('Set-Cookie', self::stateCookie('', 0, $request->https));
    }

    public function exchange(Request $request): Response
    {
        $input = Validator::validate($request->all(), ['code' => 'required|string|max:128']);
        $session = ($this->exchange)($input['code']);

        return Response::json(['user' => $this->accounts->currentUser($session['userId']), 'token' => $session['token']]);
    }

    private static function backToLogin(array $fragment): Response
    {
        return Response::redirect('/login#' . http_build_query($fragment));
    }

    /** HttpOnly + SameSite=Lax: sent on Google's top-level redirect back, unreadable by scripts. */
    private static function stateCookie(string $value, int $maxAge, bool $secure): string
    {
        return sprintf(
            '%s=%s; Max-Age=%d; Path=%s; HttpOnly; SameSite=Lax%s',
            self::STATE_COOKIE,
            rawurlencode($value),
            $maxAge,
            self::COOKIE_PATH,
            $secure ? '; Secure' : '',
        );
    }
}
