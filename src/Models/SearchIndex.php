<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;


class SearchIndex extends Model
{
    protected $table = "searchindex";

    protected $primaryKey = null;
    public $incrementing = false;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'si_page',
        'si_title',
        'si_text',

    ];


}
