<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Apply pagination with backend limit validation and optional search
     * 
     * @param Builder $query
     * @param Request $request
     * @param int $backendMaxLimit
     * @param array $searchableFields
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    private function applyPagination($query, Request $request, int $backendMaxLimit, array $searchableFields = [])
    {
        // Get per_page from frontend
        $requestedPerPage = $request->input('per_page');
        
        // Validate per_page is provided and greater than 0
        if (empty($requestedPerPage) || $requestedPerPage <= 0) {
            throw ValidationException::withMessages([
                'per_page' => 'per_page is required and must be greater than 0'
            ]);
        }
        
        // Apply search if search keyword and searchable fields are provided
        $search = $request->input('search');
        if (!empty($search) && !empty($searchableFields)) {
            $query->where(function ($q) use ($search, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }


        // user  llst search by date;
             $startDate = $request->input('start_date');
             $endDate   = $request->input('end_date');

                 if (!empty($startDate) && !empty($endDate)) {
                  $query->whereBetween('created_at', [
                  $startDate . ' 00:00:00',
                  $endDate   . ' 23:59:59'
                 ]);
                 }

        
        // Apply pagination logic: min(frontend_limit, backend_max_limit)
        $perPage = min($requestedPerPage, $backendMaxLimit);
        
        // Get page from request (default to 1)
        $page = $request->input('page', 1);
        
        // Apply Laravel's native paginate()
        return $query->paginate($perPage, ['*'], 'page', $page);
    }




   
 public function list(Request $request)
{
    $backendMaxLimit = 10;

    // Total users in DB (without any filter)
    $overallTotal = User::count();

    // Base query
    $query = User::with('hobbies:id,name')
        ->select('users.id', 'users.name', 'users.email', 'users.created_at')
        ->orderBy('users.created_at', 'desc');


    

    // Apply pagination (this also applies search + date filters)
    $paginator = $this->applyPagination($query, $request, $backendMaxLimit, ['name', 'email']);

    // Total users after applying search/date filters
    $filteredTotal = $paginator->total();

    return response()->json([
        'status' => true,

        // // Total counts
        // 'total_users' => $overallTotal,       // All users in DB
        // 'filtered_users' => $filteredTotal,     // After search/date filter

        // Data
        'data' => $paginator->items(),

        // Pagination
        'pagination' => [
            // 'current_page' => $paginator->currentPage(),
            'total' => $filteredTotal,
            'per_page' => $paginator->perPage(),
            // 'last_page' => $paginator->lastPage(),
            // 'from' => $paginator->firstItem(),
            // 'to' => $paginator->lastItem(),
            // 'has_more' => $paginator->hasMorePages(),
        ]
    ]);
}


    /**
     * Update a user record using Laravel's built-in update method
     * 
     * @param Request $request
     * @param User $user - Route model binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        // Validate input - only allow specific fields to be updated
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'hobbies' => 'sometimes|array',
             'hobbies.*' => 'exists:hobbies,id'  
        ]);
        
        // Update using Laravel's built-in update method with mass assignment protection
        $user->update($validated);
        
         // Agar hobbies aaye ho to pivot table update karo
        if ($request->has('hobbies')) {
            // sync purani hobbies hata ke nayi hobbies insert kar deta hai
            $user->hobbies()->sync($request->hobbies);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'User updated successfully',
            // 'data' => [
            //     $user->fresh(),
            //     'hobbies' => $user->hobbies()->pluck('name')
            // ]
        ]);
    }

    /**
     * Get all available hobbies
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHobbies()
    {
       
        
        return response()->json([
            'status' => true,
            'data' => \App\Models\Hobby::orderBy('id', 'asc')->get(['id', 'name']),
            'total' => \App\Models\Hobby::count()
        ]);
    }

    /**
     * Delete a user record using Laravel's built-in delete method
     * 
     * @param User $user - Route model binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        // Delete using Laravel's built-in delete method
        $user->delete();
        
        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
