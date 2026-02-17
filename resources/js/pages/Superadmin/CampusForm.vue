<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { MapPin, ArrowLeft, Save } from 'lucide-vue-next';
import SuperadminLayout from './Layout.vue';
import 'leaflet/dist/leaflet.css';

interface CampusRow {
    id: number;
    name: string;
    code: string | null;
    base_latitude: number | null;
    base_longitude: number | null;
}

const props = defineProps<{
    campus: CampusRow | null;
}>();

const isEdit = computed(() => props.campus != null);

const form = useForm({
    name: props.campus?.name ?? '',
    code: props.campus?.code ?? '',
    base_latitude: props.campus?.base_latitude ?? '',
    base_longitude: props.campus?.base_longitude ?? '',
});

const mapEl = ref<HTMLDivElement | null>(null);
let leaflet: any | null = null;
let map: any | null = null;
let marker: any | null = null;

type SearchResult = {
    place_id: string | number;
    display_name: string;
    lat: string;
    lon: string;
};

const searchQuery = ref('');
const searchResults = ref<SearchResult[]>([]);
const searchLoading = ref(false);
const searchError = ref<string | null>(null);
let searchDebounce: ReturnType<typeof setTimeout> | null = null;

function toNumber(v: unknown): number | null {
    const n = typeof v === 'number' ? v : typeof v === 'string' ? parseFloat(v) : NaN;
    return Number.isFinite(n) ? n : null;
}

function setPoint(lat: number, lng: number) {
    form.base_latitude = lat.toFixed(6);
    form.base_longitude = lng.toFixed(6);
    if (leaflet && map) {
        if (!marker) {
            marker = leaflet.marker([lat, lng]).addTo(map);
        } else {
            marker.setLatLng([lat, lng]);
        }
    }
}

function setMapView(lat: number, lng: number, zoom = 17) {
    map?.setView([lat, lng], zoom, { animate: true });
}

async function runSearch(q: string) {
    const query = q.trim();
    if (query.length < 3) {
        searchResults.value = [];
        searchError.value = null;
        return;
    }
    searchLoading.value = true;
    searchError.value = null;
    try {
        // Nominatim (OpenStreetMap) geocoding
        const url = new URL('https://nominatim.openstreetmap.org/search');
        url.searchParams.set('format', 'json');
        url.searchParams.set('limit', '6');
        url.searchParams.set('q', query);
        const res = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
            },
        });
        if (!res.ok) {
            searchError.value = 'Search failed.';
            searchResults.value = [];
            return;
        }
        const json = (await res.json()) as SearchResult[];
        searchResults.value = Array.isArray(json) ? json : [];
    } catch (e) {
        searchError.value = 'Search failed.';
        searchResults.value = [];
    } finally {
        searchLoading.value = false;
    }
}

function onSearchInput() {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        void runSearch(searchQuery.value);
    }, 450);
}

function selectSearchResult(r: SearchResult) {
    const lat = toNumber(r.lat);
    const lng = toNumber(r.lon);
    if (lat == null || lng == null) return;
    setMapView(lat, lng, 18);
    // Selecting a result also drops/updates the pin (still map-based, not manual typing)
    setPoint(lat, lng);
    searchResults.value = [];
}

function submit() {
    if (isEdit.value && props.campus) {
        form.put(`/superadmin/campuses/${props.campus.id}`);
    } else {
        form.post('/superadmin/campuses');
    }
}

onMounted(async () => {
    if (!mapEl.value) return;

    // Lazy-load Leaflet to avoid SSR/window issues
    const mod = await import('leaflet');
    leaflet = (mod as any).default ?? mod;

    // Fix default marker icon URLs under Vite
    const [iconRetinaUrl, iconUrl, shadowUrl] = await Promise.all([
        import('leaflet/dist/images/marker-icon-2x.png'),
        import('leaflet/dist/images/marker-icon.png'),
        import('leaflet/dist/images/marker-shadow.png'),
    ]);
    delete (leaflet.Icon.Default.prototype as any)._getIconUrl;
    leaflet.Icon.Default.mergeOptions({
        iconRetinaUrl: (iconRetinaUrl as any).default,
        iconUrl: (iconUrl as any).default,
        shadowUrl: (shadowUrl as any).default,
    });

    const existingLat = toNumber(form.base_latitude);
    const existingLng = toNumber(form.base_longitude);

    const defaultCenter: [number, number] = [12.8797, 121.774]; // PH fallback
    const defaultZoom = 6;

    map = leaflet.map(mapEl.value, {
        zoomControl: true,
    }).setView(
        existingLat != null && existingLng != null ? ([existingLat, existingLng] as [number, number]) : defaultCenter,
        existingLat != null && existingLng != null ? 17 : defaultZoom
    );

    leaflet
        .tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 20,
        })
        .addTo(map);

    map.on('click', (e: any) => {
        const lat = e?.latlng?.lat;
        const lng = e?.latlng?.lng;
        if (typeof lat === 'number' && typeof lng === 'number') {
            setPoint(lat, lng);
        }
    });

    // If no existing point, try to center on current device location (no auto-set)
    if (existingLat == null || existingLng == null) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map?.setView([lat, lng], 16);
                },
                () => {
                    // ignore
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 300000 }
            );
        }
    } else {
        // Show marker for existing campus base
        marker = leaflet.marker([existingLat, existingLng]).addTo(map);
    }
});

