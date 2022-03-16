<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;



class CacheTable extends Model
{
    protected $table = "cache_table";

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'url'
    ];

}
