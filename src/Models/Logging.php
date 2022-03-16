<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;


class Logging extends Model
{
    protected $table = "logging";

    protected $primaryKey = 'log_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'log_id',
        'log_type',
        'log_action',
        'log_timestamp',
        'log_user',
        'log_user_text',
        'log_namespace',
        'log_title',
        'log_page',
        'log_comment',
        'log_params',
        'log_deleted',
    ];
}
