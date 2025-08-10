<script setup lang="ts">
import {computed, ref} from 'vue'
import {usePage} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

/** FullCalendar */
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import rrulePlugin from '@fullcalendar/rrule'

/** PrimeVue */
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import {useDark} from "@vueuse/core";
import {route} from "ziggy-js";

type OrgEventType = {
    id: number
    name: string
    category?: string | null
    color?: string | null // hex or css color
}

type OrgEventDto = {
    id: number
    title: string
    description?: string | null
    start: string // ISO
    end?: string | null // ISO
    all_day?: boolean
    repeats?: boolean
    rrule?: string | null
    location?: string | null
    type: OrgEventType | null
    masons_only: boolean
    degree_required: 'none' | 'entered apprentice' | 'fellowcraft' | 'master mason'
    open_to: 'all' | 'members' | 'officers'
    is_public: boolean
    can_edit?: boolean
}

type Role = {
    id: number
    guard_name: string
    name: string
    created_at: string
    updated_at: string
}

const $page = usePage()
const isDark = useDark()

const props = defineProps<{
    events: OrgEventDto[]
    types: OrgEventType[]              // for legend
    currentMonth?: string | null
}>()

/** ---- Roles / ability helpers ---- */
const user = computed(() => ($page.props as any)?.auth?.user ?? null)
const userRoles = computed<Role[]>(() => (user.value?.roles ?? []) as Role[])

const hasRole = (role: string) => userRoles.value?.some(r => r.name.toLowerCase() === role.toLowerCase())
const isOfficer = computed(() => hasRole('Officer'))
const isAdmin = computed(() => hasRole('Administrator') || hasRole('Admin'))
const isSecretary = computed(() => hasRole('Secretary'))
const canManage = computed(() => isOfficer.value || isAdmin.value || isSecretary.value)

/** ---- Visibility: who can see details? ----
 * Calendar blocks are always visible; dialog details are gated.
 */
function canViewDetails(event: OrgEventDto): boolean {
    // Officers/Admin/Secretary can always view
    if (canManage.value) return true

    // Not signed in: must not be masons_only AND open_to must be 'all'
    if (!user.value) {
        return !event.masons_only && event.open_to === 'all'
    }

    // Signed-in users
    const isMason = hasRole('Mason') || hasRole('Entered Apprentice') || hasRole('Fellowcraft') || hasRole('Master Mason')
    const isMember = hasRole('Member')
    const isOfficerRole = hasRole('Officer')

    // open_to gate
    if (event.open_to === 'officers' && !(isOfficerRole || isAdmin.value || isSecretary.value)) return false
    if (event.open_to === 'members' && !(isMember || isOfficerRole || isAdmin.value || isSecretary.value)) return false

    // masons_only gate: non-masons can’t see details
    if (event.masons_only && !isMason) return false

    // degree gate
    const isEA = hasRole('Entered Apprentice')
    const isFC = hasRole('Fellowcraft')
    const isMM = hasRole('Master Mason')

    switch (event.degree_required.toLowerCase()) {
        case 'entered apprentice':
            if (!(isEA || isFC || isMM)) return false
            break
        case 'fellowcraft':
            if (!(isFC || isMM)) return false
            break
        case 'master mason':
            if (!isMM) return false
            break
        case 'none':
        default:
            // ok
            break
    }

    return true
}

function pad2(n: number) { return (n < 10 ? '0' : '') + n }
function formatDtStartUTC(isoUtc: string) {
    const d = new Date(isoUtc) // isoUtc has Z already from the server
    return (
        d.getUTCFullYear().toString() +
        pad2(d.getUTCMonth() + 1) +
        pad2(d.getUTCDate()) + 'T' +
        pad2(d.getUTCHours()) +
        pad2(d.getUTCMinutes()) +
        pad2(d.getUTCSeconds()) + 'Z'
    )
}

function toDuration(startIso?: string | null, endIso?: string | null) {
    if (!startIso || !endIso) return undefined
    const ms = new Date(endIso).getTime() - new Date(startIso).getTime()
    if (ms <= 0) return undefined
    const totalMinutes = Math.round(ms / 60000)
    return { minutes: totalMinutes }
}

