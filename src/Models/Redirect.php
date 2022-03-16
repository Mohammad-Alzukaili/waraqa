<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Redirect extends Model
{
    protected $table = "redirect";


    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
    'rd_from',
    'rd_namespace',
    'rd_title',
    'rd_interwiki',
    'rd_fragment'
    ];
}