onBeforeUnmount(() => {
    if (searchDebounce) {
        clearTimeout(searchDebounce);
        searchDebounce = null;
    }
    if (map) {
        map.off();
        map.remove();
        map = null;
    }
    marker = null;
    leaflet = null;
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Campus - Superadmin' : 'Add Campus - Superadmin'" />
    <SuperadminLayout>
        <div class="p-6 max-w-2xl">
            <div class="mb-6">
                <a
                    href="/superadmin/campuses"
                    class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 text-sm font-medium"
                >
                    <ArrowLeft class="w-4 h-4" />
                    Back to Campuses
                </a>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                {{ isEdit ? 'Edit Campus' : 'Add Campus' }}
            </h1>
            <p class="text-gray-600 mb-6">
                Set name, optional code, and base location (lat/long) for Find Your Match.
            </p>

            <form @submit.prevent="submit" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g. Main Campus"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code (optional)</label>
                    <input
                        id="code"
                        v-model="form.code"
                        type="text"
                        class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g. MAIN"
                    />
                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Base location *</label>
                            <p class="text-xs text-gray-500 mt-0.5">Click the map to drop a pin (no manual input).</p>
                        </div>
                        <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600">
                            <MapPin class="w-4 h-4 text-rose-500" />
                            Point on map
                        </div>
                    </div>

                    <!-- Search location -->
                    <div class="mb-3">
                        <label for="campus-map-search" class="block text-sm font-medium text-gray-700 mb-1">Search location</label>
                        <div class="relative">
                            <input
                                id="campus-map-search"
                                v-model="searchQuery"
                                type="text"
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Search a place/address…"
                                @input="onSearchInput"
                            />
                            <div v-if="searchLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <div class="w-4 h-4 border-2 border-gray-300 border-t-blue-500 rounded-full animate-spin" />
                            </div>
                        </div>
                        <p v-if="searchError" class="mt-1 text-sm text-amber-600">{{ searchError }}</p>

                        <div
                            v-if="searchResults.length"
                            class="mt-2 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden"
                        >
                            <button
                                v-for="r in searchResults"
                                :key="r.place_id"
                                type="button"
                                class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors"
                                @click="selectSearchResult(r)"
                            >
                                {{ r.display_name }}
                            </button>
                        </div>
                        <p v-else-if="searchQuery.trim().length >= 3 && !searchLoading" class="mt-2 text-xs text-gray-500">
                            No results. Try a more specific search.
                        </p>
                    </div>

                    <div class="rounded-2xl overflow-hidden border border-gray-200 bg-gray-50">
                        <div ref="mapEl" class="h-[320px] w-full" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="base_latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input
                                id="base_latitude"
                                v-model="form.base_latitude"
                                type="text"
                                readonly
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 bg-gray-50 text-gray-700"
                                placeholder="Click the map"
                            />
                            <p v-if="form.errors.base_latitude" class="mt-1 text-sm text-red-600">{{ form.errors.base_latitude }}</p>
                        </div>
                        <div>
                            <label for="base_longitude" class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input
                                id="base_longitude"
                                v-model="form.base_longitude"
                                type="text"
                                readonly
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 bg-gray-50 text-gray-700"
                                placeholder="Click the map"
                            />
                            <p v-if="form.errors.base_longitude" class="mt-1 text-sm text-red-600">{{ form.errors.base_longitude }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 disabled:opacity-50 text-white font-semibold rounded-full shadow-lg transition-all"
                    >
                        <Save class="w-4 h-4" />
                        {{ isEdit ? 'Update' : 'Create' }}
                    </button>
                    <a
                        href="/superadmin/campuses"
                        class="px-4 py-2.5 text-gray-600 hover:text-gray-900 font-medium"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </SuperadminLayout>
</template>
