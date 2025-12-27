<?php

namespace PROLANCEE\Support\App\Models;

use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PROLANCEE\Support\App\Models\BaseSecureModel;

class EloquentModel extends BaseSecureModel
{
    use HasApiTokens, Notifiable;

    /** Dynamically assigned in repository */
    protected $table;

    /** Dynamically controlled fillable */
    protected $fillable = [];

    /** Block all fields unless explicitly whitelisted */
    protected $guarded = ['*'];

    /** Enable Laravel timestamps */
    public $timestamps = true;

    /**
     * Force secure hidden fields on every serialization
     */
    public function toArray(): array
    {
        $this->setHidden($this->secureHidden());
        return parent::toArray();
    }
}
