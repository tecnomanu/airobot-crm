<?php

namespace App\Http\Controllers\Web\User;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Http\Traits\AuthorizesActions;
use App\Models\User;
use App\Services\Client\ClientService;
use App\Services\User\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use AuthorizesActions;

    public function __construct(
        private UserService $userService,
        private ClientService $clientService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $this->authorizePolicyAction('viewAny', User::class);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        $filters = [
            'client_id' => $request->input('client_id'),
            'role' => $request->input('role'),
            'is_seller' => $request->input('is_seller'),
            'search' => $request->input('search'),
        ];

        $users = $this->userService->getUsers(
            $filters,
            $request->input('per_page', 15),
            $currentUser
        );

        $clients = $this->canPerform('viewAny', User::class) && $currentUser->isAdmin()
            ? $this->clientService->getActiveClients()
            : collect();

        return Inertia::render('Users/Index', [
            'users' => UserResource::collection($users)->response()->getData(true),
            'filters' => $filters,
            'clients' => $clients,
            'roles' => UserRole::options(),
            'can' => [
                'create' => $this->canPerform('create', User::class),
                'viewAllClients' => $currentUser->isAdmin(),
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorizePolicyAction('create', User::class);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        try {
            $data = $request->validated();

            // Supervisors can only create users in their own client
            if ($currentUser->isSupervisor() && !$currentUser->isGlobalUser()) {
                $data['client_id'] = $currentUser->client_id;
            }

            // Validate role permission via policy
            $targetRole = $data['role'] ?? UserRole::USER->value;
            $tempUser = new User(['role' => $targetRole]);
            $this->authorizePolicyAction('assignRole', [$tempUser, $targetRole]);

            $this->userService->createUser($data);

            return redirect()->route('users.index')
                ->with('success', 'Usuario creado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $targetUser = $this->userService->getUserById($id);

        if (!$targetUser) {
            abort(404, 'Usuario no encontrado');
        }

        $this->authorizeFor($targetUser, 'update');

        /** @var User $currentUser */
        $currentUser = Auth::user();

        try {
            $data = $request->validated();

            // Check role assignment permission
            if (isset($data['role'])) {
                if (!$this->canPerform('assignRole', [$targetUser, $data['role']])) {
                    unset($data['role']);
                }
            }

            // Supervisors cannot change client_id to other clients
            if ($currentUser->isSupervisor() && !$currentUser->isGlobalUser()) {
                unset($data['client_id']);
            }

            $this->userService->updateUser($id, $data);

            return redirect()->route('users.index')
                ->with('success', 'Usuario actualizado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(int $id): RedirectResponse
    {
        $targetUser = $this->userService->getUserById($id);

        if (!$targetUser) {
            abort(404, 'Usuario no encontrado');
        }

        $this->authorizeFor($targetUser, 'delete');

        try {
            $this->userService->deleteUser($id);

            return redirect()->route('users.index')
                ->with('success', 'Usuario eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle seller status for a user.
     */
    public function toggleSeller(int $id): RedirectResponse
    {
        $targetUser = $this->userService->getUserById($id);

        if (!$targetUser) {
            abort(404, 'Usuario no encontrado');
        }

        $this->authorizeFor($targetUser, 'toggleSeller');

        try {
            $this->userService->toggleSellerStatus($id);

            return redirect()->back()
                ->with('success', 'Estado de vendedor actualizado');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle active/inactive status for a user.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $targetUser = $this->userService->getUserById($id);

        if (!$targetUser) {
            abort(404, 'Usuario no encontrado');
        }

        $this->authorizeFor($targetUser, 'toggleStatus');

        try {
            $this->userService->toggleStatus($id);

            return redirect()->back()
                ->with('success', 'Estado del usuario actualizado');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
