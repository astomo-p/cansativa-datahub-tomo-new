<?php

namespace Modules\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\NewContactData\Models\Contacts;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Whatsapp\Models\Message;
// use Modules\Whatsapp\Database\Factories\MessageAssignmentFactory;

class MessageAssignment extends Model
{
    use HasFactory;

    protected $table = 'wa_message_assignments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['contact_id', 'assigned_to', 'assigned_by', 'assigned_date', 'status', 'message_status', 'archived_by'];

    /**
     * The status values allowed for message assignments
     */
    const STATUS_ASSIGNED = 'ASSIGNED';
    const STATUS_UNASSIGNED = 'UNASSIGNED';

    /**
     * The message status values allowed
     */
    const MESSAGE_STATUS_IN_PROGRESS = 'IN PROGRESS';
    const MESSAGE_STATUS_ARCHIVED = 'ARCHIVED';

    /**
     * Get the contact associated with this assignment
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contacts::class, 'contact_id');
    }

    public static function getMessageData($page = 1, $perPage = 10, $search = null, $filters = null)
    {
        $query = self::query()
            ->join('contacts', 'wa_message_assignments.contact_id', '=', 'contacts.id')
            ->select([
                'contacts.id as contact_id',
                'contacts.contact_name',
                'contacts.phone_no',
                'contacts.last_message_at as last_message',
                'wa_message_assignments.status',
                'wa_message_assignments.message_status',
                'contacts.contact_type_id',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contacts.contact_name', 'like', '%' . $search . '%')
                    ->orWhere('contacts.phone_no', 'like', '%' . $search . '%');
            });
        }

        if ($filters && is_array($filters) && count($filters) > 0) {
            $query->where(function ($mainQuery) use ($filters) {
                $firstCondition = true;

                foreach ($filters as $filter) {
                    if (isset($filter['typeId']) && isset($filter['statuses']) && is_array($filter['statuses'])) {
                        $typeId = $filter['typeId'];
                        $statuses = array_map(function ($status) {
                            if (strtoupper($status) === 'IN PROGRESS') {
                                return self::MESSAGE_STATUS_IN_PROGRESS;
                            } elseif (strtoupper($status) === 'ARCHIVED') {
                                return self::MESSAGE_STATUS_ARCHIVED;
                            }
                            return $status;
                        }, $filter['statuses']);

                        if ($firstCondition) {
                            $mainQuery->where(function ($q) use ($typeId, $statuses) {
                                $q->where('contacts.contact_type_id', $typeId)
                                    ->whereIn('wa_message_assignments.message_status', $statuses);
                            });
                            $firstCondition = false;
                        } else {
                            $mainQuery->orWhere(function ($q) use ($typeId, $statuses) {
                                $q->where('contacts.contact_type_id', $typeId)
                                    ->whereIn('wa_message_assignments.message_status', $statuses);
                            });
                        }
                    }
                }
            });
        }

        $total = $query->count();

        $offset = ($page - 1) * $perPage;
        $lastPage = ceil($total / $perPage);

        $results = $query->skip($offset)->take($perPage)->get()->toArray();

        foreach ($results as &$result) {
            $result['contact_name'] = $result['contact_name'] ?? 'Unknown';

            $unreadCount = Message::where('contact_id', $result['contact_id'])
                ->where('status', '!=', 'failed')
                ->where('is_read', false)
                ->count();

            $result['totalUnreadMessages'] = $unreadCount;
        }

        return [
            'results' => $results,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ]
        ];
    }

    /**
     * Boot method to set up model event listeners
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (!in_array($model->status, [self::STATUS_ASSIGNED, self::STATUS_UNASSIGNED])) {
                throw new \InvalidArgumentException('Status must be either ASSIGNED or UNASSIGNED');
            }

            if (
                !empty($model->message_status) &&
                !in_array($model->message_status, [self::MESSAGE_STATUS_IN_PROGRESS, self::MESSAGE_STATUS_ARCHIVED])
            ) {
                throw new \InvalidArgumentException('Message status must be either IN PROGRESS or ARCHIVED');
            }
        });
    }

    // protected static function newFactory(): MessageAssignmentFactory
    // {
    //     // return MessageAssignmentFactory::new();
    // }
}
