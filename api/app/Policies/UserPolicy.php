<?php 

use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;


class UserPolicy
{
    public function viewAny($user)
    {
        return $user->is_admin;
    }
    public function delete($user, $model)
    {
        return $user->is_admin && $user->id !== $model->id;
    }
    public function restore($user)
    {
        return $user->is_admin;
    }
    public function block($user)
    {
        return $user->is_admin;
    }
    public function unblock($user)
    {
        return $user->is_admin;
    }
}