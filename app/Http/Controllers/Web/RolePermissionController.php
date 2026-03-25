<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleModulePermission;
use App\Models\User;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $modules = config('lms_modules', []);
        $roles = Role::query()->orderBy('label')->get(['id', 'name', 'label']);

        $selectedRoleId = (int) $request->input('role_id', 0);
        if ($selectedRoleId === 0) {
            $selectedRoleId = (int) ($roles->first()->id ?? 0);
        }

        $rolePermissions = RoleModulePermission::query()
            ->where('role_id', $selectedRoleId)
            ->pluck('can_access', 'module_key')
            ->toArray();

        $roleFilter = (string) $request->input('user_role', 'teacher');
        $search = trim((string) $request->input('q', ''));

        $usersQuery = User::query()
            ->with('roles:id,name,label')
            ->whereHas('roles', fn ($q) => $q->where('name', $roleFilter))
            ->when($search !== '', fn ($q) => $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('name');

        $users = $usersQuery->paginate(15)->withQueryString();

        return view('role-permissions.index', [
            'modules' => $modules,
            'roles' => $roles,
            'selectedRoleId' => $selectedRoleId,
            'rolePermissions' => $rolePermissions,
            'users' => $users,
            'roleFilter' => $roleFilter,
            'search' => $search,
        ]);
    }

    public function updateModules(Request $request)
    {
        $data = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'modules' => 'nullable|array',
        ]);

        $roleId = (int) $data['role_id'];
        $allModules = array_keys(config('lms_modules', []));
        $allowedModules = array_values(array_intersect($allModules, array_keys($data['modules'] ?? [])));

        foreach ($allModules as $moduleKey) {
            RoleModulePermission::updateOrCreate(
                ['role_id' => $roleId, 'module_key' => $moduleKey],
                ['can_access' => in_array($moduleKey, $allowedModules, true)]
            );
        }

        return back()->with('status', 'Modul yetkileri guncellendi.');
    }

    public function assignRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->roles()->sync([(int) $data['role_id']]);

        return back()->with('status', 'Kullanici rolu guncellendi.');
    }
}