const calendarEvents = computed(() =>
    props.events
        .filter(event => event.is_public || event.can_edit || canManage.value)
        .map(event => {
            const base = {
                id: String(event.id),
                title: event.title,
                allDay: !!event.all_day,
                classNames: !event.is_public ? ['fc-draft'] : [],
                extendedProps: event
            }

            if (event.rrule) {
                // recurring event
                const rruleWithDtstart = event.start
                    ? `DTSTART:${formatDtStartUTC(event.start)}\n${event.rrule}`
                    : event.rrule

                return {
                    ...base,
                    rrule: rruleWithDtstart,
                }
            } else {
                // single instance
                return {
                    ...base,
                    start: event.start,
                    end: event.end ?? undefined
                }
            }
        })
)


/** ---- Dialog handling ---- */
const showDialog = ref(false)
const selected = ref<OrgEventDto | null>(null)
const canSeeSelected = computed(() => (selected.value ? canViewDetails(selected.value) : false))

function onEventClick(arg: any) {
    selected.value = arg.event.extendedProps as OrgEventDto
    showDialog.value = true
}

function onDateClick(dateInfo: { dateStr: string }) {
    if (!canManage.value) return
    const url = route('events.create')
    if (url) {
        window.location.href = `${url}?start=${encodeURIComponent(dateInfo.dateStr)}`
    }
}

function onCreateBlank() {
    if (!canManage.value) return
    const url = route('events.create') // adjust
    if (url) window.location.href = url
}

function onEditSelected() {
    if (!canManage.value || !selected.value) return
    const url = route('events.edit', selected.value.id) // adjust
    if (url) window.location.href = url
}

function onDeleteSelected() {
    if (!canManage.value || !selected.value) return
    const url = route('events.destroy', selected.value.id) // adjust
    if (url) window.location.href = url
}

/** ---- FullCalendar options ---- */
const todayISO = new Date().toISOString().slice(0, 10)
const initialDate = computed(() => props.currentMonth ?? todayISO)

const headerToolbar = {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth'
}

function isOverflowing(el?: Element | null) {
    if (!el) return false
    const e = el as HTMLElement
    return e.scrollWidth > e.clientWidth
}

function eventDidMount(info: any) {
    const event = info.event.extendedProps as OrgEventDto
    const main = (info.el.querySelector('.fc-event-main') as HTMLElement) || (info.el as HTMLElement)

    // guarantee colors (some themes don’t apply backgroundColor reliably)
    const bg = event.type?.color || 'var(--p-primary-color, #5868fc)'
    main.style.backgroundColor = bg
    main.style.borderColor = bg
    main.style.color = '#fff'

    // add tooltip only if truncated
    const titleEl = info.el.querySelector('.fc-event-title')
    if (isOverflowing(titleEl)) {
        info.el.setAttribute('title', event.title)
    }

    // draft badge + class
    if (!event.is_public) {
        info.el.classList.add('fc-draft')
        const badge = document.createElement('span')
        badge.className = 'fc-draft-badge'
        badge.textContent = 'Draft'
        main.appendChild(badge)
    }

    // lock icon for restricted-detail events (optional, keep if you like)
    const allowed = canViewDetails(event)
    if (!allowed) {
        const lock = document.createElement('span')
        lock.className = 'pi pi-lock fc-lock-icon'
        Object.assign(lock.style, { fontSize: '0.75rem', marginLeft: '0.375rem', verticalAlign: 'middle' })
        info.el.querySelector('.fc-event-title')?.append(lock)
        info.el.style.filter = 'saturate(0.85) brightness(0.98)'
    }
}

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin, rrulePlugin],
    timeZone: 'local',
    initialView: 'dayGridMonth',
    initialDate: initialDate.value,
    headerToolbar,
    events: calendarEvents.value,   // your computed from earlier
    dayMaxEvents: true,
    fixedWeekCount: true,
    showNonCurrentDates: true,
    firstDay: 0,
    aspectRatio: 1.1,
    eventClick: onEventClick,
    dateClick: onDateClick,
    eventDidMount
}))

/** Legend chips for types */
const legendTypes = computed(() => props.types || [])
</script>

