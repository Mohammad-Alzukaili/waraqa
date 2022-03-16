<?php

namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CategoryService
 *
 * @property int $cat_id
 * @property string $cat_title
 * @property int $cat_pages
 * @property int $cat_subcats
 * @property int $cat_files
 *
 * @package Mawdoo3\Models
 */
class Category extends Model
{
    protected $table = 'category';
    protected $primaryKey = 'cat_id';
    public $timestamps = false;

    protected $casts = [
        'cat_title' => 'varbinary',
        'cat_pages' => 'int',
        'cat_subcats' => 'int',
        'cat_files' => 'int'
    ];

    protected $fillable = [
        'cat_title',
        'cat_pages',
        'cat_subcats',
        'cat_files'
    ];
}
