<script setup lang="ts">
import { TYPE_COLORS, TYPE_LABELS } from '@/lib/format';
import type { LocationType, SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

export interface MapMarker {
    id: number;
    lat: number;
    lng: number;
    title: string;
    subtitle?: string;
    summary?: string;
    href?: string;
    type: LocationType;
    label?: string | number;
}

const props = withDefaults(
    defineProps<{ markers: MapMarker[]; selectedId?: number | null; cluster?: boolean; route?: boolean; label?: string; zoomOnSingle?: number }>(),
    { cluster: false, route: false, selectedId: null, label: 'Map of literary places', zoomOnSingle: 13 },
);
const emit = defineEmits<{ select: [id: number] }>();

const page = usePage<SharedData>();
const el = ref<HTMLDivElement | null>(null);
const loading = ref(true);
const tileFailed = ref(false);

let map: L.Map | null = null;
let layerGroup: L.LayerGroup | L.MarkerClusterGroup | null = null;
let line: L.Polyline | null = null;
let resizeObserver: ResizeObserver | null = null;
const byId = new Map<number, L.Marker>();

function icon(m: MapMarker, active: boolean) {
    return L.divIcon({
        className: `la-pin${active ? ' is-active' : ''}`,
        html: `<span style="background:${TYPE_COLORS[m.type]}"><b>${m.label ?? ''}</b></span>`,
        iconSize: [30, 30],
        iconAnchor: [15, 30],
        popupAnchor: [0, -28],
    });
}

function popupContent(m: MapMarker) {
    const root = document.createElement('div');
    root.className = 'min-w-[12rem] max-w-[16rem]';
    const t = document.createElement('p');
    t.style.cssText = 'font-weight:600;font-size:14px;margin:0';
    t.textContent = m.title;
    const sub = document.createElement('p');
    sub.style.cssText = 'margin:2px 0 6px;color:#555;font-size:12px';
    sub.textContent = [TYPE_LABELS[m.type], m.subtitle].filter(Boolean).join(' · ');
    root.append(t, sub);
    if (m.summary) {
        const s = document.createElement('p');
        s.style.cssText = 'margin:0 0 8px;font-size:12px;line-height:1.4';
        s.textContent = m.summary;
        root.append(s);
    }
    if (m.href) {
        const a = document.createElement('a');
        a.href = m.href;
        a.textContent = 'Open place details →';
        a.style.cssText = 'font-weight:600;font-size:12px;color:#a8472f';
        a.addEventListener('click', (e) => {
            e.preventDefault();
            router.visit(m.href!);
        });
        root.append(a);
    }
    return root;
}

function render() {
    if (!map) return;
    layerGroup?.remove();
    line?.remove();
    byId.clear();

    layerGroup = props.cluster ? L.markerClusterGroup({ maxClusterRadius: 45, showCoverageOnHover: false }) : L.layerGroup();
    for (const m of props.markers) {
        const marker = L.marker([m.lat, m.lng], { icon: icon(m, m.id === props.selectedId), title: m.title, alt: m.title, keyboard: true });
        marker.bindPopup(() => popupContent(m));
        marker.on('click', () => emit('select', m.id));
        marker.addTo(layerGroup);
        byId.set(m.id, marker);
    }
    layerGroup.addTo(map);

    if (props.route && props.markers.length > 1) {
        line = L.polyline(
            props.markers.map((m) => [m.lat, m.lng] as [number, number]),
            { color: '#a8472f', weight: 3, opacity: 0.8, dashArray: '6 6' },
        ).addTo(map);
    }

    fit();
}

function fit() {
    if (!map) return;
    if (!props.markers.length) {
        map.setView([30, 0], 2);
    } else if (props.markers.length === 1) {
        map.setView([props.markers[0].lat, props.markers[0].lng], props.zoomOnSingle);
    } else {
        map.fitBounds(L.latLngBounds(props.markers.map((m) => [m.lat, m.lng] as [number, number])), { padding: [32, 32], maxZoom: 12 });
    }
}

function focusSelected() {
    if (!map) return;
    const m = props.markers.find((x) => x.id === props.selectedId);
    for (const [id, marker] of byId) {
        const data = props.markers.find((x) => x.id === id);
        if (data) marker.setIcon(icon(data, id === props.selectedId));
    }
    if (!m) return;
    const marker = byId.get(m.id);
    const reveal = () => {
        map!.setView([m.lat, m.lng], Math.max(map!.getZoom(), 11), { animate: true });
        marker?.openPopup();
    };
    if (layerGroup && 'zoomToShowLayer' in layerGroup && marker) {
        (layerGroup as L.MarkerClusterGroup).zoomToShowLayer(marker, () => marker.openPopup());
    } else {
        reveal();
    }
}

onMounted(() => {
    if (!el.value) return;
    map = L.map(el.value, { scrollWheelZoom: false, worldCopyJump: true, zoomControl: true });
    let loaded = 0;
    let errors = 0;
    const tiles = L.tileLayer(page.props.map.tileUrl, {
        attribution: page.props.map.attribution,
        maxZoom: page.props.map.maxZoom,
    });
    tiles.on('loading', () => (loading.value = true));
    tiles.on('load', () => (loading.value = false));
    tiles.on('tileload', () => {
        loaded++;
        tileFailed.value = false;
    });
    tiles.on('tileerror', () => {
        errors++;
        if (errors >= 3 && loaded === 0) {
            tileFailed.value = true;
            loading.value = false;
        }
    });
    tiles.addTo(map);

    // Wheel zoom only after the map has focus, so the page still scrolls normally.
    map.on('focus', () => map?.scrollWheelZoom.enable());
    map.on('blur', () => map?.scrollWheelZoom.disable());

    render();
    let first = true;
    resizeObserver = new ResizeObserver(() => {
        map?.invalidateSize();
        if (first) {
            first = false;
            fit();
        }
    });
    resizeObserver.observe(el.value);
    focusSelected();
});

watch(() => props.markers, render, { deep: false });
watch(() => props.selectedId, focusSelected);

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    map?.remove();
    map = null;
});
</script>

<template>
    <div class="relative h-full min-h-[18rem] w-full overflow-hidden rounded-lg border border-border">
        <div ref="el" class="absolute inset-0" role="region" :aria-label="label" tabindex="-1"></div>
        <div v-if="loading && !tileFailed" class="pointer-events-none absolute inset-0 z-[500] animate-pulse bg-muted/60" aria-hidden="true"></div>
        <p
            v-if="loading && !tileFailed"
            class="absolute left-1/2 top-3 z-[501] -translate-x-1/2 rounded-full bg-card px-3 py-1 text-xs shadow"
            role="status"
        >
            Loading map…
        </p>
        <div
            v-if="tileFailed"
            class="absolute inset-x-3 top-3 z-[501] rounded-md border border-destructive/40 bg-card p-3 text-sm shadow"
            role="alert"
        >
            Map tiles could not be loaded. Check your connection; the places are still listed beside the map.
        </div>
    </div>
</template>
