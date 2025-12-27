<?php

namespace App\Models\Prolancee\Classes;

use PROLANCEE\Support\App\Models\BaseSecureModel;

class User extends BaseSecureModel
{
    /** @var string Database table name */
    protected $table = 'users';

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Mandatory fields per endpoint.
     *
     * @var array<string, mixed>
     */
    protected array $mandatory = [
        '{intermediate}/endpoint-url' => [
            'required' => [
                // 'field' => 'Error message',
            ],
        ],
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
