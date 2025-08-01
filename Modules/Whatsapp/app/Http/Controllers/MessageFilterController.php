<?php

namespace Modules\Whatsapp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponder;
use Modules\Whatsapp\Models\MessageFilter;

class MessageFilterController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search');

        $query = MessageFilter::where('user_id', Auth::id());

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $filters = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->successResponse([
            'results' => $filters,
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

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'filter_query' => 'required|string'
        ]);

        $filter = new MessageFilter([
            'name' => $request->name,
            'filter_query' => $request->filter_query,
            'user_id' => Auth::id()
        ]);

        $filter->save();

        return $this->successResponse($filter, 'Message filter created successfully', 201);
    }

    public function update(Request $request, $id)
    {
        $filter = MessageFilter::where('user_id', Auth::id())->find($id);

        if (!$filter) {
            return $this->errorResponse('Message filter not found', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'filter_query' => 'sometimes|required|string'
        ]);

        $filter->update($request->only(['name', 'filter_query']));

        return $this->successResponse($filter, 'Message filter updated successfully');
    }

    public function destroy($id)
    {
        $filter = MessageFilter::where('user_id', Auth::id())->find($id);

        if (!$filter) {
            return $this->errorResponse('Message filter not found', 404);
        }

        $filter->delete();

        return $this->successResponse(null, 'Message filter deleted successfully');
    }
}
