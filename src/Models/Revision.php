<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Revision extends Model
{
    protected $table = "revision";

    protected $primaryKey = null;
    public $incrementing = false;

    const CREATED_AT = null;
    const UPDATED_AT = null;
    // Revision deletion constants
    const DELETED_TEXT = 1;
    const DELETED_COMMENT = 2;
    const DELETED_USER = 4;
    const DELETED_RESTRICTED = 8;
    const SUPPRESSED_USER = 12; // convenience

    // Audience options for accessors
    const FOR_PUBLIC = 1;
    const FOR_THIS_USER = 2;
    const RAW = 3;

    // Constants for object loading bitfield flags (higher => higher QoS)
    const READ_LATEST = 1; // read from the master
    const READ_LOCKING = 3; // READ_LATEST (1) and "LOCK IN SHARE MODE" (2)
    const READ_EXCLUSIVE = 7; // READ_LOCKING (3) and "FOR UPDATE" (4)

    // Convenience constant for callers to explicitly request slave data
    const READ_NORMAL = 0; // read from the slave

    // Convenience constant for tracking how data was loaded (higher => higher QoS)
    const READ_NONE = -1; // not loaded yet (or the object was cleared)

    protected $fillable = [
        'rev_id',
        'rev_page',
        'rev_text_id',
        'rev_comment',
        'rev_user',
        'rev_user_text',
        'rev_timestamp',
        'rev_minor_edit',
        'rev_deleted',
        'rev_len',
        'rev_parent_id',
        'rev_sha1',
        'rev_content_format',
        'rev_content_model',
        'waraqa_writer_id',
        'waraqa_proofreader_id',
    ];


    /**
     * @return BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'rev_page', 'page_id');
    }

    /**
     * @return BelongsTo
     */
    public function text(): BelongsTo
    {
        return $this->belongsTo(Text::class, 'rev_text_id', 'old_id');
    }

}
