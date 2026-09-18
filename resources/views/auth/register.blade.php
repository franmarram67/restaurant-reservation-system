<div>
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <input type="text" name="name" placeholder="Name" />
        @error('name')
            <div>{{ $message }}</div>
        @enderror
        <input type="email" name="email" placeholder="Email" />
        @error('email')
            <div>{{ $message }}</div>
        @enderror
        <input type="password" name="password" placeholder="Password" />
        @error('password')
            <div>{{ $message }}</div>
        @enderror
        <input type="password" name="password_confirmation" placeholder="Password Confirmation" />
        @error('password_confirmation')
            <div>{{ $message }}</div>
        @enderror
        <input type="submit" />
    </form>
</div>
