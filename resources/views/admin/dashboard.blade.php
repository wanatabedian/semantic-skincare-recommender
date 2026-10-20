<!-- resources/views/admin/dashboard.blade.php -->
<h1>Selamat Datang di Dashboard Admin!</h1>
<p>Halo, {{ Auth::user()->name }}. Anda berhasil login.</p>

<form action="{{ route('logout') }}" method="POST">
    @csrf
    <button type="submit">Logout</button>
</form>