<?php

namespace PROLANCEE\Support\App\Repositories;

interface BaseRepositoryInterface
{
    /*
    |---------------------------------------------------------------------------
    | Signup
    |---------------------------------------------------------------------------
    | Create a new user record in the database during the signup process.
    */
    public function signup(
        string $table, 
        array $data, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Signin
    |---------------------------------------------------------------------------
    | Authenticate a user by validating credentials against stored records.
    | Typically used for login verification before generating an auth session.
    */
    public function signin(
        string $table, 
        string $column, 
        int|string $unique, 
        string $password, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Register
    |---------------------------------------------------------------------------
    | Store new user registration data into the database.
    | Used to create a user record after pre-validation in the service layer.
    */
    public function register(
        string $table, 
        array $data, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Login
    |---------------------------------------------------------------------------
    | Retrieve a user record for login validation using provided conditions.
    | This method fetches the record required to verify login credentials.
    */
    public function login(
        string $table, 
        string $column, 
        int|string $unique, 
        string $password, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Forgot Password Reset Link
    |---------------------------------------------------------------------------
    | Update the password reset token or link associated with the user account.
    | Commonly used during the "forgot password" flow.
    */
    public function passwordResetToken(
        string $email, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Change Password
    |---------------------------------------------------------------------------
    | Update the password of an authenticated user using matching conditions.
    | Supports conditional updates such as checking old password, status, etc.
    */
    public function changePassword(
        string $table, 
        string $column, 
        int|string $unique, 
        string $password, 
        string $token, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Logout
    |---------------------------------------------------------------------------
    | Invalidate user authentication by removing or clearing stored login data.
    | Example: deleting session records, tokens, or device bindings.
    */
    public function logout(
        string $table, 
        string $column, 
        int|string $unique, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Store Methods
    |---------------------------------------------------------------------------
    | Create or insert records into the database
    */
    public function storeSingle(
        string $table, 
        array $data, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Store Multiple Methods
    |---------------------------------------------------------------------------
    | Create or insert multiple records into the database
    */
    public function storeMultiple(
        array $table, 
        array $data, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Fetch Single Methods
    |---------------------------------------------------------------------------
    | Fetch a single record based on a condition
    */
    public function fetchSingle(
        string $table, 
        string $column, 
        int|string $unique, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Fetch Multiple Methods
    |---------------------------------------------------------------------------
    | Fetch multiple records with optional sorting and pagination
    */
    public function fetchMultiple(
        string $table, 
        array $orderBy, 
        int $offset, 
        int|string $limit, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Update Methods
    |---------------------------------------------------------------------------
    | Update existing records in the database
    */
    public function updateSingle(
        string $table, 
        string $column, 
        int|string $unique, 
        array $data, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Update Multiple Methods
    |---------------------------------------------------------------------------
    | Update multiple records in the database
    */
    public function updateMultiple(
        array $table, 
        array $column, 
        array $unique, 
        array $data, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Delete Methods
    |---------------------------------------------------------------------------
    | Delete a single record from the database
    */
    public function deleteSingle(
        string $table, 
        string $column, 
        int|string $unique, 
        string $notifier, 
        string $operation
    ): array|bool;

    /*
    |---------------------------------------------------------------------------
    | Delete Multiple Methods
    |---------------------------------------------------------------------------
    | Delete multiple records from the database
    */
    public function deleteMultiple(
        array $table, 
        array $column, 
        array $unique, 
        string $notifier, 
        string $operation
    ): array|bool;
}