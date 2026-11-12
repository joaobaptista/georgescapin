<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AccountQueries;
use App\Application\Identity\ActivateAccount;
use App\Application\Identity\ChangePassword;
use App\Application\Identity\Login;
use App\Application\Identity\Logout;
use App\Application\Identity\RegisterPatientAccount;
use App\Application\Identity\UpdateProfile;
use App\Http\Request;
use App\Http\Response;
use App\Http\Validation\Validator;

final class AuthController
{
    public function __construct(
        private readonly RegisterPatientAccount $register,
        private readonly Login $login,
        private readonly Logout $logout,
        private readonly ActivateAccount $activate,
        private readonly UpdateProfile $updateProfile,
        private readonly ChangePassword $changePassword,
        private readonly AccountQueries $accounts,
    ) {
    }

    public function register(Request $request): Response
    {
        $input = Validator::validate($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
            'role' => 'sometimes|in:patient,professional',
            'cpf' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
        ]);
        $session = ($this->register)($input);

        return Response::json(['user' => $this->accounts->currentUser($session['userId']), 'token' => $session['token']], 201);
    }

    public function login(Request $request): Response
    {
        $input = Validator::validate($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);
        $session = ($this->login)($input['email'], $input['password'], $request->clientIp);

        return Response::json(['user' => $this->accounts->currentUser($session['userId']), 'token' => $session['token']]);
    }

    public function logout(Request $request): Response
    {
        ($this->logout)($request->actor());

        return Response::json(['message' => 'Logout realizado com sucesso.']);
    }

    public function user(Request $request): Response
    {
        return Response::json($this->accounts->currentUser($request->actor()->userId));
    }

    public function activateAccount(Request $request): Response
    {
        $input = Validator::validate($request->all(), [
            'email' => 'required|email|max:255',
            'token' => 'required|string|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        ($this->activate)($input['email'], $input['token'], $input['password'], $request->clientIp);

        return Response::json(['success' => true, 'message' => 'Conta ativada com sucesso!']);
    }

    public function updateProfile(Request $request): Response
    {
        $input = Validator::validate($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'current_password' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:20',
            'birth_date' => 'nullable|string|max:10',
        ]);
        ($this->updateProfile)($request->actor(), $input);

        return Response::json(['message' => 'Perfil atualizado com sucesso.']);
    }

    public function updatePassword(Request $request): Response
    {
        $input = Validator::validate($request->all(), [
            'current_password' => 'required|string|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        ($this->changePassword)($request->actor(), $input['current_password'], $input['password']);

        return Response::json(['message' => 'Senha atualizada com sucesso.']);
    }
}
