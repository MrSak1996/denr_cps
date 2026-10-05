<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { BadgeCheck, EllipsisVertical, Eye, FileSearch, MessageSquare, PrinterCheck, Search, X } from 'lucide-vue-next';
import Drawer from 'primevue/drawer'; // PrimeVue v4 (use Sidebar on v3)
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

type Action = 'view' | 'preview' | 'receive';

const props = withDefaults(
    defineProps<{
        applications: any[];
        /** which rows appear in the bottom sheet */
        actions?: Action[];
        /** needed only when 'receive' is in actions */
        buttonState?: (app: any) => { receiveDisable: boolean };
        /** per-row permission for opening the application */
        canView?: (app: any) => boolean;
        /** show a "View comments" row for this app */
        canComment?: (app: any) => boolean;
    }>(),
    {
        actions: () => ['view', 'preview', 'receive'],
        buttonState: () => ({ receiveDisable: false }),
        canView: () => true,
        canComment: () => false,
    },
);

const emit = defineEmits<{
    (e: 'preview', app: any): void;
    (e: 'receive', app: any): void;
    (e: 'comments', app: any): void;
}>();

const has = (a: Action) => props.actions.includes(a);

/* ---------- helpers ---------- */
const displayName = (a: any) => (a.application_type === 'Individual' ? a.applicant_name : a.company_name);

const statusSeverity = (a: any) => {
    const t: string = a.status_title ?? '';
    if ((a.application_status >= 25 && a.application_status <= 27) || t.startsWith('Returned')) return 'danger';
    if (t.startsWith('Approved')) return 'info';
    if (t.startsWith('Endorsed')) return 'success';
    return 'warn';
};

const viewHref = (app: any) =>
    route('applications.edit', {
        application_id: app.id,
        type: app.application_type,
        step: 4,
    });

const linkable = (app: any) => has('view') && props.canView(app);

/* ---------- search + status filter ---------- */
const search = ref('');
const activeStatus = ref('');

const statusList = computed(() => [...new Set(props.applications.map((a) => a.status_title).filter(Boolean))] as string[]);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.applications.filter((a) => {
        if (activeStatus.value && a.status_title !== activeStatus.value) return false;
        if (!q) return true;
        return [a.application_no, a.permit_no, a.application_type, a.status_title, a.applicant_name, a.company_name, a.office_title]
            .filter(Boolean)
            .some((v) => String(v).toLowerCase().includes(q));
    });
});

const clearFilters = () => {
    search.value = '';
    activeStatus.value = '';
};

/* ---------- bottom sheet ---------- */
const sheetOpen = ref(false);
const selected = ref<any>(null);

const openSheet = (app: any) => {
    selected.value = app;
    sheetOpen.value = true;
};

const doView = () => {
    sheetOpen.value = false;
    router.visit(viewHref(selected.value));
};

const doPreview = () => {
    sheetOpen.value = false;
    emit('preview', selected.value);
};

const doReceive = () => {
    if (props.buttonState(selected.value).receiveDisable) return;
    sheetOpen.value = false;
    emit('receive', selected.value);
};

const doComments = () => {
    sheetOpen.value = false;
    emit('comments', selected.value);
};
</script>

