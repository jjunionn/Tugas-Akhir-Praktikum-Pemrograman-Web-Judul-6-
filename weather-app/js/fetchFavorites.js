// Caller: import { fetchFavorites } from './js/fetchFavorites.js';
// Then: fetchFavorites().then(favs => { /* use favs */ }).catch(err => console.error(err));

export async function fetchFavorites() {
  const url = '/weather-app/api/favorites.php'; // Use the API wrapper endpoint
  try {
    const res = await fetch(url, { credentials: 'same-origin' });
    const ct = (res.headers.get('content-type') || '').toLowerCase();
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`HTTP ${res.status} ${res.statusText}: ${text}`);
    }
    if (!ct.includes('application/json')) {
      const text = await res.text();
      const excerpt = text.length > 500 ? text.slice(0, 500) + '…' : text;
      throw new Error(`Expected JSON, received Content-Type '${ct}'. Response excerpt: ${excerpt}`);
    }
    return res.json();
  } catch (err) {
    // log for debugging; return empty array as fallback so UI doesn't crash
    console.error('Failed to fetch favorites:', err);
    return [];
  }
}
