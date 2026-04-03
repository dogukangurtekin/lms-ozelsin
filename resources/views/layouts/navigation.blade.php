<div class="h-full flex flex-col overflow-y-auto">
    <div class="lg:hidden px-4 pt-4 pb-2 border-b border-slate-200">
        <a href="{{ url('dashboard') }}" class="flex items-center gap-3 min-w-0">
            <img src="{{ asset('assets/logo.png') }}" alt="LMS Logo" class="h-10 w-10 rounded-lg bg-white p-1 border border-slate-200 object-contain">
            <div class="lms-page-title text-slate-800 whitespace-nowrap overflow-hidden text-ellipsis min-w-0 font-semibold">Özelsin Bilişim Sistemleri</div>
        </a>
    </div>
    <div class="px-4 py-4 text-xs uppercase tracking-wide text-slate-400">Navigasyon</div>

    <nav class="px-3 space-y-1 text-sm">
        @if(auth()->user()->canAccessModule('dashboard'))
            <a href="{{ url('dashboard') }}" class="lms-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
        @endif

        @if(auth()->user()->canAccessModule('assignments'))
            <a href="{{ url('assignments') }}" class="lms-nav-link {{ request()->routeIs('assignments.*') ? 'active' : '' }}">Ödev Yönetimi</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher']) && auth()->user()->canAccessModule('attendance'))
            <a href="{{ url('attendance') }}" class="lms-nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">Yoklama Modülü</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher']) && auth()->user()->canAccessModule('timetables'))
            <a href="{{ url('timetables') }}" class="lms-nav-link {{ request()->routeIs('timetables.*') ? 'active' : '' }}">Ders Programı</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher','parent','student']) && auth()->user()->canAccessModule('meetings'))
            <a href="{{ url('meetings') }}" class="lms-nav-link {{ request()->routeIs('meetings.*') ? 'active' : '' }}">Görüşmeler</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher']) && auth()->user()->canAccessModule('reports'))
            <a href="{{ url('reports') }}" class="lms-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">Raporlama</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher','student']) && auth()->user()->canAccessModule('books'))
            <a href="{{ url('books') }}" class="lms-nav-link {{ request()->routeIs('books.*') ? 'active' : '' }}">Kitap Yönetimi</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher']) && auth()->user()->canAccessModule('whatsapp'))
            <a href="{{ url('whatsapp') }}" class="lms-nav-link {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">Whatsapp Modülü</a>
        @endif

        @if(auth()->user()->hasRole('admin') && auth()->user()->canAccessModule('lessons'))
            <a href="{{ url('lessons') }}" class="lms-nav-link {{ request()->routeIs('lessons.*') ? 'active' : '' }}">Ders Ekleme Modülü</a>
        @endif

        @if(auth()->user()->hasRole('admin') && auth()->user()->canAccessModule('users'))
            <a href="{{ url('users') }}" class="lms-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">Kullanıcı Yönetimi</a>
        @endif

        @if(auth()->user()->hasRole('admin') && auth()->user()->canAccessModule('role_permissions'))
            <a href="{{ route('role-permissions.index') }}" class="lms-nav-link {{ request()->routeIs('role-permissions.*') ? 'active' : '' }}">Rol ve Modül Yetkileri</a>
        @endif

        {{-- Listede unutulabilecek diğer modüller en sonda --}}
        @if(auth()->user()->canAccessModule('dashboard'))
            <a href="{{ route('notifications.index') }}" class="lms-nav-link {{ request()->routeIs('notifications.*') || request()->routeIs('push.*') ? 'active' : '' }}">Bildirimler</a>
        @endif
    </nav>
</div>

