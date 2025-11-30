document.addEventListener('DOMContentLoaded', () => {
	// -------------------------
	// Weather Dashboard JS
	// -------------------------

	const cities = ["Tokyo","Paris","London","Jakarta","New York"]; // daftar kota statis
	let favorites = JSON.parse(localStorage.getItem('favorites')) || [];

	// DOM Elements
	const input = document.getElementById('city-input');
	const suggestionsDiv = document.getElementById('suggestions');
	const favoritesDiv = document.getElementById('favorites');
	const statusDiv = document.getElementById('status');
	const currentDiv = document.getElementById('current');
	const forecastDiv = document.getElementById('forecast');
	const searchBtn = document.getElementById('search-button');
	const saveFavBtn = document.getElementById('save-favorite-button');

	let lastCity = '';
	let lastCurrent = null;

	// --- UI helpers ---
	function setStatus(msg, type = 'info') {
		if (!statusDiv) return;
		statusDiv.textContent = msg || '';
		statusDiv.classList.remove('status--info', 'status--error');
		// type can be 'info' | 'error' | 'clear'
		if (type === 'error') statusDiv.classList.add('status--error');
		else if (type === 'info') statusDiv.classList.add('status--info');
	}

	// skeleton rendering while loading
	function showSkeleton() {
		if (!currentDiv) return;
		currentDiv.innerHTML = `
			<div class="weather-card">
				<div class="skeleton-avatar skeleton"></div>
				<div style="flex:1;min-width:140px;">
					<div style="width:40%;height:18px;margin-bottom:8px;" class="skeleton-line skeleton"></div>
					<div style="width:60%;height:12px;margin-bottom:12px;" class="skeleton-line skeleton"></div>
					<div style="display:flex;gap:8px;">
						<div style="width:86px;height:44px;" class="skeleton-line skeleton"></div>
						<div style="width:86px;height:44px;" class="skeleton-line skeleton"></div>
						<div style="width:86px;height:44px;" class="skeleton-line skeleton"></div>
					</div>
				</div>
				<div style="width:130px; text-align:right;">
					<div style="width:100%;height:40px;margin-bottom:10px;" class="skeleton-line skeleton"></div>
					<div style="width:80%;height:12px;" class="skeleton-line skeleton"></div>
				</div>
			</div>
		`;
	}

	// nicer render for favorites: chips with remove
	function renderFavorites() {
		favoritesDiv.innerHTML = '';
		favorites.forEach(city => {
			const chip = document.createElement('button');
			chip.setAttribute('type','button');
			chip.className = 'favorite-chip fade-in';
			chip.innerHTML = `${city} <span class="remove" title="Remove">×</span>`;
			chip.onclick = (e) => {
				// If remove clicked, don't load
				if (e.target.classList.contains('remove')) return;
				loadWeather(city);
			};
			// remove handler
			chip.querySelector('.remove').onclick = (ev) => {
				ev.stopPropagation();
				removeFavorite(city);
			};
			favoritesDiv.appendChild(chip);
		});
		// disable Save button if lastCity is already in favorites
		updateSaveBtnState();
	}

	function updateSaveBtnState() {
		if (!saveFavBtn) return;
		if (!lastCity) { saveFavBtn.disabled = true; saveFavBtn.style.opacity = '.6'; return; }
		if (favorites.includes(lastCity)) {
			saveFavBtn.disabled = true;
			saveFavBtn.textContent = 'Saved';
			saveFavBtn.style.opacity = '.8';
		} else {
			saveFavBtn.disabled = false;
			saveFavBtn.textContent = 'Save Favorite';
			saveFavBtn.style.opacity = '1';
		}
	}

	function addFavorite(city) {
		if (!favorites.includes(city)) {
			favorites.push(city);
			localStorage.setItem('favorites', JSON.stringify(favorites));
			renderFavorites();
			setStatus(`${city} added to favorites`);
			// small animation hint
			const lastChip = favoritesDiv.lastElementChild;
			if (lastChip) lastChip.classList.add('fade-in');
		}
		updateSaveBtnState();
	}

	function removeFavorite(city) {
		const idx = favorites.indexOf(city);
		if (idx === -1) return;
		favorites.splice(idx, 1);
		localStorage.setItem('favorites', JSON.stringify(favorites));
		renderFavorites();
		setStatus(`${city} removed from favorites`);
	}

	// -------------------------
	// Auto-complete
	// -------------------------
	if (input) {
		input.addEventListener('input', () => {
			const val = input.value.toLowerCase();
			suggestionsDiv.innerHTML = '';
			if (!val) return;

			const matches = cities.filter(c => c.toLowerCase().startsWith(val));
			matches.forEach(city => {
				const div = document.createElement('div');
				div.textContent = city;
				div.classList.add('suggestion');
				div.onclick = () => {
					input.value = city;
					suggestionsDiv.innerHTML = '';
					loadWeather(city);
				};
				suggestionsDiv.appendChild(div);
			});
		});

		// NEW: Press Enter to search typed city
		input.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') {
				e.preventDefault();
				const city = input.value.trim();
				if (city) {
					suggestionsDiv.innerHTML = ''; // clear suggestions on enter
					loadWeather(city);
				}
			}
		});
	}

	// short-circuit: attach search and save listeners if present
	if (searchBtn) {
		searchBtn.addEventListener('click', () => {
			const city = input.value.trim();
			if (city) {
				suggestionsDiv.innerHTML = '';
				loadWeather(city);
			}
		});
	}
	if (saveFavBtn) {
		saveFavBtn.addEventListener('click', () => {
			if (lastCity) addFavorite(lastCity);
		});
	}

	// -------------------------
	// Fetch Weather
	// -------------------------
	function loadWeather(city) {
		if (!city) return;
		// Keep input updated (in case triggered via external search)
		if (input && input.value !== city) input.value = city;

		setStatus('Loading weather...', 'info');
		showSkeleton(); // show skeleton while fetching
		forecastDiv.innerHTML = '';

		fetch(`api/weather.php?city=${encodeURIComponent(city)}`)
			.then(res => {
				const ct = (res.headers.get('content-type') || '').toLowerCase();
				if (!res.ok) throw new Error(`HTTP ${res.status}`);
				if (!ct.includes('application/json')) {
					return res.text().then(t => { throw new Error('Expected JSON, got: ' + t); });
				}
				return res.json();
			})
			.then(data => {
				if (data.error) throw new Error(data.error);
				renderCurrent(data.current);
				renderForecast(data.forecast);
				setStatus(`Weather loaded for ${data.current.city}, ${data.current.country}`, 'info');
			})
			.catch(err => {
				// show user friendly message
				setStatus('Weather load failed: ' + err.message, 'error');
			});
	}
	// expose loadWeather in case other scripts or buttons call it
	window.loadWeather = loadWeather;

	// -------------------------
	// Render Current Weather
	// -------------------------
	function renderCurrent(current) {
		lastCity = current.city;
		lastCurrent = current;
		updateSaveBtnState();

		currentDiv.classList.add('fade-in');
		currentDiv.innerHTML = `
			<div class="weather-card">
				<div class="icon-box">
					<img src="https://openweathermap.org/img/wn/${current.weather.icon}@4x.png" alt="${current.weather.description}">
				</div>
				<div class="city-area">
					<h2>${current.city}, ${current.country}</h2>
					<p>${current.weather.description}</p>
					<div class="metrics" aria-hidden="true">
						<div class="metric"><small>Humidity</small>${current.humidity ?? '--'}%</div>
						<div class="metric"><small>Wind</small>${current.wind_speed ?? '--'} m/s</div>
						<div class="metric"><small>Pressure</small>${current.pressure ?? '--'} hPa</div>
					</div>
				</div>
				<div class="temp-area">
					<h3>${Number(current.temp).toFixed(1)}°C</h3>
					<small>Feels like ${Number(current.feels_like).toFixed(1)}°C</small>
					<div style="margin-top:12px;">
						<button style="background:#fff0d6;border-radius:10px;border:0;padding:8px 10px;color:#8a4b0e;cursor:pointer;" onclick="addFavorite('${escapeHtml(current.city)}')">⭐ Add to Favorites</button>
					</div>
				</div>
			</div>
		`;
	}

	// simple escape helper used when embedding string into onClick HTML attribute
	function escapeHtml(str = '') {
		return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
	}

	// -------------------------
	// Render Forecast (5-day)
	// -------------------------
	function renderForecast(forecast) {
		forecastDiv.innerHTML = '';
		const wrapper = document.createElement('div');
		wrapper.style.display = 'flex';
		wrapper.style.gap = '12px';
		wrapper.style.marginTop = '14px';
		wrapper.style.flexWrap = 'wrap';

		forecast.forEach(day => {
			const card = document.createElement('div');
			card.classList.add('card');
			card.style.minWidth = '140px';
			card.style.textAlign = 'center';
			card.innerHTML = `
				<div style="font-weight:700">${day.date}</div>
				<img style="width:64px;height:64px;margin:6px 0;" src="https://openweathermap.org/img/wn/${day.icon}@2x.png" alt="${day.description}">
				<div style="font-size:14px;color:#6b6b6b;">${day.description}</div>
				<div style="margin-top:6px;font-weight:700;">${day.min}° / ${day.max}°</div>
			`;
			wrapper.appendChild(card);
		});
		forecastDiv.appendChild(wrapper);
	}

	// -------------------------
	// Optional: auto-refresh every 5 minutes
	// -------------------------
	setInterval(() => {
		const city = input.value || (favorites[0] || '');
		if (city) loadWeather(city);
	}, 5 * 60 * 1000);

	// Optional: load the first favorite or a default city on startup
	if (!input.value) {
		const defaultCity = favorites[0] || 'Jakarta';
		input.value = defaultCity;
		loadWeather(defaultCity);
	}
	renderFavorites();
});