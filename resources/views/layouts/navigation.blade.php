<div class="h-full flex flex-col overflow-y-auto">
    <div class="h-16 px-5 flex items-center border-b border-slate-200">
        <a href="{{ url('dashboard') }}" class="flex items-center gap-3">
            <img src="{{ asset('assets/logo.png') }}" alt="LMS Logo" class="h-10 w-10 rounded-lg bg-white p-1 border border-slate-200 object-contain">
            <div>
                <p class="text-sm font-bold tracking-tight text-slate-800 leading-none">OzelSin LMS</p>
                <p class="text-[11px] text-slate-500 mt-1">Egitim Yonetimi</p>
            </div>
        </a>
    </div>

    <div class="px-4 py-4 text-xs uppercase tracking-wide text-slate-400">Navigasyon</div>

    <nav class="px-3 space-y-1 text-sm">
        @if(auth()->user()->canAccessModule('dashboard'))
            <a href="{{ url('dashboard') }}" class="lms-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher','student']) && auth()->user()->canAccessModule('books'))
            <a href="{{ url('books') }}" class="lms-nav-link {{ request()->routeIs('books.*') ? 'active' : '' }}">Kitap Yonetimi</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher','parent','student']) && auth()->user()->canAccessModule('meetings'))
            <a href="{{ url('meetings') }}" class="lms-nav-link {{ request()->routeIs('meetings.*') ? 'active' : '' }}">Gorusmeler</a>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher']))
            @if(auth()->user()->canAccessModule('whatsapp'))
                <a href="{{ url('whatsapp') }}" class="lms-nav-link {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">Whatsapp Modulu</a>
            @endif
            @if(auth()->user()->canAccessModule('reports'))
                <a href="{{ url('reports') }}" class="lms-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">Raporlama</a>
            @endif
            @if(auth()->user()->canAccessModule('attendance'))
                <a href="{{ url('attendance') }}" class="lms-nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}">Yoklama Modulu</a>
            @endif
            @if(auth()->user()->canAccessModule('timetables'))
                <a href="{{ url('timetables') }}" class="lms-nav-link {{ request()->routeIs('timetables.*') ? 'active' : '' }}">Ders Programi</a>
            @endif
        @endif

        @if(auth()->user()->canAccessModule('assignments'))
            <a href="{{ url('assignments') }}" class="lms-nav-link {{ request()->routeIs('assignments.*') ? 'active' : '' }}">Odev Yonetimi</a>
        @endif

        @if(auth()->user()->hasRole('admin'))
            @if(auth()->user()->canAccessModule('lessons'))
                <a href="{{ url('lessons') }}" class="lms-nav-link {{ request()->routeIs('lessons.*') ? 'active' : '' }}">Ders Ekleme Modulu</a>
            @endif
            @if(auth()->user()->canAccessModule('users'))
                <a href="{{ url('users') }}" class="lms-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">Kullanici Yonetimi</a>
            @endif
            @if(auth()->user()->canAccessModule('role_permissions'))
                <a href="{{ route('role-permissions.index') }}" class="lms-nav-link {{ request()->routeIs('role-permissions.*') ? 'active' : '' }}">Rol ve Modul Yetkileri</a>
            @endif
        @endif
    </nav>

    <div class="mt-auto p-4 border-t border-slate-200 space-y-2">
        <a href="{{ url('profile') }}" class="block text-sm text-slate-600 hover:text-slate-900">Profil Ayarlari</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full rounded-lg bg-slate-900 text-white px-3 py-2 text-sm">Cikis Yap</button>
        </form>
    </div>
</div>
