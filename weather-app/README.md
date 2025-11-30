# Ghibli-Inspired Anime Weather Dashboard (PHP + JS + TailwindCSS)

Quick start (XAMPP / Apache):

1. Place the `weather-app` folder inside your XAMPP `htdocs` directory:
   - e.g., `C:\xampp\htdocs\weather-app` or `d:\xampp\htdocs\weather-app` depending on installation.

2. Open `api/weather.php` and set your OpenWeatherMap API key:
   - Replace `YOUR_API_KEY_HERE` with a valid API key, OR set the environment variable `OPENWEATHER_API_KEY` in Apache / system environment.

3. Start Apache in XAMPP.

4. Open a browser and go to:
   - `http://localhost/weather-app/index.php`

5. Use the search bar to search for cities (autocomplete helps). Save favorites, toggle Celsius/Fahrenheit and Light/Dark mode. The app auto-refreshes every 5 minutes.

Notes:
- The PHP backend proxies OpenWeatherMap data (current and 5-day forecast) for easier client usage.
- The app uses TailwindCSS via CDN and custom CSS for watercolor-style and animations.
- All favorites/settings are stored in the browser's localStorage.

Enjoy the Ghibli-like weather experience!
