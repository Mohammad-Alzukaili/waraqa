<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class CategoryLink extends Model
{
    protected $table = "categorylinks";


    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'cl_from',
        'cl_to',
        'cl_sortkey',
        'cl_timestamp',
        'cl_sortkey_prefix',
        'cl_collation',
        'cl_type'
    ];
}
