<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set your password</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f5f6f7; color: #1f2937; margin: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 1rem; }
        .card { width: 100%; max-width: 420px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { color: #6b7280; font-size: .9rem; margin: 0 0 1.25rem; }
        label { display: block; font-size: .85rem; font-weight: 600; margin: 0 0 .35rem; }
        input { width: 100%; box-sizing: border-box; padding: .6rem .75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: .95rem; background: #fff; color: #1f2937; }
        input:focus { outline: 2px solid #0891b2; border-color: #0891b2; }
        input[readonly] { background: #f3f4f6; color: #6b7280; cursor: not-allowed; }
        .field { margin-bottom: 1rem; }
        button { width: 100%; padding: .65rem; background: #0891b2; color: #fff; border: 0; border-radius: 8px; font-size: .95rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #0e7490; }
        .status { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: .6rem .75rem; border-radius: 8px; font-size: .85rem; margin-bottom: 1.25rem; }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: .6rem .75rem; border-radius: 8px; font-size: .85rem; margin-bottom: 1.25rem; }
        ul.errors { list-style: none; padding: 0; margin: 0 0 1.25rem; color: #991b1b; font-size: .85rem; }
        ul.errors li { margin-bottom: .25rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Set your password</h1>
        <p>Choose a password for your Quatriz TimeSheet account.</p>

        @if (session('status'))
            <div class="status">{{ __(session('status')) }}</div>
        @endif

        @if ($errors->any())
            <ul class="errors">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" readonly>
            </div>

            <div class="field">
                <label for="password">New password</label>
                <input id="password" type="password" name="password" required autofocus>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>
            </div>

            <button type="submit">Set password</button>
        </form>
    </div>
</body>
</html>