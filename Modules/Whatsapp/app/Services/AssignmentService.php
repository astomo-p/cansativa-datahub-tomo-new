<?php

namespace Modules\Whatsapp\Services;

use Modules\Whatsapp\Models\MessageAssignment;
use Modules\User\Models\UserMessageLanguage;
use Illuminate\Support\Facades\DB;
use Modules\NewContactData\Models\Contacts as ModelsContacts;

class AssignmentService
{
    protected $googleTranslateService;

    public function __construct(GoogleTranslateService $googleTranslateService)
    {
        $this->googleTranslateService = $googleTranslateService;
    }

    public function handle() {}

    public function getAgentToAssign($languageCode)
    {
        $usersWithLanguage = UserMessageLanguage::where('language_code', $languageCode)
            ->pluck('user_id')
            ->toArray();

        if (empty($usersWithLanguage)) {
            return null;
        }

        $userAssignmentCounts = MessageAssignment::whereIn('assigned_to', $usersWithLanguage)
            ->where('status', MessageAssignment::STATUS_ASSIGNED)
            ->where(function ($query) {
                $query->where('message_status', '!=', MessageAssignment::MESSAGE_STATUS_ARCHIVED)
                    ->orWhereNull('message_status');
            })
            ->select('assigned_to', DB::raw('count(*) as assignment_count'))
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to')
            ->toArray();

        $minAssignments = PHP_INT_MAX;
        $bestAgentId = null;

        foreach ($usersWithLanguage as $userId) {
            $assignmentCount = $userAssignmentCounts[$userId]['assignment_count'] ?? 0;

            if ($assignmentCount < $minAssignments) {
                $minAssignments = $assignmentCount;
                $bestAgentId = $userId;

                if ($minAssignments === 0) {
                    break;
                }
            }
        }

        return $bestAgentId;
    }

    public function assignmentChecker($contactId, $message)
    {
        $existingAssignment = MessageAssignment::where('contact_id', $contactId)
            ->where('status', MessageAssignment::STATUS_ASSIGNED)
            ->where('message_status', MessageAssignment::MESSAGE_STATUS_IN_PROGRESS)
            ->latest('created_at')
            ->first();

        if ($existingAssignment) {
            return $existingAssignment;
        }

        $latestAssignment = MessageAssignment::where('contact_id', $contactId)
            ->latest('created_at')
            ->first();

        if (!$latestAssignment) {
            $assignment = new MessageAssignment();
            $assignment->contact_id = $contactId;
            $assignment->message_status = MessageAssignment::MESSAGE_STATUS_IN_PROGRESS;
            $assignment->status = MessageAssignment::STATUS_UNASSIGNED;
            $assignment->save();

            return $assignment;
        }

        if (
            $latestAssignment->status === MessageAssignment::STATUS_UNASSIGNED ||
            $latestAssignment->message_status === MessageAssignment::MESSAGE_STATUS_ARCHIVED
        ) {
            $latestAssignment->message_status = MessageAssignment::MESSAGE_STATUS_IN_PROGRESS;

            $contact = ModelsContacts::find($contactId);
            $detectedLanguage = null;

            if (!$contact || empty($contact->message_language)) {
                $detectedLanguage = $this->googleTranslateService->detectLanguage($message);

                if ($detectedLanguage && $contact) {
                    $contact->message_language = $detectedLanguage;
                    $contact->save();
                }
            }

            $language = $detectedLanguage ?? ($contact && !empty($contact->message_language) ? $contact->message_language : null);

            if ($language) {
                $assignedTo = $this->getAgentToAssign($language);

                if ($assignedTo !== null) {
                    $latestAssignment->assigned_to = $assignedTo;
                    $latestAssignment->assigned_date = now();
                    $latestAssignment->status = MessageAssignment::STATUS_ASSIGNED;
                } else {
                    $latestAssignment->status = MessageAssignment::STATUS_UNASSIGNED;
                }
            } else {
                $latestAssignment->status = MessageAssignment::STATUS_UNASSIGNED;
            }

            $latestAssignment->save();
        }

        return $latestAssignment;
    }
}
