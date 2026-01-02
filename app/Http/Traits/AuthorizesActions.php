<?php

namespace App\Http\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Trait to simplify policy authorization in controllers.
 *
 * Provides convenient methods for authorizing actions with better
 * error handling and consistent patterns.
 *
 * Usage in Controller:
 *
 *   use AuthorizesActions;
 *
 *   public function index()
 *   {
 *       $this->authorizePolicyAction('viewAny', User::class);
 *       // or: $this->authorizeForModel(User::class, 'viewAny');
 *   }
 *
 *   public function update(Request $request, User $user)
 *   {
 *       $this->authorizePolicyAction('update', $user);
 *       // or: $this->authorizeFor($user, 'update');
 *   }
 */
trait AuthorizesActions
{
    /**
     * Authorize a policy action (preferred method).
     *
     * @param string $ability The policy method name
     * @param mixed $arguments Model instance, class string, or array [model, ...args]
     * @param string|null $message Custom error message
     * @throws AuthorizationException
     */
    protected function authorizePolicyAction(string $ability, mixed $arguments, ?string $message = null): void
    {
        $response = Gate::inspect($ability, $arguments);

        if ($response->denied()) {
            throw new AuthorizationException(
                $message ?? $response->message() ?? __('No tienes permisos para realizar esta acción')
            );
        }
    }

    /**
     * Authorize action on a specific model instance.
     *
     * @param Model $model The model to authorize against
     * @param string $ability The policy method name
     * @param array $extraArguments Additional arguments to pass to policy
     * @param string|null $message Custom error message
     * @throws AuthorizationException
     */
    protected function authorizeFor(Model $model, string $ability, array $extraArguments = [], ?string $message = null): void
    {
        $arguments = empty($extraArguments) ? $model : array_merge([$model], $extraArguments);

        $this->authorizePolicyAction($ability, $arguments, $message);
    }

    /**
     * Authorize action on a model class (for viewAny, create).
     *
     * @param string $modelClass The model class name
     * @param string $ability The policy method name
     * @param string|null $message Custom error message
     * @throws AuthorizationException
     */
    protected function authorizeForModel(string $modelClass, string $ability, ?string $message = null): void
    {
        $this->authorizePolicyAction($ability, $modelClass, $message);
    }

    /**
     * Check if user can perform action (returns bool, doesn't throw).
     *
     * @param string $ability The policy method name
     * @param mixed $arguments Model instance or class string
     * @return bool
     */
    protected function canPerform(string $ability, mixed $arguments): bool
    {
        return Gate::allows($ability, $arguments);
    }

    /**
     * Get authorization abilities for frontend (to pass to Inertia).
     *
     * @param Model|string $modelOrClass Model instance or class
     * @param array $abilities List of abilities to check
     * @return array<string, bool>
     */
    protected function getAuthorizationAbilities(Model|string $modelOrClass, array $abilities): array
    {
        $can = [];

        foreach ($abilities as $ability) {
            $can[$ability] = $this->canPerform($ability, $modelOrClass);
        }

        return $can;
    }

    /**
     * Get CRUD authorization abilities for a model class.
     *
     * @param string $modelClass Model class name
     * @param Model|null $instance Optional model instance for instance-based checks
     * @return array<string, bool>
     */
    protected function getCrudAbilities(string $modelClass, ?Model $instance = null): array
    {
        $abilities = [
            'viewAny' => $this->canPerform('viewAny', $modelClass),
            'create' => $this->canPerform('create', $modelClass),
        ];

        if ($instance) {
            $abilities['view'] = $this->canPerform('view', $instance);
            $abilities['update'] = $this->canPerform('update', $instance);
            $abilities['delete'] = $this->canPerform('delete', $instance);
        }

        return $abilities;
    }
}


