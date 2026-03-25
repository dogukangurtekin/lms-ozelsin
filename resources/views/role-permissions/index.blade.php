<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Rol ve Modul Yetkileri</h2></x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 text-sm">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700 text-sm">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800 mb-3">Modul Kontrolu</h3>
                <form method="GET" action="{{ route('role-permissions.index') }}" class="flex items-end gap-3 mb-3">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Rol</label>
                        <select name="role_id" class="rounded-lg border-slate-300 w-full sm:w-auto sm:min-w-[240px]" onchange="this.form.submit()">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected($selectedRoleId === $role->id)>{{ $role->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <form method="POST" action="{{ route('role-permissions.modules.update') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $selectedRoleId }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
                        @foreach($modules as $key => $label)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <input type="checkbox" name="modules[{{ $key }}]" value="1" @checked(($rolePermissions[$key] ?? true) === true)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Modul Yetkilerini Kaydet</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="font-semibold text-slate-800 mb-3">Kullanici Rol Atama</h3>
                <form method="GET" action="{{ route('role-permissions.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end mb-4">
                    <input type="hidden" name="role_id" value="{{ $selectedRoleId }}">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kullanici Tipi</label>
                        <select name="user_role" class="rounded-lg border-slate-300 w-full">
                            <option value="teacher" @selected($roleFilter==='teacher')>Ogretmen</option>
                            <option value="parent" @selected($roleFilter==='parent')>Veli</option>
                            <option value="student" @selected($roleFilter==='student')>Ogrenci</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Ara</label>
                        <input type="text" name="q" value="{{ $search }}" class="rounded-lg border-slate-300 w-full" placeholder="Ad, e-posta, telefon">
                    </div>
                    <button class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-semibold">Filtrele</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="p-2">Ad</th>
                                <th class="p-2">E-posta</th>
                                <th class="p-2">Telefon</th>
                                <th class="p-2">Mevcut Rol</th>
                                <th class="p-2">Yeni Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr class="border-b">
                                    <td class="p-2">{{ $user->name }}</td>
                                    <td class="p-2">{{ $user->email }}</td>
                                    <td class="p-2">{{ $user->phone ?? '-' }}</td>
                                    <td class="p-2">{{ $user->roles->first()?->label ?? '-' }}</td>
                                    <td class="p-2">
                                        <form method="POST" action="{{ route('role-permissions.assign-role', $user) }}" class="flex gap-2">
                                            @csrf
                                            <select name="role_id" class="rounded border-slate-300 text-sm">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->id }}" @selected($user->roles->first()?->id === $role->id)>{{ $role->label }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded bg-emerald-600 text-white px-3 py-1 text-xs font-semibold">Ata</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-2">Kullanici bulunamadi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $users->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
