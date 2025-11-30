<?php
// ...existing code...
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Weather App</title>
	<meta name="viewport" content="width=device-width, initial-scale=1" />

	<!-- Tailwind CDN -->
	<script src="https://cdn.tailwindcss.com"></script>

	<!-- Custom style -->
	<link rel="stylesheet" href="assets/style.css" />

	<style>
		/* Global visuals */
		:root {
			--bg: #f8fbff;
			--card: #ffffff;
			--accent: #1976d2;
			--accent-2: #f5b400;
			--muted: #6b6b6b;
			--chip-bg: #f3f7ff;
			--accent-text: #0d47a1;
		}
		body {
			background: linear-gradient(180deg, #f7fbff 0%, #fff 100%);
			font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
			margin: 12px;
			color: #382a1a;
		}
		#weather-app {
			max-width: 1100px;
			margin: 20px auto;
			display: grid;
			grid-template-columns: 1fr 300px;
			gap: 18px;
		}
		.search-panel {
			background: var(--card);
			padding: 16px;
			border-radius: 12px;
			box-shadow: 0 10px 24px rgba(19, 26, 32, 0.06);
		}
		.search-row {
			display:flex;
			gap:10px;
			align-items:center;
		}
		#city-input {
			flex: 1;
			padding: 12px 14px;
			border-radius: 10px;
			border: 1px solid #e9eef6;
			font-size: 15px;
			background: #fcfeff;
		}
		button {
			border: 0;
			cursor: pointer;
			padding: 10px 12px;
			border-radius: 10px;
			font-weight: 700;
			letter-spacing: .2px;
		}
		#search-button {
			background: var(--accent);
			color: white;
			box-shadow: 0 6px 18px rgba(25,118,210,.12);
		}
		#save-favorite-button {
			background: linear-gradient(180deg,#e6f7ff,#dff6ff);
			color: var(--accent-text);
			font-weight: 700;
			padding: 9px 12px;
		}
		#status {
			margin-top: 10px;
			font-size: 14px;
			padding: 10px 12px;
			border-radius: 10px;
			background: #fff;
			border: 1px solid #f1f3f5;
			color: var(--muted);
			min-height: 40px;
			display:flex;
			align-items:center;
			gap: 10px;
		}
		.suggestion {
			padding: 8px 10px;
			cursor: pointer;
			border-radius: 8px;
			color:#0f1720;
		}
		.suggestion:hover { background: #f3f8ff; }
		.suggestions-list {
			margin-top: 10px;
			display: grid;
			gap:4px;
		}

		/* weather card */
		.weather-card { 
			margin-top: 14px;
			display:flex;
			gap:18px;
			align-items:center;
			padding: 22px;
			border-radius: 16px;
			background: linear-gradient(180deg,#fffefc,#fffdf9);
			box-shadow: 0 18px 40px rgba(53,64,71,.06);
		}
		.icon-box{ width:120px;height:120px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#fff,#fbfbff)}
		.icon-box img { width:80px; height:80px; display:block; }
		.city-area h2 { font-size: 26px; margin: 0; color:#6d3b12; }
		.city-area p { color: var(--muted); margin: 6px 0 10px; }
		.metrics{ display:flex; gap:8px; margin-top:8px; }
		.metric{ background: #fff; padding:8px 10px; border-radius: 10px; min-width:88px; text-align:center; font-weight:700; color:#4a4a4a; box-shadow: 0 8px 20px rgba(10,10,10,0.03); }
		.temp-area{ width:150px; text-align:right; }
		.temp-area h3{ font-size:44px; margin:0; color:#7a2f06; }
		.temp-area small{ color:#6b6b6b; display:block; margin-top:8px; font-weight:700; }

		/* favorites (sidebar) */
		.sidebar {
			background: var(--card);
			padding: 16px;
			border-radius: 12px;
			box-shadow: 0 10px 26px rgba(19, 26, 32, 0.06);
		}
		#favorites { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
		.favorite-chip {
			background: var(--chip-bg);
			padding: 8px 10px;
			border-radius: 12px;
			display:inline-flex;
			gap:8px;
			align-items:center;
			box-shadow: 0 6px 16px rgba(11,25,42,.03);
			cursor: pointer;
			border: 1px solid #e8f1ff;
			font-weight:700;
			color: var(--accent-text);
		}
		.favorite-chip .remove { background:#fff; color:#a33; border-radius: 100px; padding: 0 6px; font-weight:700; margin-left: 6px; cursor:pointer; border: 1px solid #ffdfe5; }

		/* animations / skeleton */
		.fade-in { animation: fadeIn .28s cubic-bezier(.2,.9,.4,1) both; }
		@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

		.skeleton { background: linear-gradient(90deg,#eee,#f6f6f6,#eee); background-size: 200% 100%; animation: shimmer 1.2s linear infinite; border-radius: 8px; }
		.skeleton-avatar{ width:120px;height:120px;border-radius:12px; display:inline-block; }
		.skeleton-line{ height:14px; border-radius:6px; display:block; }
		@keyframes shimmer { 0% { background-position: 200% 0 } 100% { background-position: -200% 0 } }

		/* responsive */
		@media (max-width: 900px) {
			#weather-app { grid-template-columns: 1fr; padding: 12px; }
			.temp-area { text-align:left; width:auto; }
			.icon-box, .skeleton-avatar { width:96px;height:96px; }
		}
	</style>
</head>
<body>
	<!-- header / brand -->
	<div style="max-width:1100px;margin: 10px auto;display:flex;align-items:center;gap:12px;">
		<div style="width:48px;height:48px;border-radius:50%;background: #ffe08a; display:flex;align-items:center;justify-content:center;font-weight:800;color:#6b3800;font-size:20px;">InilahMy</div>
		<h1 style="margin:0;font-size:22px;color:#6b3b08"> — Weather</h1>
	</div>

	<div id="weather-app">
		<!-- LEFT -->
		<div>
			<div class="search-panel">
				<div class="search-row">
					<input id="city-input" placeholder="Type a city — e.g., Tokyo, Paris" aria-label="City input"/>
					<button id="search-button">Search</button>
					<button id="save-favorite-button">Save Favorite</button>
				</div>
				<div class="suggestions-list" id="suggestions"></div>
				<div id="status" role="status" aria-live="polite"></div>
			</div>

			<!-- Current and forecast -->
			<div id="current" aria-live="polite" style="margin-top:18px;"></div>
			<div id="forecast"></div>
		</div>

		<!-- RIGHT: favorites -->
		<div class="sidebar" aria-label="Favorites">
			<h3 style="margin:0 0 6px 0">Favorites</h3>
			<div id="favorites"></div>
		</div>
	</div>

	<!-- include the app script -->
	<script src="assets/app.js"></script>
</body>
</html>