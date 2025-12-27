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
    | Insert single or bulk records
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

    public function storeBulk(array $data): array|bool
    {
        return $this->repository->storeBulk(
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
    | Retrieve single records and builder
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

    public function fetchBuilder(array $config): array|bool
    {
        $group = is_callable($config['group']) ? $config['group']() : $config['group'];

        return $this->repository->fetchBuilder(
            $config['table'],
            $group['select'] ?? ['*'],
            $group['join'] ?? [],
            $group['where'] ?? [],
            $group['or_where'] ?? [],
            $group['where_row'] ?? [],
            $group['between'] ?? [],
            $group['not_between'] ?? [],
            $group['in'] ?? [],
            $group['not_in'] ?? [],
            $group['like'] ?? [],
            $group['not_like'] ?? [],
            $group['order_by'] ?? [],
            $group['offset'] ?? 0,
            $group['limit'] ?? '*',
            $group['notifier'] ?? '',
            $group['operation'] ?? 'fetch'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Methods
    |--------------------------------------------------------------------------
    | Update single or bulk records
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

    public function updateBulk(array $data): array|bool
    {
        return $this->repository->updateBulk(
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
    | Remove single or bulk records
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

    public function deleteBulk(array $data): array|bool
    {
        return $this->repository->deleteBulk(
            (array) ($data['table'] ?? []),
            (array) ($data['column'] ?? []),
            (array) ($data['unique'] ?? []),
            (string) ($data['notifier'] ?? ''),
            (string) ($data['operation'] ?? '')
        );
    }
}