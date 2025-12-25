<?php

namespace PROLANCEE\Support\App\Services;

use PROLANCEE\Support\App\Repositories\Eloquent\BaseRepository;

class BaseService
{
    protected $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /*
    |--------------------------------------------------------------------------
    | Signup
    |--------------------------------------------------------------------------
    | Create a new user account by storing user data into the database.
    */
    public function signup(array $data): array|bool
    {
        return $this->repository->signup(
            (string) ($data['table'] ?? ''),
            (array) ($data['data'] ?? []),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Signin
    |--------------------------------------------------------------------------
    | Authenticate a user by verifying credentials through repository.
    */
    public function signin(array $data): array|bool
    {
        return $this->repository->signin(
            (string) ($data['table'] ?? ''),
            (string) ($data['column'] ?? ''),
            isset($data['unique'])
                ? (is_numeric($data['unique']) ? (int) $data['unique'] : (string) $data['unique'])
                : null,
            (string) ($data['password'] ?? ''),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Register (Create User)
    |--------------------------------------------------------------------------
    | Store a new user record into the database during the registration process.
    */
    public function register(array $data): array|bool
    {
        return $this->repository->register(
            (string) ($data['table'] ?? ''),
            (array) ($data['data'] ?? []),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Login Fetch
    |--------------------------------------------------------------------------
    | Get the user record for login validation and return required details.
    */
    public function login(array $data): array|bool
    {
        return $this->repository->login(
            (string) ($data['table'] ?? ''),
            (string) ($data['column'] ?? ''),
            isset($data['unique'])
                ? (is_numeric($data['unique']) ? (int) $data['unique'] : (string) $data['unique'])
                : null,
            (string) ($data['password'] ?? ''),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Forgot Password Reset Link
    |--------------------------------------------------------------------------
    | Update/reset the password reset token or link for the specified user.
    */
    public function passwordResetToken(array $data): array|bool
    {
        return $this->repository->passwordResetToken(
            (string) ($data['email'] ?? ''), 
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Change Password
    |--------------------------------------------------------------------------
    | Update the user's password using the repository layer.
    */
    public function changePassword(array $data): array|bool
    {
        return $this->repository->changePassword(
            (string) ($data['table'] ?? ''),
            (string) ($data['column'] ?? ''),
            isset($data['unique'])
                ? (is_numeric($data['unique']) ? (int) $data['unique'] : (string) $data['unique'])
                : null,
            (string) ($data['password'] ?? ''),
            (string) ($data['token'] ?? ''),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    | Log the user out by removing or invalidating session/token entry.
    */
    public function logout(array $data): array|bool
    {
        return $this->repository->logout(
            (string) ($data['table'] ?? ''),
            (string) ($data['column'] ?? ''),
            isset($data['unique'])
                ? (is_numeric($data['unique']) ? (int) $data['unique'] : (string) $data['unique'])
                : null,
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Methods
    |--------------------------------------------------------------------------
    | Insert single or multiple records
    */
    public function storeSingle(array $data): array|bool
    {
        return $this->repository->storeSingle(
            (string) ($data['table'] ?? ''),
            (array) ($data['data'] ?? []),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    public function storeMultiple(array $data): array|bool
    {
        return $this->repository->storeMultiple(
            (array) ($data['table'] ?? []),
            (array) ($data['data'] ?? []),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Methods
    |--------------------------------------------------------------------------
    | Retrieve single or multiple records and builder
    */
    public function fetchSingle(array $data): array|bool
    {
        return $this->repository->fetchSingle(
            (string) ($data['table'] ?? ''),
            (string) ($data['column'] ?? ''),
            isset($data['unique'])
                ? (is_numeric($data['unique']) ? (int) $data['unique'] : (string) $data['unique'])
                : null,
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    public function fetchMultiple(array $data): array|bool
    {
        $limit = isset($data['limit'])
            ? (is_numeric($data['limit']) ? (int) $data['limit'] : (string) $data['limit'])
            : '*';

        return $this->repository->fetchMultiple(
            (string) ($data['table'] ?? ''),
            (array) ($data['orderBy'] ?? []),
            (int) ($data['offset'] ?? 0),
            $limit,
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Methods
    |--------------------------------------------------------------------------
    | Update single or multiple records
    */
    public function updateSingle(array $data): array|bool
    {
        return $this->repository->updateSingle(
            (string) ($data['table'] ?? ''),
            (string) ($data['column'] ?? ''),
            isset($data['unique'])
                ? (is_numeric($data['unique']) ? (int) $data['unique'] : (string) $data['unique'])
                : null,
            (array) ($data['data'] ?? []),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    public function updateMultiple(array $data): array|bool
    {
        return $this->repository->updateMultiple(
            (array) ($data['table'] ?? []),
            (array) ($data['column'] ?? []),
            (array) ($data['unique'] ?? []),
            (array) ($data['data'] ?? []),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Methods
    |--------------------------------------------------------------------------
    | Remove single or multiple records
    */
    public function deleteSingle(array $data): array|bool
    {
        return $this->repository->deleteSingle(
            (string) ($data['table'] ?? ''),
            (string) ($data['column'] ?? ''),
            isset($data['unique'])
                ? (is_numeric($data['unique']) ? (int) $data['unique'] : (string) $data['unique'])
                : null,
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }

    public function deleteMultiple(array $data): array|bool
    {
        return $this->repository->deleteMultiple(
            (array) ($data['table'] ?? []),
            (array) ($data['column'] ?? []),
            (array) ($data['unique'] ?? []),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }
}