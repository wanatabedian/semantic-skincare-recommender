<!-- resources/views/admin/login.blade.php -->
<h1>Login Admin GlowSemantics</h1>

<!-- Tempat menampilkan pesan error (password salah, dll) -->
@if ($errors->any())
    <div style="color: red;">
        {{ $errors->first() }}
    </div>
@endif

<form action="{{ url('/admin/login') }}" method="POST">
    @csrf
    <div style="margin-bottom: 10px;">
        <label>Email:</label><br>
        <input type="email" name="email" value="{{ old('email') }}" required>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Password:</label><br>
        <input type="password" name="password" required>
    </div>
    <button type="submit">Masuk</button>
</form>