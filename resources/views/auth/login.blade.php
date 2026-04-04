<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - NEX-COMM</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-gray-700">

    <!-- Glass Card -->
    <div class="w-full max-w-md p-8 rounded-2xl backdrop-blur-lg bg-white/10 border border-white/20 shadow-2xl">

        <!-- Logo / Title -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-wide">NEX-COMM</h1>
            <p class="text-sm text-white/70 mt-2">Welcome back 👋</p>
        </div>

        <!-- Error -->
        @if($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-600/20 text-red-200 text-sm text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="/login" class="space-y-5">
            @csrf

            <!-- Email -->
            <div class="relative">
                <input 
                    type="email" 
                    name="email"
                    placeholder="Email address"
                    required
                    class="w-full px-4 py-3 rounded-lg bg-white/10 text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50"
                >
            </div>

            <!-- Password -->
            <div class="relative">
                <input 
                    id="password"
                    type="password" 
                    name="password"
                    placeholder="Password"
                    required
                    class="w-full px-4 py-3 rounded-lg bg-white/10 text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50 pr-12"
                >

                <!-- Toggle Button -->
                <button type="button" onclick="togglePassword()" 
                    class="absolute right-3 top-3 text-white/70 hover:text-white text-sm">
                    👁
                </button>
            </div>

            <!-- Remember -->
            <div class="flex items-center justify-between text-sm text-white/70">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="accent-white">
                    Remember me
                </label>

                <a href="#" class="hover:text-white">Forgot?</a>
            </div>

            <!-- Button -->
            <button 
                type="submit"
                class="w-full py-3 rounded-lg bg-white text-gray-900 font-semibold hover:bg-gray-100 transition duration-200 shadow-lg"
            >
                Sign In
            </button>
        </form>

        <!-- Footer -->
        <p class="text-center text-xs text-white/60 mt-6">
            © {{ date('Y') }} NEX-COMM. All rights reserved.
        </p>
    </div>

    <!-- JS -->
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>