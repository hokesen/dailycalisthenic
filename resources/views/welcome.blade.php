<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Daily Calisthenic</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html, body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                font-family: 'Inter', sans-serif;
                min-height: 100vh;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
            }

            .top-nav {
                display: flex;
                justify-content: flex-end;
                padding: 20px 0;
            }

            .top-nav a {
                color: #fff;
                text-decoration: none;
                margin-left: 30px;
                font-weight: 500;
                opacity: 0.9;
                transition: opacity 0.3s;
            }

            .top-nav a:hover {
                opacity: 1;
            }

            .hero {
                text-align: center;
                padding: 60px 20px 40px;
            }

            .hero h1 {
                font-size: 56px;
                font-weight: 700;
                margin-bottom: 20px;
                line-height: 1.2;
            }

            .hero .subtitle {
                font-size: 24px;
                font-weight: 300;
                margin-bottom: 50px;
                opacity: 0.95;
            }

            .benefits {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 30px;
            }

            .benefit-card {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                border-radius: 15px;
                padding: 30px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                transition: transform 0.3s, background 0.3s;
            }

            .benefit-card:hover {
                transform: translateY(-5px);
                background: rgba(255, 255, 255, 0.15);
            }

            .benefit-card .icon {
                font-size: 48px;
                margin-bottom: 15px;
            }

            .benefit-card h3 {
                font-size: 22px;
                font-weight: 600;
                margin-bottom: 10px;
            }

            .benefit-card p {
                font-size: 16px;
                line-height: 1.6;
                opacity: 0.9;
                font-weight: 300;
            }

            @media (max-width: 768px) {
                .hero h1 {
                    font-size: 36px;
                }

                .hero .subtitle {
                    font-size: 18px;
                }

                .benefits {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            @if (Route::has('login'))
                <div class="top-nav">
                    @auth
                        <a href="{{ url('/dashboard') }}">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}">Login</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="hero">
                <h1>Daily Calisthenic</h1>
                <p class="subtitle">by Simon Hokesen</p>
            </div>

            <div class="benefits">
                <div class="benefit-card">
                    <div class="icon">💪</div>
                    <h3>Build Real Strength</h3>
                    <p>Develop functional strength using your own bodyweight. No equipment needed, just dedication and consistency.</p>
                </div>

                <div class="benefit-card">
                    <div class="icon">🎯</div>
                    <h3>Daily Progress</h3>
                    <p>Track your journey with daily exercises. Small, consistent actions lead to remarkable transformations.</p>
                </div>

                <div class="benefit-card">
                    <div class="icon">🌟</div>
                    <h3>Flexible & Free</h3>
                    <p>Train anywhere, anytime. No gym membership required. Your body is the only equipment you need.</p>
                </div>

                <div class="benefit-card">
                    <div class="icon">🔥</div>
                    <h3>Stay Motivated</h3>
                    <p>Build a sustainable habit with structured daily routines designed to keep you engaged and progressing.</p>
                </div>

                <div class="benefit-card">
                    <div class="icon">🧘</div>
                    <h3>Mind & Body</h3>
                    <p>Improve not just physical strength, but mental resilience, focus, and overall well-being.</p>
                </div>

                <div class="benefit-card">
                    <div class="icon">📈</div>
                    <h3>Measurable Results</h3>
                    <p>See your progress with clear metrics and achievements as you master new movements and techniques.</p>
                </div>
            </div>
        </div>
    </body>
</html>