<template>
    <section class="mx-auto w-full max-w-2xl">
        <div class="sticky top-0 z-10 -mx-1 bg-white/95 px-1 pt-1 pb-2 backdrop-blur">
            <label class="relative block">
                <span class="sr-only">Search applications</span>
                <Search :size="16" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-gray-400" />
                <input v-model="search" type="search" inputmode="search" enterkeyhint="search" autocomplete="off"
                    placeholder="Search name, application no. or status"
                    class="search-input h-11 w-full rounded-full border border-gray-200 bg-gray-50 pr-10 pl-10 text-base text-gray-800 placeholder:text-gray-400 focus:border-teal-600 focus:bg-white focus:ring-2 focus:ring-teal-600/20 focus:outline-none" />
                <button v-if="search" type="button" aria-label="Clear search"
                    class="absolute top-1/2 right-1.5 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-gray-500 active:bg-gray-200"
                    @click="search = ''">
                    <X :size="16" />
                </button>
            </label>

            <div v-if="statusList.length > 1" class="no-scrollbar -mx-1 mt-2 flex gap-2 overflow-x-auto px-1">
                <button type="button" class="chip" :class="{ 'chip-active': !activeStatus }" @click="activeStatus = ''">
                    All
                </button>
                <button v-for="s in statusList" :key="s" type="button" class="chip"
                    :class="{ 'chip-active': activeStatus === s }" @click="activeStatus = activeStatus === s ? '' : s">
                    {{ s }}
                </button>
            </div>

            <p class="mt-2 px-1 text-xs text-gray-500" aria-live="polite">
                {{ filtered.length }} of {{ applications.length }} applications
            </p>
        </div>

        <ul v-if="filtered.length" class="space-y-2.5 pb-6">
            <li v-for="app in filtered" :key="app.id"
                class="flex items-stretch overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <component :is="linkable(app) ? Link : 'div'" v-bind="linkable(app) ? { href: viewHref(app) } : {}"
                    class="block min-w-0 flex-1 p-3" :class="{ 'active:bg-gray-50': linkable(app) }">
                    <div class="truncate text-sm font-semibold text-gray-900">{{ app.application_no }}</div>
                    <div v-if="displayName(app)" class="mt-0.5 truncate text-xs text-gray-700">{{ displayName(app) }}</div>
                    <div class="text-xs text-gray-500">{{ app.application_type }}</div>
                    <Tag :value="app.status_title" :severity="statusSeverity(app)" class="status-tag mt-2 max-w-full text-xs" />
                    <div v-if="app.updated_by" class="mt-1 truncate text-[11px] text-gray-500 italic">{{ app.updated_by }}</div>
                </component>

                <button type="button" aria-label="More actions"
                    class="flex w-12 shrink-0 items-center justify-center text-gray-500 active:bg-gray-100"
                    @click="openSheet(app)">
                    <EllipsisVertical :size="20" />
                </button>
            </li>
        </ul>

        <div v-else class="flex flex-col items-center px-6 py-14 text-center">
            <FileSearch :size="40" class="text-gray-300" />
            <p class="mt-3 text-sm font-medium text-gray-700">No applications found</p>
            <p class="mt-1 text-xs text-gray-500">Try a different name or application number, or clear the filters.</p>
            <button v-if="search || activeStatus" type="button"
                class="mt-4 h-10 rounded-full bg-teal-700 px-5 text-sm font-medium text-white active:bg-teal-800"
                @click="clearFilters">
                Clear filters
            </button>
        </div>

        <Drawer v-model:visible="sheetOpen" position="bottom" :header="selected?.application_no"
            :style="{ height: 'auto', borderTopLeftRadius: '1rem', borderTopRightRadius: '1rem' }">
            <div v-if="selected" class="sheet-body -mx-2 divide-y divide-gray-100">
                <button v-if="has('view') && canView(selected)" type="button" class="sheet-row" @click="doView">
                    <Eye :size="20" class="text-teal-700" />
                    <span>View application</span>
                </button>

                <button v-if="has('preview')" type="button" class="sheet-row" @click="doPreview">
                    <PrinterCheck :size="20" class="text-blue-800" />
                    <span>Preview / print</span>
                </button>

                <button v-if="has('receive')" type="button" class="sheet-row"
                    :disabled="buttonState(selected).receiveDisable" @click="doReceive">
                    <BadgeCheck :size="20" class="text-teal-700" />
                    <span class="flex flex-col items-start">
                        <span>Receive application</span>
                        <span v-if="buttonState(selected).receiveDisable" class="text-xs font-normal text-gray-500">
                            Not available yet
                        </span>
                    </span>
                </button>

                <button v-if="canComment(selected)" type="button" class="sheet-row" @click="doComments">
                    <MessageSquare :size="20" class="text-blue-800" />
                    <span>View comments</span>
                </button>
            </div>
        </Drawer>
    </section>
</template>

<style scoped>
.search-input {
    -webkit-appearance: none;
    appearance: none;
}

.search-input::-webkit-search-cancel-button,
.search-input::-webkit-search-decoration {
    -webkit-appearance: none;
    display: none;
}

.chip {
    flex-shrink: 0;
    height: 2rem;
    padding: 0 0.85rem;
    border-radius: 999px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #4b5563;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
}

.chip-active {
    background: #0f766e;
    border-color: #0f766e;
    color: #fff;
}

.no-scrollbar {
    scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.status-tag {
    white-space: normal;
    text-align: left;
    line-height: 1.25;
}

.sheet-row {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.9rem;
    min-height: 3.25rem;
    padding: 0.5rem;
    font-size: 0.95rem;
    font-weight: 500;
    color: #111827;
    text-align: left;
}

.sheet-row:active:not(:disabled) {
    background: #f3f4f6;
}

.sheet-row:disabled {
    opacity: 0.45;
}

.sheet-body {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>