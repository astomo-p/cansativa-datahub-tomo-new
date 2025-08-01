<?php

namespace Modules\Whatsapp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Whatsapp\Models\MessageAssignment;
use Modules\User\Models\User;
use App\Traits\ApiResponder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class WhatsappAssignmentController extends Controller
{
    use ApiResponder;

    public function assignContactToAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contactId' => 'required|exists:contacts,id',
            'agentId' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $assignment = MessageAssignment::updateOrCreate(
                ['contact_id' => $request->contactId],
                [
                    'assigned_to' => $request->agentId,
                    'assigned_by' => Auth::id(),
                    'assigned_date' => Carbon::now(),
                    'status' => MessageAssignment::STATUS_ASSIGNED,
                    'message_status' => MessageAssignment::MESSAGE_STATUS_IN_PROGRESS
                ]
            );

            return $this->successResponse($assignment, 'Contact assigned successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign contact: ' . $e->getMessage(), 500);
        }
    }

    public function getAgents(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search');

        $query = User::select(['id', 'user_name'])
            ->orderBy('user_name');

        if ($search) {
            $query->where('user_name', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $results = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->successResponse([
            'data' => $results,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ]
        ]);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contactId' => 'required|exists:contacts,id',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $status = $request->status;

            if (is_numeric($status)) {
                $status = $status == 1 ? MessageAssignment::MESSAGE_STATUS_IN_PROGRESS : MessageAssignment::MESSAGE_STATUS_ARCHIVED;
            }

            if (!in_array($status, [MessageAssignment::MESSAGE_STATUS_IN_PROGRESS, MessageAssignment::MESSAGE_STATUS_ARCHIVED])) {
                return $this->errorResponse('Invalid status value', 422);
            }

            $assignment = MessageAssignment::where('contact_id', $request->contactId)->first();

            if (!$assignment) {
                return $this->errorResponse('No assignment found for this contact', 404);
            }

            $assignment->message_status = $status;

            if ($status === MessageAssignment::MESSAGE_STATUS_ARCHIVED) {
                $assignment->status = MessageAssignment::STATUS_UNASSIGNED;
                $assignment->archived_by = Auth::id();
                $assignment->assigned_to = null;
                if (Schema::hasColumn('wa_message_assignments', 'archived_date')) {
                    $assignment->archived_date = Carbon::now();
                }
            }

            $assignment->save();

            return $this->successResponse($assignment, 'Status updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update status: ' . $e->getMessage(), 500);
        }
    }
}