<template>
    <AppLayout title="Events">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Events</h2>
                <div class="hidden sm:flex gap-2">
                    <Button
                        v-if="canManage"
                        icon="pi pi-plus"
                        label="New Event"
                        @click="onCreateBlank"
                    />
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
            <!-- Type legend -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-300 mr-2">Legend:</span>
                <Tag
                    v-for="t in legendTypes"
                    :key="t.id"
                    :value="t.name"
                    :style="{ backgroundColor: t.color || 'var(--p-primary-color)', color: '#fff', borderColor: 'transparent' }"
                    rounded
                />
                <span class="ml-auto flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
          <i class="pi pi-lock"/> Restricted details
        </span>
            </div>

            <!-- Calendar -->
            <Card class="bg-white dark:bg-surface-800 shadow">
                <template #content>
                    <FullCalendar :options="calendarOptions" />
                </template>
            </Card>

            <!-- Mobile create -->
            <div class="sm:hidden">
                <Button v-if="canManage" icon="pi pi-plus" label="New Event" class="w-full" @click="onCreateBlank"/>
            </div>
        </div>

        <!-- Details Dialog -->
        <Dialog
            v-model:visible="showDialog"
            :header="selected ? selected.title : 'Event'"
            modal
            class="w-full sm:w-[36rem]"
        >
            <div v-if="selected">
                <!-- top chips -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <Tag
                        v-if="selected.type && selected.type.name"
                        :value="selected.type.name"
                        :style="{ backgroundColor: (selected.type && selected.type.color) || 'var(--p-primary-color)', color: '#fff', borderColor: 'transparent' }"
                        rounded
                    />
                    <Tag v-if="selected && !selected.is_public" value="Draft" severity="warning" />
                    <Badge v-if="selected.all_day" value="All day" />
                    <Badge v-if="selected.masons_only" value="Masons only" severity="danger" />
                    <Badge v-else value="Public" severity="success" />
                    <Badge v-if="selected.open_to === 'members'" value="Members" severity="info" />
                    <Badge v-if="selected.open_to === 'officers'" value="Officers" severity="warning" />
                </div>

                <!-- times -->
                <div class="text-sm text-gray-700 dark:text-gray-200">
                    <div class="mb-1">
                        <i class="pi pi-calendar mr-2" />
                        <span>
          {{ new Date(selected.start).toLocaleString() }}
          <template v-if="selected.end"> – {{ new Date(selected.end).toLocaleString() }}</template>
        </span>
                    </div>
                    <div v-if="selected.location" class="mb-2">
                        <i class="pi pi-map-marker mr-2" />
                        <span>{{ selected.location }}</span>
                    </div>
                </div>

                <Divider class="my-3" />

                <!-- description / restrictions -->
                <div v-if="canSeeSelected" class="prose dark:prose-invert max-w-none text-sm">
                    <div v-html="selected.description || '<em>No description provided.</em>'"></div>
                </div>
                <div v-else class="text-sm text-amber-700 dark:text-amber-300">
                    <i class="pi pi-lock mr-2" />
                    You don’t have permission to view the details of this event.
                </div>
            </div>

            <!-- footer MUST be a direct child slot -->
            <template #footer>
                <div class="w-full flex justify-between gap-2">
                    <div class="flex">
                        <Button
                            v-if="canManage && selected"
                            icon="pi pi-delete"
                            label="Delete"
                            severity="danger"
                            @click="onDeleteSelected"
                        />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button
                            v-if="canManage && selected"
                            icon="pi pi-pencil"
                            label="Edit"
                            @click="onEditSelected"
                        />
                        <Button
                            icon="pi pi-times"
                            label="Close"
                            severity="secondary"
                            text
                            @click="showDialog = false"
                        />
                    </div>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Ensure inline background/border applied by eventDidMount take effect */
:deep(.fc .fc-daygrid-event .fc-event-main) {
    background-color: inherit;
    border-color: inherit;
    color: inherit;
}

/* Draft: diagonal stripes + dashed border */
:deep(.fc-draft .fc-event-main) {
    position: relative;
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.18) 0,
        rgba(255, 255, 255, 0.18) 6px,
        rgba(255, 255, 255, 0) 6px,
        rgba(255, 255, 255, 0) 12px
    ) !important;
    border-style: dashed !important;
}

:deep(.app-dark .fc-draft .fc-event-main) {
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.25) 0,
        rgba(255, 255, 255, 0.25) 6px,
        rgba(255, 255, 255, 0) 6px,
        rgba(255, 255, 255, 0) 12px
    ) !important;
}

/* Small 'Draft' badge in top-right */
:deep(.fc-draft-badge) {
    position: absolute;
    top: 2px;
    right: 4px;
    font-size: 10px;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 9999px;
    background: rgba(0, 0, 0, .35);
    color: #fff;
    pointer-events: none;
}

:deep(.app-dark .fc-draft-badge) {
    background: rgba(0, 0, 0, .5);
}

/* Optional: lock icon polish */
:deep(.fc-lock-icon) { opacity: .95 }

</style>
