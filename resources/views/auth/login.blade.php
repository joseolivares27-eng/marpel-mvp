<x-layouts.mobile heading="Acceso Marpel" subheading="Tecnicos, administracion y gerencia" :nav="false">
    <section class="job-card">
        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" class="input" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" class="input" name="password" type="password" autocomplete="current-password" required>
            </div>
            <div class="field" style="display:flex;align-items:center;gap:10px">
                <input id="remember" name="remember" type="checkbox" value="1">
                <label for="remember" style="margin:0">Mantener sesion</label>
            </div>
            <button class="button full" type="submit">Entrar</button>
        </form>
    </section>
</x-layouts.mobile>
